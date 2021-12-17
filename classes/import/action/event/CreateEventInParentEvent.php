<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\MembershipManager;

/**
 * Class CreateEventInParentEvent
 * @package EventoImport\import\action\event
 */
class CreateEventInParentEvent extends EventAction
{
    /** @var IliasEventoParentEvent */
    private $parent_event;

    /**
     * CreateEventInParentEvent constructor.
     * @param EventoEvent                                        $evento_event
     * @param IliasEventoParentEvent                             $parent_event
     * @param IliasEventObjectFactory                            $event_object_factory
     * @param \EventoImport\import\settings\DefaultEventSettings $event_settings
     * @param RepositoryFacade                                   $repository_facade
     * @param UserFacade                                         $user_facade
     * @param MembershipManager                                  $membership_manager
     * @param \ilEventoImportLogger                              $logger
     * @param \ILIAS\DI\RBACServices                             $rbac_services
     */
    public function __construct(EventoEvent $evento_event, IliasEventoParentEvent $parent_event, IliasEventObjectFactory $event_object_factory, \EventoImport\import\settings\DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, UserFacade $user_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        parent::__construct(
            $evento_event,
            $event_object_factory,
            \ilEventoImportLogger::CREVENTO_MA_EVENT_IN_EXISTING_PARENT_EVENT_CREATED,
            $event_settings,
            $repository_facade,
            $user_facade,
            $membership_manager,
            $logger,
            $rbac_services
        );

        $this->parent_event = $parent_event;
    }

    public function executeAction() : void
    {
        $event_sub_group = $this->event_object_factory->buildNewGroupObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->parent_event->getRefId(),
        );

        $parent_event_ilias_obj = new \ilObjCourse($this->parent_event->getRefId(), true);

        $event_wrapper = $this->repository_facade->addNewEventToExistingMultiGroupEvent($this->evento_event, $parent_event_ilias_obj, $event_sub_group);
        $this->synchronizeUsersInRole($event_wrapper);
        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $event_sub_group->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}
