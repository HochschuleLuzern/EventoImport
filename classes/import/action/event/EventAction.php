<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\EventoImportAction;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\IliasEventWrapper;
use EventoImport\communication\api_models\EventoUserShort;

abstract class EventAction implements EventoImportAction
{
    protected $evento_event;
    protected $repository_facade;
    protected $event_object_factory;
    protected $logger;
    protected $rbac_services;
    protected $event_settings;
    /**
     * @var UserFacade
     */
    protected $user_facade;

    public function __construct(EventoEvent $evento_event, IliasEventObjectFactory $event_object_factory, \EventoImport\import\settings\DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, \EventoImport\import\db\UserFacade $user_facade, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        $this->evento_event = $evento_event;
        $this->repository_facade = $repository_facade;
        $this->user_facade = $user_facade;
        $this->event_object_factory = $event_object_factory;
        $this->logger = $logger;
        $this->rbac_services = $rbac_services;
        $this->event_settings = $event_settings;
    }

    protected function synchronizeUsersInRole(IliasEventWrapper $ilias_event)
    {
        /** @var EventoUserShort $employee */
        foreach ($this->evento_event->getEmployees() as $employee) {
            $user_id = $this->user_facade->fetchUserIdByMembership($this->evento_event->getEventoId(), $employee);

            if (!is_null($user_id)) {
                $ilias_event->addUserAsAdminToEvent($user_id);
            } else {
                $user_id = $this->user_facade->eventoUserRepository()->getIliasUserIdByEventoId($employee->getEventoId());
                if (!is_null($user_id)) {
                    $ilias_event->addUserAsAdminToEvent($user_id);
                }
            }
        }

        /** @var EventoUserShort $student */
        foreach ($this->evento_event->getStudents() as $student) {
            $user_id = $this->user_facade->fetchUserIdByMembership($this->evento_event->getEventoId(), $student);

            if (!is_null($user_id)) {
                $ilias_event->addUserAsStudentToEvent($user_id);
            } else {
                $user_id = $this->user_facade->eventoUserRepository()->getIliasUserIdByEventoId($student->getEventoId());
                if (!is_null($user_id)) {
                    $ilias_event->addUserAsStudentToEvent($user_id);
                }
            }
        }
    }
}
