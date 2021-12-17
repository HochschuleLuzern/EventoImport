<?php

namespace EventoImport\import;

use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use ILIAS\DI\RBACServices;

/**
 * Class IliasEventWrapperEventWithParent
 * @package EventoImport\import
 */
class IliasEventWrapperEventWithParent extends IliasEventWrapper
{
    /** @var IliasEventoParentEvent */
    private IliasEventoParentEvent $parent_event;

    /** @var \ilObjCourse */
    private \ilObjCourse $parent_event_obj;

    /** @var IliasEventoEvent */
    private IliasEventoEvent $sub_event;

    /** @var \ilObjGroup */
    private \ilObjGroup $sub_event_obj;

    /**
     * IliasEventWrapperEventWithParent constructor.
     * @param IliasEventoParentEvent $parent_event
     * @param \ilObjCourse           $parent_event_obj
     * @param IliasEventoEvent       $sub_event
     * @param \ilObjGroup            $sub_event_obj
     * @param RBACServices|null      $rbac_services
     */
    public function __construct(IliasEventoParentEvent $parent_event, \ilObjCourse $parent_event_obj, IliasEventoEvent $sub_event, \ilObjGroup $sub_event_obj, RBACServices $rbac_services = null)
    {
        parent::__construct($rbac_services);

        $this->parent_event = $parent_event;
        $this->parent_event_obj = $parent_event_obj;
        $this->sub_event = $sub_event;
        $this->sub_event_obj = $sub_event_obj;
    }

    /**
     * @param int $user_id
     */
    public function addUserAsAdminToEvent($user_id) : void
    {
        $this->addUserToGivenRole($user_id, $this->parent_event->getAdminRoleId());
        $this->addUserToGivenRole($user_id, $this->sub_event->getAdminRoleId());
    }

    /**
     * @param int $user_id
     */
    public function addUserAsStudentToEvent($user_id) : void
    {
        $this->addUserToGivenRole($user_id, $this->parent_event->getStudentRoleId());
        $this->addUserToGivenRole($user_id, $this->sub_event->getStudentRoleId());
    }

    /**
     * @return IliasEventoEvent
     */
    public function getIliasEventoEventObj() : IliasEventoEvent
    {
        return $this->sub_event;
    }

    /**
     * @return array
     */
    public function getAllAdminRoles() : array
    {
        return [
            $this->parent_event->getAdminRoleId(),
            $this->sub_event->getAdminRoleId()
        ];
    }

    /**
     * @return array
     */
    public function getAllMemberRoles() : array
    {
        return [
            $this->parent_event->getStudentRoleId(),
            $this->sub_event->getStudentRoleId()
        ];
    }
}
