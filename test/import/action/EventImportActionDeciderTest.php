<?php declare(strict_type=1);

namespace EventoImport\import\action;

use PHPUnit\Framework\TestCase;
use EventoImport\import\data_management\EventManager;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use EventoImport\import\data_management\ilias_core_service\IliasEventObjectService;
use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\action\event\CreateSingleEvent;
use EventoImport\config\EventLocations;

class EventImportActionDeciderTest extends TestCase
{
    public function testDetermineImportAction_createSingleCourseEvent()
    {
        // Arrange
        $evento_id = 1234;
        $evento_event = new \MockEventoEvent(
            [
                \MockEventoEvent::JSON_ID => $evento_id,
                \MockEventoEvent::JSON_IS_CREATE_COURSE_FLAG => true,
                \MockEventoEvent::JSON_IS_GROUP_MEMBER_FLAG => false,
            ]
        );

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->expects($this->once())
                      ->method('searchIliasEventoEventByEventoEvent')
                      ->with($this->equalTo($evento_event))
                      ->willReturn(null);

        $create_action = $this->createMock(CreateSingleEvent::class);
        $factory = $this->createMock(EventActionFactory::class);
        $factory->expects($this->once())
                 ->method('createSingleEvent')
                 ->willReturn($create_action);
        $locations = $this->createMock(EventLocations::class);
        $locations->expects($this->once())
            ->method('getLocationRefIdForEventoEvent')
            ->willReturn(123);

        $sut = new EventImportActionDecider(
            $event_manager,
            $factory,
            $locations
        );

        // Act
        $returned_action = $sut->determineImportAction($evento_event);

        // Assert
        $this->assertEquals($create_action, $returned_action);
    }

    public function testDetermineImportAction_createEventInParentEvent()
    {
        // Arrange
        $evento_event = new \MockEventoEvent(
            [
                \MockEventoEvent::JSON_IS_CREATE_COURSE_FLAG => true,
                \MockEventoEvent::JSON_IS_GROUP_MEMBER_FLAG => true,
            ]
        );
    }

    public function testDetermineImportAction_createParentAndSubEvent()
    {
        $evento_event = new \MockEventoEvent(
            [
                \MockEventoEvent::JSON_IS_CREATE_COURSE_FLAG => true,
                \MockEventoEvent::JSON_IS_GROUP_MEMBER_FLAG => true,
            ]
        );
    }

    public function testDetermineImportAction_matchExistingCourse()
    {
        $evento_event = new \MockEventoEvent(
            [
                \MockEventoEvent::JSON_IS_CREATE_COURSE_FLAG => false
            ]
        );
    }
}
