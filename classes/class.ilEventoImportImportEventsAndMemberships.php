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

    public function __construct(
        \EventoImport\communication\EventoEventImporter $evento_importer,
        \EventoImport\import\db\RepositoryFacade $repository_facade,
        \EventoImport\import\db\UserFacade $user_facade,
        \EventoImport\import\data_matching\EventoEventToIliasObjectMatcher $evento_event_matcher,
        \EventoImport\import\IliasEventObjectFactory $ilias_event_object_factory,
        ilEventoImportLogger $logger,
        \ILIAS\DI\RBACServices $rbac
    )
    {
        $this->evento_importer         = $evento_importer;
        $this->repository_facade       = $repository_facade;
        $this->user_facade             = $user_facade;
        $this->evento_event_matcher = $evento_event_matcher;
        $this->ilias_event_object_factory = $ilias_event_object_factory;
        $this->logger = $logger;
        $this->rbac = $rbac;
    }

    private function updateMembershipsOfCourse(\EventoImport\communication\api_models\EventoEvent $evento_event, \EventoImport\import\IliasEventWrapper $event_wrapper)
    {
        foreach($evento_event->getEmployees() as $employee) {

            if(isset($employee["Id"]) && isset($employee["Email"])) {
                $user_id = $this->user_facade->fetchUserIdByMembership($evento_event->getEventoId(), $employee);

                if(!is_null($user_id)) {
                    $event_wrapper->addUserAsAdminToEvent($user_id);
                }
            }
        }
    }

    private function handleChangedCreateCourseFlagAfterImport(\EventoImport\import\db\model\IliasEventoEvent $ilias_evento_event, \EventoImport\communication\api_models\EventoEvent $evento_event)
    {

    }

    private function handleNotExistingEvent(\EventoImport\communication\api_models\EventoEvent $evento_event)
    {
        if ($evento_event->hasCreateCourseFlag()) {
            $destination = $this->repository_facade->departmentLocationRepository()->fetchRefIdForEventoObject($evento_event);

            if($destination === null) {
                throw new Exception('Location for Event not found');
            }
            $event_wrapper = $this->ilias_event_object_factory->buildNewIliasEventObject($evento_event, $destination);

            $this->updateMembershipsOfCourse($evento_event, $event_wrapper);

        } else {
            $matched_course = $this->evento_event_matcher->searchExactlyOneMatchingCourseByTitle($evento_event);

            if (!is_null($matched_course)) {
                $event_wrapper = $this->addExistingIliasObjectAsEventoEvent($evento_event, $matched_course);

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

    private function importEvents()
    {
        do {
            try {
                foreach($this->evento_importer->fetchNextDataSet() as $data_set) {
                    try {
                        $evento_event = new \EventoImport\communication\api_models\EventoEvent($data_set);
                        $ilias_evento_event = $this->repository_facade->iliasEventoEventRepository()->getEventByEventoId($evento_event->getEventoId());

                        if(is_null($ilias_evento_event)) {
                            $this->handleNotExistingEvent($evento_event);
                        } else {
                            $this->updateEvent($ilias_evento_event, $evento_event);
                        }

                    } catch(\Exception $e) {

                    }
                }
            } catch(\Exception $e) {

            }
        } while ($this->evento_importer->hasMoreData());
    }
}