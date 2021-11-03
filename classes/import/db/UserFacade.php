<?php

namespace EventoImport\import\db;

use EventoImport\import\db\query\IliasUserQuerying;
use EventoImport\import\db\repository\EventMembershipRepository;
use EventoImport\import\db\repository\EventoUserRepository;
use ILIAS\DI\RBACServices;

class UserFacade
{
    private $user_query;
    private $evento_user_repo;
    private $event_membership_rep;
    private $rbac_services;

    public function __construct(IliasUserQuerying $user_query = null, EventoUserRepository $evento_user_repo = null, EventMembershipRepository $event_membership_rep = null, RBACServices $rbac_services)
    {
        global $DIC;
        
        $this->user_query = $user_query ?? new IliasUserQuerying($DIC->database());
        $this->evento_user_repo = $evento_user_repo ?? new EventoUserRepository($DIC->database());
        $this->event_membership_rep = $event_membership_rep ?? new EventMembershipRepository($DIC->database());
        $this->rbac_services = $rbac_services ?? $DIC->rbac();
    }

    public function fetchUserIdsByEmail($email) {
        return $this->user_query->fetchUserIdsByEmailAdresses($email);
    }

    public function fetchUserIdsByEventoId($evento_id) {
        return $this->user_query->fetchUserIdsByEventoId($evento_id);
    }

    public function fetchUserIdByLogin(string $login_name) {
        return \ilObjUser::getUserIdByLogin($login_name);
    }

    public function createNewIliasUserObject() : \ilObjUser
    {
        return new \ilObjUser();
    }

    public function getExistingIliasUserObject(int $user_id) : \ilObjUser
    {
        return new \ilObjUser($user_id);
    }

    public function eventoUserRepository() : EventoUserRepository
    {
        return $this->evento_user_repo;
    }

    public function rbacServices() : \ILIAS\DI\RBACServices
    {
        return $this->rbac_services;
    }

    public function fetchUserIdByMembership($evento_event_id, $employee)
    {
        $user_id = $this->evento_user_repo->getIliasUserIdByEventoId($employee['id']);

        if(!is_null($user_id) && $user_id > 0) {
            return $user_id;
        }

        $user_ids = $this->user_query->fetchUserIdsByEmailAdress($employee['email']);
        if(count($user_ids) == 1) {
            return $user_ids[1];
        }
    }

    public function assignUserToRole(int $role_id, int $user_id)
    {
        $this->rbac_services->admin()->assignUser($role_id, $user_id);
    }
}