<?php

namespace EventoImport\import;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use ILIAS\DI\RBACServices;

class IliasEventWrapperSingleEvent extends IliasEventWrapper
{
    private $evento_event;
    private $ilias_event_object;

    public function __construct(IliasEventoEvent $event, \ilObjCourse $ilias_event_object, RBACServices $rbac_services = null)
    {
        parent::__construct($rbac_services);

        $this->evento_event = $event;
        $this->ilias_event_object = $ilias_event_object;
    }

    public function addUserAsAdminToEvent(int $user_id)
    {
        $this->addUserToGivenRole($user_id, $this->evento_event->getAdminRoleId());
    }

    public function addUserAsStudentToEvent(int $user_id)
    {
        $this->addUserToGivenRole($user_id, $this->evento_event->getStudentRoleId());
    }

    public function getIliasEventoEventObj() : IliasEventoEvent
    {
        return $this->evento_event;
    }
}