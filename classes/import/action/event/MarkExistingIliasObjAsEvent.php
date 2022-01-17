<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\MembershipManager;

/**
 * Class MarkExistingIliasObjAsEvent
 * @package EventoImport\import\action\event
 */
class MarkExistingIliasObjAsEvent implements EventAction
{
    private EventoEvent $evento_event;
    private \ilContainer $ilias_object;
    private RepositoryFacade $repository_facade;
    private MembershipManager $membership_manager;
    private \ilEventoImportLogger $logger;
    private int $log_code;

    /**
     * MarkExistingIliasObjAsEvent constructor.
     * @param EventoEvent                                        $evento_event
     * @param \ilContainer                                       $ilias_object
     * @param IliasEventObjectFactory                            $event_object_factory
     * @param \EventoImport\import\settings\DefaultEventSettings $event_settings
     * @param RepositoryFacade                                   $repository_facade
     * @param UserFacade                                         $user_facade
     * @param MembershipManager                                  $membership_manager
     * @param \ilEventoImportLogger                              $logger
     * @param \ILIAS\DI\RBACServices                             $rbac_services
     */
    public function __construct(EventoEvent $evento_event, \ilContainer $ilias_object, RepositoryFacade $repository_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger)
    {
        $this->evento_event = $evento_event;
        $this->ilias_object = $ilias_object;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = \ilEventoImportLogger::CREVENTO_MA_EXISTING_ILIAS_COURSE_AS_EVENT_MARKED;
    }

    public function executeAction() : void
    {
        $ilias_event = $this->repository_facade->addNewIliasEvent($this->evento_event, $this->ilias_object);

        $this->membership_manager->syncMemberships($this->evento_event, $ilias_event);
        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $this->ilias_object->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}
