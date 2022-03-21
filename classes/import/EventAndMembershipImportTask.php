<?php declare(strict_types=1);

namespace EventoImport\import;

use EventoImport\communication\EventoEventImporter;
use EventoImport\import\action\EventImportActionDecider;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use EventoImport\import\data_management\repository\model\IliasEventoEvent;

/**
 * Copyright (c) 2017 Hochschule Luzern
 * This file is part of the EventoImport-Plugin for ILIAS.
 * EventoImport-Plugin for ILIAS is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * EventoImport-Plugin for ILIAS is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with EventoImport-Plugin for ILIAS.  If not,
 * see <http://www.gnu.org/licenses/>.
 */

/**
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class EventAndMembershipImportTask
{
    private EventoEventImporter $evento_importer;
    private EventImportActionDecider $event_import_action_decider;
    private IliasEventoEventObjectRepository $evento_event_obj_repo;
    private Logger $logger;

    public function __construct(
        EventoEventImporter $evento_importer,
        EventImportActionDecider $event_import_action_decider,
        IliasEventoEventObjectRepository $evento_event_obj_repo,
        Logger $logger
    ) {
        $this->evento_importer = $evento_importer;
        $this->event_import_action_decider = $event_import_action_decider;
        $this->evento_event_obj_repo = $evento_event_obj_repo;
        $this->logger = $logger;
    }

    public function run() : void
    {
        $this->importEvents();
        $this->deleteInEventoRemovedEvents();
    }

    private function importEvents() : void
    {
        do {
            try {
                $this->importNextEventPage();
            } catch (\ilEventoImportCommunicationException $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->logger->logException('Importing Event Page', $e->getMessage());
            }
        } while ($this->evento_importer->hasMoreData());
    }

    private function deleteInEventoRemovedEvents()
    {
        $list = $this->evento_event_obj_repo->getActiveEventsWithLastImportOlderThanOneWeek();

        /** @var $ilias_evento_event IliasEventoEvent*/
        foreach ($list as $ilias_evento_event) {
            try {
                // Ensure that the user is not being returned by the api right now
                $result = $this->evento_importer->fetchEventDataRecordById($ilias_evento_event->getEventoEventId());

                if (is_null($result)) {
                    $action = $this->event_import_action_decider->determineDeleteAction($ilias_evento_event);
                    $action->executeAction();
                } else {
                    $this->evento_event_obj_repo->registerEventAsDelivered($result->getEventoId());
                }
            } catch (\Exception $e) {
                $this->logger->logException('Deleting Event', 'Exception on deleting event with evento_id ' . $ilias_evento_event->getEventoEventId()
                    . ', exception message: ' . $e->getMessage());
            }
        }
    }

    private function importNextEventPage() : void
    {
        foreach ($this->evento_importer->fetchNextEventDataSet() as $data_set) {
            try {
                $evento_event = new EventoEvent($data_set);

                $action = $this->event_import_action_decider->determineImportAction($evento_event);
                $action->executeAction();
            } catch (\Exception $e) {
                $this->logger->logException('Importing Event', $e->getMessage());
            }
        }
    }
}
