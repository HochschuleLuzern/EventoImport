<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\MembershipManager;

/**
 * Class MarkExistingIliasObjAsEvent
 * @package EventoImport\import\action\event
 */
class MarkExistingIliasObjAsEvent extends EventAction
{
    /** @var \ilContainer  */
    private \ilContainer $ilias_object;

    /**
     * MarkExistingIliasObjAsEvent constructor.
     * @param EventoEvent                                        $evento_event
     * @param \ilContainer                                       $ilias_object
     * @param IliasEventObjectFactory                            $event_object_factory
     * @param \EventoImport\import\settings\DefaultEventSettings $event_settings
     * @param RepositoryFacade                                   $repository_facade
     * @param UserFacade                                         $user_facade
     * @param MembershipManager                                  $membership_manager
     * @param \ilEventoImportLogger                              $logger
     * @param \ILIAS\DI\RBACServices                             $rbac_services
     */
    public function __construct(EventoEvent $evento_event, \ilContainer $ilias_object, IliasEventObjectFactory $event_object_factory, \EventoImport\import\settings\DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, UserFacade $user_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        parent::__construct($evento_event, $event_object_factory, \ilEventoImportLogger::CREVENTO_MA_EXISTING_ILIAS_COURSE_AS_EVENT_MARKED, $event_settings, $repository_facade, $user_facade, $membership_manager, $logger, $rbac_services);

        $this->ilias_object = $ilias_object;
    }

    public function executeAction() : void
    {
        if ($this->ilias_object instanceof \ilObjCourse && $this->ilias_object->getType() == 'crs') {
            $this->repository_facade->addNewSingleEventCourse($this->evento_event, $this->ilias_object);
        }
        throw new \Error("not implemented yet");
    }
}
