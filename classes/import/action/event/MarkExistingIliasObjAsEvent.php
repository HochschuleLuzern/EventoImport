<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\MembershipManager;

class MarkExistingIliasObjAsEvent extends EventAction
{
    public function __construct(EventoEvent $evento_event, \ilContainer $ilias_object, IliasEventObjectFactory $event_object_factory, \EventoImport\import\settings\DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, UserFacade $user_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        parent::__construct($evento_event, $event_object_factory, \ilEventoImportLogger::CREVENTO_MA_EXISTING_ILIAS_COURSE_AS_EVENT_MARKED ,$event_settings, $repository_facade, $user_facade, $membership_manager,$logger, $rbac_services);

        $this->ilias_object = $ilias_object;
    }

    public function executeAction()
    {
        throw new \Error("not implemented yet");
        // TODO: Implement executeAction() method.
    }
}
