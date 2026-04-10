<?php

namespace App\Tests\Unit\Controller;

use App\Controller\PlayerController;
use App\Entity\Player;
use App\Service\PlayerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PlayerControllerTest extends TestCase
{
    public function testListReturnsPaginatedPlayers(): void
    {
        $player = new Player('Alice');
        (new \ReflectionProperty($player, 'id'))->setValue($player, 1);

        $service = $this->createStub(PlayerService::class);
        $service->method('list')->willReturn(['items' => [$player], 'total' => 1]);

        $controller = new PlayerController($service, $this->createStub(ValidatorInterface::class));
        $response = $controller->list(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $data['data']);
        $this->assertSame('Alice', $data['data'][0]['name']);
        $this->assertSame(1, $data['total']);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['limit']);
    }

    public function testListRespectsPageAndLimit(): void
    {
        $service = $this->createMock(PlayerService::class);
        $service->expects($this->once())->method('list')->with(2, 5)->willReturn(['items' => [], 'total' => 10]);

        $controller = new PlayerController($service, $this->createStub(ValidatorInterface::class));
        $response = $controller->list(new Request(query: ['page' => '2', 'limit' => '5']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(2, $data['page']);
        $this->assertSame(5, $data['limit']);
        $this->assertSame(10, $data['total']);
    }

    public function testCreatePlayer(): void
    {
        $player = new Player('Alice');
        (new \ReflectionProperty($player, 'id'))->setValue($player, 1);

        $service = $this->createStub(PlayerService::class);
        $service->method('create')->willReturn($player);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $controller = new PlayerController($service, $validator);
        $response = $controller->create(new Request(content: json_encode(['name' => 'Alice'])));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Alice', json_decode($response->getContent(), true)['name']);
    }

    public function testCreatePlayerInvalidJson(): void
    {
        $controller = new PlayerController(
            $this->createStub(PlayerService::class),
            $this->createStub(ValidatorInterface::class),
        );
        $response = $controller->create(new Request(content: '{invalid'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid JSON', json_decode($response->getContent(), true)['error']);
    }

    public function testCreatePlayerValidationFails(): void
    {
        $violation = new ConstraintViolation('Name is required', '', [], '', 'name', '');
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $controller = new PlayerController($this->createStub(PlayerService::class), $validator);
        $response = $controller->create(new Request(content: json_encode([])));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Name is required', json_decode($response->getContent(), true)['error']);
    }
}
