<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\MembershipManager;

/**
 * Class CreateEventWithParent
 * @package EventoImport\import\action\event
 */
class CreateEventWithParent extends EventAction
{
    /** @var int  */
    private int $destination_ref_id;

    /**
     * CreateEventWithParent constructor.
     * @param EventoEvent                                        $evento_event
     * @param int                                                $destination_ref_id
     * @param IliasEventObjectFactory                            $event_object_factory
     * @param \EventoImport\import\settings\DefaultEventSettings $event_settings
     * @param RepositoryFacade                                   $repository_facade
     * @param UserFacade                                         $user_facade
     * @param MembershipManager                                  $membership_manager
     * @param \ilEventoImportLogger                              $logger
     * @param \ILIAS\DI\RBACServices                             $rbac_services
     */
    public function __construct(EventoEvent $evento_event, int $destination_ref_id, IliasEventObjectFactory $event_object_factory, \EventoImport\import\settings\DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, UserFacade $user_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        parent::__construct($evento_event, $event_object_factory, \ilEventoImportLogger::CREVENTO_MA_EVENT_WITH_PARENT_EVENT_CREATED, $event_settings, $repository_facade, $user_facade, $membership_manager, $logger, $rbac_services);
        $this->destination_ref_id = $destination_ref_id;
    }

    public function executeAction() : void
    {
        $parent_event_crs_obj = $this->event_object_factory->buildNewCourseObject(
            $this->evento_event->getGroupName(),
            $this->evento_event->getDescription(),
            $this->destination_ref_id,
        );

        $event_sub_group = $this->event_object_factory->buildNewGroupObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $parent_event_crs_obj->getRefId(),
        );

        $event_wrapper = $this->repository_facade->addNewMultiEventCourseAndGroup($this->evento_event, $parent_event_crs_obj, $event_sub_group);
        $this->synchronizeUsersInRole($event_wrapper);
        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $event_sub_group->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}
