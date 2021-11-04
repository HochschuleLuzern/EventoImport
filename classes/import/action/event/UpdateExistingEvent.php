<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\IliasEventWrapper;
use EventoImport\import\settings\DefaultEventSettings;

class UpdateExistingEvent extends EventAction
{
    private $ilias_event;

    public function __construct(EventoEvent $evento_event, IliasEventWrapper $ilias_event, IliasEventObjectFactory $event_factory, DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, UserFacade $user_facade, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        parent::__construct($evento_event, $event_factory, $event_settings, $repository_facade, $user_facade, $logger, $rbac_services);

        $this->ilias_event = $ilias_event;
    }

    public function executeAction()
    {
        $this->synchronizeUsersInRole($this->ilias_event);
    }
}