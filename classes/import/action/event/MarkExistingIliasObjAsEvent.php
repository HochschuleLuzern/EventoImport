<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\IliasEventObjectService;
use EventoImport\import\db\MembershipManager;

class MarkExistingIliasObjAsEvent implements EventAction
{
    private EventoEvent $evento_event;
    private \ilContainer $ilias_object;
    private IliasEventObjectService $repository_facade;
    private MembershipManager $membership_manager;
    private \EventoImport\import\Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, \ilContainer $ilias_object, IliasEventObjectService $repository_facade, MembershipManager $membership_manager, \EventoImport\import\Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->ilias_object = $ilias_object;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = \EventoImport\import\Logger::CREVENTO_MA_EXISTING_ILIAS_COURSE_AS_EVENT_MARKED;
    }

    public function executeAction() : void
    {
        $ilias_event = $this->repository_facade->addNewIliasEvent($this->evento_event, $this->ilias_object);

        $this->membership_manager->syncMemberships($this->evento_event, $ilias_event);
        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $this->ilias_object->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}
