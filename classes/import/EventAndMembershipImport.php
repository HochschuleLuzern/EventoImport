<?php declare(strict_types=1);

namespace EventoImport\import;

use EventoImport\communication\EventoEventImporter;
use EventoImport\import\action\EventImportActionDecider;
use EventoImport\communication\api_models\EventoEvent;

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
class EventAndMembershipImport
{
    private EventoEventImporter $evento_importer;
    private EventImportActionDecider $event_import_action_decider;
    private Logger $logger;

    public function __construct(
        EventoEventImporter $evento_importer,
        EventImportActionDecider $event_import_action_decider,
        Logger $logger
    ) {
        $this->evento_importer = $evento_importer;
        $this->event_import_action_decider = $event_import_action_decider;
        $this->logger = $logger;
    }

    public function run() : void
    {
        $this->importEvents();
    }

    private function importEvents() : void
    {
        do {
            try {
                $this->importNextEventPage();
            } catch (\Exception $e) {
                $this->logger->logException('Importing Event Page', $e->getMessage());
            }
        } while ($this->evento_importer->hasMoreData());
    }

    private function importNextEventPage() : void
    {
        foreach ($this->evento_importer->fetchNextEventDataSet() as $data_set) {
            try {
                $evento_event = new EventoEvent($data_set);

                $action = $this->event_import_action_decider->determineAction($evento_event);
                $action->executeAction();
            } catch (\Exception $e) {
                $this->logger->logException('Importing Event', $e->getMessage());
            }
        }
    }
}
