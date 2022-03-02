<?php

namespace EventoImport\import\action_decider;

use PHPUnit\Framework\TestCase;
use EventoImport\import\service\IliasUserServices;
use EventoImport\import\action\user\UserActionFactory;
use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\action\user\CreateUser;
use EventoImport\import\manager\db\IliasEventoUserRepository;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\UserImportActionDecider;

class UserActionDeciderTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function testDetermineImportAction()
    {
        // Arrange
        $evento_id = 5;
        $login_name = 'login';
        $evento_user = new \MockEventoUser([EventoUser::JSON_ID => $evento_id, EventoUser::JSON_LOGIN_NAME => $login_name]);
        $event_repo = $this->createMock(IliasEventoUserRepository::class);
        $event_repo->expects($this->once())
                    ->method('getIliasUserIdByEventoId')
                    ->with($this->equalTo($evento_id))
                    ->willReturn(null);
        $user_facade = $this->createMock(IliasUserServices::class);
        $user_facade->expects($this->once())
                    ->method('eventoUserRepository')
                    ->willReturn($event_repo);
        $user_facade->expects($this->once())
                    ->method('fetchUserIdsByEventoId')
                    ->willReturn([]);
        $user_facade->expects($this->once())
                    ->method('fetchUserIdsByEmail')
                    ->willReturn([]);
        $user_facade->expects($this->once())
                    ->method('fetchUserIdByLogin')
                    ->willReturn(null);

        $action_factory = $this->createMock(UserActionFactory::class);
        $action_factory->expects($this->once())
                    ->method('buildCreateAction')
                    ->with($this->equalTo($evento_user))
                    ->willReturn($this->createMock(CreateUser::class));
        $action_decider = new UserImportActionDecider($user_facade, $action_factory);

        // Act
        $action = $action_decider->determineImportAction($evento_user);

        // Assert
        $this->assertInstanceOf(CreateUser::class, $action);
    }

    /**
     * @test
     * @small
     */
    public function testDetermineDeleteAction()
    {
    }
}
