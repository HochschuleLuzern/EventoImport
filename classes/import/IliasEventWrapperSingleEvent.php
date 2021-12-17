<?php

namespace EventoImport\import;

use EventoImport\import\db\model\IliasEventoEvent;
use ILIAS\DI\RBACServices;

/**
 * Class IliasEventWrapperSingleEvent
 * @package EventoImport\import
 */
class IliasEventWrapperSingleEvent extends IliasEventWrapper
{
    /** @var IliasEventoEvent */
    private IliasEventoEvent $evento_event;

    /** @var \ilObjCourse  */
    private \ilObjCourse $ilias_event_object;

    /**
     * IliasEventWrapperSingleEvent constructor.
     * @param IliasEventoEvent  $event
     * @param \ilObjCourse      $ilias_event_object
     * @param RBACServices|null $rbac_services
     */
    public function __construct(IliasEventoEvent $event, \ilObjCourse $ilias_event_object, RBACServices $rbac_services = null)
    {
        parent::__construct($rbac_services);

        $this->evento_event = $event;
        $this->ilias_event_object = $ilias_event_object;
    }

    /**
     * @param int $user_id
     */
    public function addUserAsAdminToEvent(int $user_id) : void
    {
        $this->addUserToGivenRole($user_id, $this->evento_event->getAdminRoleId());
    }

    /**
     * @param int $user_id
     * @return void
     */
    public function addUserAsStudentToEvent(int $user_id) : void
    {
        $this->addUserToGivenRole($user_id, $this->evento_event->getStudentRoleId());
    }

    /**
     * @return IliasEventoEvent
     */
    public function getIliasEventoEventObj() : IliasEventoEvent
    {
        return $this->evento_event;
    }

    /**
     * @return array
     */
    public function getAllAdminRoles() : array
    {
        return [$this->evento_event->getAdminRoleId()];
    }

    /**
     * @return array
     */
    public function getAllMemberRoles() : array
    {
        return [$this->evento_event->getStudentRoleId()];
    }
}
