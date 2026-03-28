<?php

namespace App\Tests\Unit\Controller;

use App\Controller\PlayerController;
use App\Entity\Player;
use App\Repository\PlayerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PlayerControllerTest extends TestCase
{
    public function testListReturnsAllPlayers(): void
    {
        $player = new Player('Alice');
        (new \ReflectionProperty($player, 'id'))->setValue($player, 1);

        $repo = $this->createStub(PlayerRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$player]);

        $controller = new PlayerController($repo, $this->createStub(EntityManagerInterface::class));
        $response = $controller->list();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $data);
        $this->assertSame('Alice', $data[0]['name']);
    }

    public function testCreatePlayer(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Player::class));
        $em->expects($this->once())->method('flush');

        $controller = new PlayerController($this->createStub(PlayerRepositoryInterface::class), $em);
        $response = $controller->create(new Request(content: json_encode(['name' => 'Alice'])));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Alice', json_decode($response->getContent(), true)['name']);
    }

    public function testCreatePlayerRequiresName(): void
    {
        $controller = new PlayerController($this->createStub(PlayerRepositoryInterface::class), $this->createStub(EntityManagerInterface::class));
        $response = $controller->create(new Request(content: json_encode([])));

        $this->assertSame(400, $response->getStatusCode());
    }
}
