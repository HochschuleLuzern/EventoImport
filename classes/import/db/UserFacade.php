<?php

namespace EventoImport\import\db;

use EventoImport\import\db\query\IliasUserQuerying;
use EventoImport\import\db\repository\EventoUserRepository;

class UserFacade
{
    private IliasUserQuerying $user_query;
    private EventoUserRepository $evento_user_repo;

    public function __construct(IliasUserQuerying $user_query = null, EventoUserRepository $evento_user_repo = null)
    {
        global $DIC;
        
        $this->user_query = $user_query ?? new IliasUserQuerying($DIC->database());
        $this->evento_user_repo = $evento_user_repo ?? new EventoUserRepository($DIC->database());
    }

    public function getUserIdsByEmail($email) {
        return \ilObjUser::getUserIdsByEmail($email);
    }

    public function getUserIdsByEventoId($evento_id) {
        return $this->user_query->fetchUserIdsByEventoId($evento_id);
    }

    public function getUserIdByLogin(string $login_name) {
        return \ilObjUser::getUserIdByLogin($login_name);
    }

    public function EventoUserRepository() : EventoUserRepository
    {
        return $this->evento_user_repo;
    }
}