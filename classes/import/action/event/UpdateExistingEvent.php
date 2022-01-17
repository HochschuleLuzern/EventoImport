<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\MembershipManager;

/**
 * Class UpdateExistingEvent
 * @package EventoImport\import\action\event
 */
class UpdateExistingEvent implements EventAction
{
    private EventoEvent $evento_event;
    private IliasEventoEvent $ilias_event;
    private RepositoryFacade $repository_facade;
    private MembershipManager $membership_manager;
    private \ilEventoImportLogger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, IliasEventoEvent $ilias_event, RepositoryFacade $repository_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger)
    {
        $this->evento_event = $evento_event;
        $this->ilias_event = $ilias_event;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = \ilEventoImportLogger::CREVENTO_MA_SUBS_UPDATED;
    }

    public function executeAction() : void
    {
        $this->membership_manager->syncMemberships($this->evento_event, $this->ilias_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $this->ilias_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}
