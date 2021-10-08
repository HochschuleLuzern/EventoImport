<?php

class ilEventoImportImportEventsAndMemberships
{
    private $evento_importer;
    private $ilias_evento_event_repo;
    private $evento_event_matcher;
    private $logger;
    private $rbac;
    private $ilias_event_object_factory;

    public function __construct(
        \EventoImport\communication\EventoEventImporter $evento_importer,
        \EventoImport\import\db_repository\IliasEventoEventsRepository $ilias_evento_event_repo,
        \EventoImport\import\EventoEventToIliasObjectMatcher $evento_event_matcher,
        \EventoImport\import\IliasEventObjectFactory $ilias_event_object_factory,
        ilEventoImportLogger $logger,
        \ILIAS\DI\RBACServices $rbac
    )
    {
        $this->evento_importer         = $evento_importer;
        $this->ilias_evento_event_repo = $ilias_evento_event_repo;
        $this->evento_event_matcher = $evento_event_matcher;
        $this->ilias_event_object_factory = $ilias_event_object_factory;
        $this->logger = $logger;
        $this->rbac = $rbac;
    }

    private function handleChangedCreateCourseFlagAfterImport(\EventoImport\import\data_models\IliasEventoEventCombination $ilias_evento_event, \EventoImport\import\data_models\EventoEvent $evento_event)
    {

    }

    private function handleNotExistingEvent(\EventoImport\import\data_models\EventoEvent $evento_event)
    {
        if ($evento_event->hasCreateCourseFlag()) {
            $this->createNewCourse($evento_event);
        } else {
            $matched_course = $this->evento_event_matcher->searchExactlyOneMatchingCourseByTitle($evento_event);

            if (!is_null($matched_course)) {
                $this->addExistingIliasObjectAsEventoEvent($evento_event, $matched_course);
            }
        }
    }

    private function createNewCourse(\EventoImport\import\data_models\EventoEvent $evento_event)
    {
        $this->ilias_event_object_factory->buildNewIliasEventObject($evento_event, $destination);
    }

    private function addExistingIliasObjectAsEventoEvent(\EventoImport\import\data_models\EventoEvent $evento_event, ilContainer $matched_course)
    {

    }

    private function updateEvent(\EventoImport\import\data_models\IliasEventoEventCombination $ilias_evento_event, \EventoImport\import\data_models\EventoEvent $evento_event)
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
                        $evento_event = new \EventoImport\import\data_models\EventoEvent($data_set);
                        $ilias_evento_event = $this->ilias_evento_event_repo->getEventByEventoId($evento_event->getEventoId());

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