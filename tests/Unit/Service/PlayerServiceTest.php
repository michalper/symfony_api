<?php

namespace App\Tests\Unit\Service;

use App\Entity\Player;
use App\Repository\PlayerRepositoryInterface;
use App\Service\PlayerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PlayerServiceTest extends TestCase
{
    public function testCreate(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Player::class));
        $em->expects($this->once())->method('flush');

        $service = new PlayerService($this->createStub(PlayerRepositoryInterface::class), $em);
        $player = $service->create('Alice');

        $this->assertSame('Alice', $player->getName());
    }

    public function testListReturnsPaginatedResults(): void
    {
        $player = new Player('Alice');

        $repo = $this->createStub(PlayerRepositoryInterface::class);
        $repo->method('findPaginated')->willReturn([$player]);
        $repo->method('countAll')->willReturn(1);

        $service = new PlayerService($repo, $this->createStub(EntityManagerInterface::class));
        $result = $service->list(1, 20);

        $this->assertCount(1, $result['items']);
        $this->assertSame(1, $result['total']);
        $this->assertSame('Alice', $result['items'][0]->getName());
    }

    public function testListCalculatesOffset(): void
    {
        $repo = $this->createMock(PlayerRepositoryInterface::class);
        $repo->expects($this->once())->method('findPaginated')->with(20, 10)->willReturn([]);
        $repo->method('countAll')->willReturn(25);

        $service = new PlayerService($repo, $this->createStub(EntityManagerInterface::class));
        $result = $service->list(3, 10);

        $this->assertSame(25, $result['total']);
    }
}
