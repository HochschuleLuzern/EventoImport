<?php

class ilEventoImportImportEventsAndMemberships
{
    private $evento_importer;
    private \EventoImport\import\db\RepositoryFacade $repository_facade;
    private $user_facade;
    private $ilias_evento_event_repo;
    private $evento_event_matcher;
    private $logger;
    private $rbac;
    private $ilias_event_object_factory;
    private $event_action_factory;

    public function __construct(
        \EventoImport\communication\EventoEventImporter $evento_importer,
        \EventoImport\import\db\RepositoryFacade $repository_facade,
        \EventoImport\import\db\UserFacade $user_facade,
        \EventoImport\import\data_matching\EventoEventToIliasObjectMatcher $evento_event_matcher,
        \EventoImport\import\IliasEventObjectFactory $ilias_event_object_factory,
        ilEventoImportLogger $logger,
        \ILIAS\DI\RBACServices $rbac,
        \EventoImport\import\action\event\EventActionFactory $action_factory
    )
    {
        $this->evento_importer         = $evento_importer;
        $this->repository_facade       = $repository_facade;
        $this->user_facade             = $user_facade;
        $this->evento_event_matcher = $evento_event_matcher;
        $this->ilias_event_object_factory = $ilias_event_object_factory;
        $this->logger = $logger;
        $this->rbac = $rbac;
        $this->event_action_factory = $action_factory;
    }

    private function updateMembershipsOfCourse(\EventoImport\communication\api_models\EventoEvent $evento_event, \EventoImport\import\IliasEventWrapper $event_wrapper)
    {
        foreach($evento_event->getEmployees() as $employee) {

            if(isset($employee["id"]) && isset($employee["email"])) {
                $user_id = $this->user_facade->fetchUserIdByMembership($evento_event->getEventoId(), $employee);

                if(!is_null($user_id)) {
                    $event_wrapper->addUserAsAdminToEvent($user_id);
                } else {
                    $user_id = $this->user_facade->eventoUserRepository()->getIliasUserIdByEventoId($employee["id"]);
                    if(!is_null($user_id)) {
                        $event_wrapper->addUserAsAdminToEvent($user_id);
                    }
                }

            }
        }

        foreach($evento_event->getStudents() as $student) {

            if(isset($student["id"]) && isset($student["email"])) {
                $user_id = $this->user_facade->fetchUserIdByMembership($evento_event->getEventoId(), $student);

                if(!is_null($user_id)) {
                    $event_wrapper->addUserAsStudentToEvent($user_id);
                } else {

                    $user_id = $this->user_facade->eventoUserRepository()->getIliasUserIdByEventoId($student["id"]);
                    if(!is_null($user_id)) {
                        $event_wrapper->addUserAsStudentToEvent($user_id);
                    }
                }

            }
        }
    }

    private function handleChangedCreateCourseFlagAfterImport(\EventoImport\import\db\model\IliasEventoEvent $ilias_evento_event, \EventoImport\communication\api_models\EventoEvent $evento_event)
    {

    }

    private function handleNotMatchedEvent(\EventoImport\communication\api_models\EventoEvent $evento_event)
    {
        $sort_mode = \ilContainer::SORT_TITLE;
        $owner_user_id = 6;
        // Has create flag
        if ($evento_event->hasCreateCourseFlag()) {
            $destination = $this->repository_facade->departmentLocationRepository()->fetchRefIdForEventoObject($evento_event);

            if($destination === null) {
                throw new Exception('Location for Event not found');
            }

            // Is MultiGroup
            if($evento_event->hasGroupMemberFlag()) {
                $parent_event_crs_obj = $this->repository_facade->searchPossibleParentEventForEvent($evento_event);
                $obj_for_parent_already_existed = false;

                if(is_null($parent_event_crs_obj)) {
                    $parent_event_crs_obj = $this->ilias_event_object_factory->buildNewCourseObject($evento_event->getGroupName(), $evento_event->getDescription(), $owner_user_id, $destination, $sort_mode);
                } else {
                    $obj_for_parent_already_existed = true;
                }

                $event_sub_group = $this->ilias_event_object_factory->buildNewGroupObject($evento_event->getName(), $evento_event->getDescription(), $owner_user_id, $parent_event_crs_obj->getRefId(), $sort_mode);

                if($obj_for_parent_already_existed) {
                    $event_wrapper = $this->repository_facade->addNewEventToExistingMultiGroupEvent($evento_event, $parent_event_crs_obj, $event_sub_group);
                } else {
                    $event_wrapper = $this->repository_facade->addNewMultiEventCourseAndGroup($evento_event, $parent_event_crs_obj, $event_sub_group);
                }
            }
            // Is single Group
            else {
                $crs_object = $this->ilias_event_object_factory->buildNewCourseObject($evento_event->getName(), $evento_event->getDescription(), $owner_user_id, $destination, $sort_mode);

                $event_wrapper = $this->repository_facade->addNewSingleEventCourse($evento_event, $crs_object);
            }

            //$event_wrapper = $this->ilias_event_object_factory->buildNewIliasEventObject($evento_event, $destination);

            $this->updateMembershipsOfCourse($evento_event, $event_wrapper);

        }
        // Has no create flag
        else {
            $matched_course = $this->evento_event_matcher->searchExactlyOneMatchingCourseByTitle($evento_event);

            if (!is_null($matched_course)) {
                $event_wrapper = $this->ilias_event_object_factory->addExistingIliasObjectAsEventoEvent($evento_event, $matched_course);

                $this->updateMembershipsOfCourse($evento_event, $event_wrapper);
            }
        }
    }

    private function addExistingIliasObjectAsEventoEvent(\EventoImport\communication\api_models\EventoEvent $evento_event, ilContainer $matched_course)
    {

    }

    private function updateEvent(\EventoImport\import\db\model\IliasEventoEvent $ilias_evento_event, \EventoImport\communication\api_models\EventoEvent $evento_event)
    {
        // Edge-case
        if(!$ilias_evento_event->wasAutomaticallyCreated() && $evento_event->hasCreateCourseFlag()) {
            $this->handleChangedCreateCourseFlagAfterImport($ilias_evento_event, $evento_event);
        }
    }

    public function run() {
        $this->importEvents();
    }

    private function determineEventAction(\EventoImport\communication\api_models\EventoEvent $evento_event) : \EventoImport\import\action\EventoImportAction
    {
        $ilias_event = $this->repository_facade->getIliasEventByEventoIdOrReturnNull($evento_event->getEventoId());
        if(!is_null($ilias_event) && $ilias_event->getIliasEventoEventObj()->wasAutomaticallyCreated() == $evento_event->hasCreateCourseFlag()) {

            return $this->event_action_factory->updateExistingEvent($evento_event, $ilias_event);
        }

        // Has create flag
        if ($evento_event->hasCreateCourseFlag()) {
            $destination_ref_id = $this->repository_facade->departmentLocationRepository()->fetchRefIdForEventoObject($evento_event);

            if($destination_ref_id === null) {
                throw new Exception('Location for Event not found');
            }

            if(!$evento_event->hasGroupMemberFlag()) {
                // Is single Group
                return $this->event_action_factory->createSingleEvent($evento_event, $destination_ref_id);
            }

            // Is MultiGroup
            $parent_event = $this->repository_facade->searchPossibleParentEventForEvent($evento_event);
            if(!is_null($parent_event)) {
                // Parent event in multi group exists
                return $this->event_action_factory->createEventInParentEvent($evento_event, $parent_event);
            }

            // Parent event in multi group has also to be created
            return $this->event_action_factory->createEventWithParent($evento_event, $destination_ref_id);
        }
        // Has no create flag
        else {
            $matched_course = $this->evento_event_matcher->searchExactlyOneMatchingCourseByTitle($evento_event);

            if (!is_null($matched_course)) {
                return $this->event_action_factory->convertExistingIliasObjToEvent($evento_event, $matched_course);
            } else {
                return $this->event_action_factory->reportNonIliasEvent($evento_event);
            }

        }
    }

    private function importNextEventPage()
    {
        foreach($this->evento_importer->fetchNextDataSet() as $data_set) {
            try {
                $evento_event = new \EventoImport\communication\api_models\EventoEvent($data_set);

                $action = $this->determineEventAction($evento_event);
                $action->executeAction();
            } catch(\Exception $e) {
                $this->logger->logException('Importing Event', $e->getMessage());
            }
        }
    }

    private function importEvents()
    {
        do {
            try {
                $this->importNextEventPage();
            } catch(\Exception $e) {
                $this->logger->logException('Importing Event Page', $e->getMessage());
            }
        } while ($this->evento_importer->hasMoreData());
    }
}