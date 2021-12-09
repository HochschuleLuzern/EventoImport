<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\IliasEventWrapper;
use EventoImport\import\settings\DefaultEventSettings;
use EventoImport\import\db\MembershipManager;

class UpdateExistingEvent extends EventAction
{
    private $ilias_event;

    public function __construct(EventoEvent $evento_event, IliasEventWrapper $ilias_event, IliasEventObjectFactory $event_factory, DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, UserFacade $user_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        parent::__construct($evento_event, $event_factory, \ilEventoImportLogger::CREVENTO_MA_SUBS_UPDATED, $event_settings, $repository_facade, $user_facade, $membership_manager, $logger, $rbac_services);

        $this->ilias_event = $ilias_event;
    }

    public function executeAction()
    {
        $ilias_event_obj = $this->ilias_event->getIliasEventoEventObj();
        if (is_null($ilias_event_obj->getStartDate())) {
            $updated_obj = new IliasEventoEvent(
                $ilias_event_obj->getEventoEventId(),
                $ilias_event_obj->getEventoTitle(),
                $ilias_event_obj->getEventoDescription(),
                $ilias_event_obj->getEventoType(),
                $ilias_event_obj->wasAutomaticallyCreated(),
                $this->evento_event->getStartDate(),
                $this->evento_event->getEndDate(),
                $ilias_event_obj->getIliasType(),
                $ilias_event_obj->getRefId(),
                $ilias_event_obj->getObjId(),
                $ilias_event_obj->getAdminRoleId(),
                $ilias_event_obj->getStudentRoleId(),
                $ilias_event_obj->getParentEventKey()
            );

            $this->repository_facade->iliasEventoEventRepository()->updateIliasEventoEvent($updated_obj);
        }
        $this->synchronizeUsersInRole($this->ilias_event);
        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $this->ilias_event->getIliasEventoEventObj()->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}
