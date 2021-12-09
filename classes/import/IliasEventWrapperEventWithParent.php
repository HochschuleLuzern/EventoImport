<?php

namespace EventoImport\import;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use ILIAS\DI\RBACServices;

class IliasEventWrapperEventWithParent extends IliasEventWrapper
{
    private $parent_event;
    private $parent_event_obj;
    private $sub_event;
    private $sub_event_obj;

    public function __construct(IliasEventoParentEvent $parent_event, \ilObjCourse $parent_event_obj, IliasEventoEvent $sub_event, \ilObjGroup $sub_event_obj, RBACServices $rbac_services = null)
    {
        parent::__construct($rbac_services);

        $this->parent_event = $parent_event;
        $this->parent_event_obj = $parent_event_obj;
        $this->sub_event = $sub_event;
        $this->sub_event_obj = $sub_event_obj;
    }

    public function addUserAsAdminToEvent($user_id)
    {
        $this->addUserToGivenRole($user_id, $this->parent_event->getAdminRoleId());
        $this->addUserToGivenRole($user_id, $this->sub_event->getAdminRoleId());
    }

    public function addUserAsStudentToEvent($user_id)
    {
        $this->addUserToGivenRole($user_id, $this->parent_event->getStudentRoleId());
        $this->addUserToGivenRole($user_id, $this->sub_event->getStudentRoleId());
    }

    public function getIliasEventoEventObj() : IliasEventoEvent
    {
        return $this->sub_event;
    }

    public function getAllAdminRoles() : array
    {
        return [
            $this->parent_event->getAdminRoleId(),
            $this->sub_event->getAdminRoleId()
        ];
    }

    public function getAllMemberRoles() : array
    {
        return [
            $this->parent_event->getStudentRoleId(),
            $this->sub_event->getStudentRoleId()
        ];
    }
}
