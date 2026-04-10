<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GameLoveController;
use App\Entity\Game;
use App\Entity\GameLove;
use App\Entity\Player;
use App\Exception\GameAlreadyLovedException;
use App\Exception\GameNotFoundException;
use App\Exception\LoveNotFoundException;
use App\Exception\PlayerNotFoundException;
use App\Service\GameLoveService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GameLoveControllerTest extends TestCase
{
    public function testLoveGame(): void
    {
        $player = $this->createPlayer(1, 'Alice');
        $game = $this->createGame(1, 'Chess');
        $love = new GameLove($player, $game);

        $service = $this->createStub(GameLoveService::class);
        $service->method('love')->willReturn($love);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $controller = new GameLoveController($service, $validator);
        $response = $controller->love(1, new Request(content: json_encode(['gameId' => 1])));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, $data['playerId']);
        $this->assertSame(1, $data['gameId']);
    }

    public function testLoveInvalidJson(): void
    {
        $controller = new GameLoveController(
            $this->createStub(GameLoveService::class),
            $this->createStub(ValidatorInterface::class),
        );
        $response = $controller->love(1, new Request(content: '{bad'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid JSON', json_decode($response->getContent(), true)['error']);
    }

    public function testLovePlayerNotFound(): void
    {
        $service = $this->createStub(GameLoveService::class);
        $service->method('love')->willThrowException(new PlayerNotFoundException(999));

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $controller = new GameLoveController($service, $validator);
        $response = $controller->love(999, new Request(content: json_encode(['gameId' => 1])));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Player not found', json_decode($response->getContent(), true)['error']);
    }

    public function testLoveGameNotFound(): void
    {
        $service = $this->createStub(GameLoveService::class);
        $service->method('love')->willThrowException(new GameNotFoundException(999));

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $controller = new GameLoveController($service, $validator);
        $response = $controller->love(1, new Request(content: json_encode(['gameId' => 999])));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Game not found', json_decode($response->getContent(), true)['error']);
    }

    public function testLoveDuplicate(): void
    {
        $service = $this->createStub(GameLoveService::class);
        $service->method('love')->willThrowException(new GameAlreadyLovedException(1, 1));

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $controller = new GameLoveController($service, $validator);
        $response = $controller->love(1, new Request(content: json_encode(['gameId' => 1])));

        $this->assertSame(409, $response->getStatusCode());
    }

    public function testLoveMissingGameId(): void
    {
        $violation = new ConstraintViolation('gameId is required', '', [], '', 'gameId', null);
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $controller = new GameLoveController($this->createStub(GameLoveService::class), $validator);
        $response = $controller->love(1, new Request(content: json_encode([])));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUnloveGame(): void
    {
        $service = $this->createMock(GameLoveService::class);
        $service->expects($this->once())->method('unlove')->with(1, 1);

        $controller = new GameLoveController($service, $this->createStub(ValidatorInterface::class));

        $this->assertSame(204, $controller->unlove(1, 1)->getStatusCode());
    }

    public function testUnlovePlayerNotFound(): void
    {
        $service = $this->createStub(GameLoveService::class);
        $service->method('unlove')->willThrowException(new PlayerNotFoundException(999));

        $controller = new GameLoveController($service, $this->createStub(ValidatorInterface::class));
        $response = $controller->unlove(999, 1);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Player not found', json_decode($response->getContent(), true)['error']);
    }

    public function testUnloveLoveNotFound(): void
    {
        $service = $this->createStub(GameLoveService::class);
        $service->method('unlove')->willThrowException(new LoveNotFoundException(1, 1));

        $controller = new GameLoveController($service, $this->createStub(ValidatorInterface::class));

        $this->assertSame(404, $controller->unlove(1, 1)->getStatusCode());
    }

    public function testListPlayerLoves(): void
    {
        $player = $this->createPlayer(1, 'Alice');
        $game = $this->createGame(1, 'Chess');

        $service = $this->createStub(GameLoveService::class);
        $service->method('listByPlayer')->willReturn([new GameLove($player, $game)]);

        $controller = new GameLoveController($service, $this->createStub(ValidatorInterface::class));
        $response = $controller->list(1);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $data);
        $this->assertSame('Chess', $data[0]['title']);
    }

    public function testListPlayerNotFound(): void
    {
        $service = $this->createStub(GameLoveService::class);
        $service->method('listByPlayer')->willThrowException(new PlayerNotFoundException(999));

        $controller = new GameLoveController($service, $this->createStub(ValidatorInterface::class));

        $this->assertSame(404, $controller->list(999)->getStatusCode());
    }

    private function createPlayer(int $id, string $name): Player
    {
        $player = new Player($name);
        (new \ReflectionProperty($player, 'id'))->setValue($player, $id);
        return $player;
    }

    private function createGame(int $id, string $title): Game
    {
        $game = new Game($title);
        (new \ReflectionProperty($game, 'id'))->setValue($game, $id);
        return $game;
    }
}
