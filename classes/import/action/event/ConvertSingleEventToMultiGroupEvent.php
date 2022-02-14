<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\IliasEventObjectService;
use EventoImport\import\db\MembershipManager;
use EventoImport\import\db\model\IliasEventoEvent;

class ConvertSingleEventToMultiGroupEvent implements EventAction
{
    private EventoEvent $evento_event;
    private IliasEventoEvent $ilias_event;
    private \ilContainer $current_event_object;
    private IliasEventObjectFactory $event_object_factory;
    private IliasEventObjectService $repository_facade;
    private MembershipManager $membership_manager;
    private \EventoImport\import\Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, IliasEventoEvent $ilias_event, IliasEventObjectFactory $event_object_factory, IliasEventObjectService $repository_facade, MembershipManager $membership_manager, \EventoImport\import\Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->ilias_event = $ilias_event;
        // TODO: Refactor with refactoring of repository facade
        $this->current_event_object = new \ilObjCourse($ilias_event->getRefId(), true);
        $this->event_object_factory = $event_object_factory;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = \EventoImport\import\Logger::CREVENTO_MA_SINGLE_EVENT_TO_MULTI_GROUP_CONVERTED;
    }

    public function executeAction() : void
    {
        // Only change title of crs-obj if it has not been changed by an admin
        if ($this->evento_event->getName() == $this->current_event_object->getTitle()) {
            $this->current_event_object->setTitle($this->evento_event->getGroupName());
            $this->current_event_object->update();
        }

        // Create first subgroup which now is the new event object
        $event_sub_group = $this->event_object_factory->buildNewGroupObject($this->evento_event->getName(), $this->evento_event->getDescription(), $this->current_event_object->getRefId());

        // Update DB-Entries
        $this->repository_facade->addNewIliasEventoParentEvent($this->evento_event, $this->current_event_object);
        $updated_ilias_event = $this->repository_facade->updateIliasEventoEvent($this->evento_event, $this->ilias_event, $event_sub_group);

        $this->membership_manager->syncMemberships($this->evento_event, $updated_ilias_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $updated_ilias_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}
