<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GameController;
use App\Entity\Game;
use App\Service\GameService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GameControllerTest extends TestCase
{
    public function testListReturnsPaginatedGames(): void
    {
        $game1 = new Game('Chess');
        $game2 = new Game('Go');
        (new \ReflectionProperty($game1, 'id'))->setValue($game1, 1);
        (new \ReflectionProperty($game2, 'id'))->setValue($game2, 2);

        $service = $this->createStub(GameService::class);
        $service->method('list')->willReturn(['items' => [$game1, $game2], 'total' => 2]);

        $controller = new GameController($service, $this->createStub(ValidatorInterface::class));
        $response = $controller->list(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $data['data']);
        $this->assertSame('Chess', $data['data'][0]['title']);
        $this->assertSame('Go', $data['data'][1]['title']);
        $this->assertSame(2, $data['total']);
    }

    public function testListReturnsEmptyArray(): void
    {
        $service = $this->createStub(GameService::class);
        $service->method('list')->willReturn(['items' => [], 'total' => 0]);

        $controller = new GameController($service, $this->createStub(ValidatorInterface::class));
        $response = $controller->list(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $data['data']);
        $this->assertSame(0, $data['total']);
    }

    public function testCreateGame(): void
    {
        $game = new Game('Chess');
        (new \ReflectionProperty($game, 'id'))->setValue($game, 1);

        $service = $this->createStub(GameService::class);
        $service->method('create')->willReturn($game);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $controller = new GameController($service, $validator);
        $response = $controller->create(new Request(content: json_encode(['title' => 'Chess'])));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Chess', json_decode($response->getContent(), true)['title']);
    }

    public function testCreateGameInvalidJson(): void
    {
        $controller = new GameController(
            $this->createStub(GameService::class),
            $this->createStub(ValidatorInterface::class),
        );
        $response = $controller->create(new Request(content: 'not json'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid JSON', json_decode($response->getContent(), true)['error']);
    }

    public function testCreateGameValidationFails(): void
    {
        $violation = new ConstraintViolation('Title is required', '', [], '', 'title', '');
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $controller = new GameController($this->createStub(GameService::class), $validator);
        $response = $controller->create(new Request(content: json_encode([])));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Title is required', json_decode($response->getContent(), true)['error']);
    }

    public function testTopLovedGames(): void
    {
        $service = $this->createStub(GameService::class);
        $service->method('topLoved')->willReturn([
            ['gameId' => 1, 'title' => 'Chess', 'loveCount' => 5],
            ['gameId' => 2, 'title' => 'Go', 'loveCount' => 3],
        ]);

        $controller = new GameController($service, $this->createStub(ValidatorInterface::class));
        $response = $controller->top(new Request(query: ['limit' => '2']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $data);
        $this->assertSame(5, $data[0]['loves']);
        $this->assertSame('Go', $data[1]['title']);
    }

    public function testTopDefaultsToLimit10(): void
    {
        $service = $this->createMock(GameService::class);
        $service->expects($this->once())->method('topLoved')->with(10)->willReturn([]);

        $controller = new GameController($service, $this->createStub(ValidatorInterface::class));
        $response = $controller->top(new Request());

        $this->assertSame(200, $response->getStatusCode());
    }
}
