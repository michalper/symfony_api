<?php

namespace App\Tests\Unit\Service;

use App\Entity\Game;
use App\Repository\GameLoveRepositoryInterface;
use App\Repository\GameRepositoryInterface;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class GameServiceTest extends TestCase
{
    public function testCreate(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Game::class));
        $em->expects($this->once())->method('flush');

        $service = new GameService(
            $this->createStub(GameRepositoryInterface::class),
            $this->createStub(GameLoveRepositoryInterface::class),
            $em,
        );
        $game = $service->create('Chess');

        $this->assertSame('Chess', $game->getTitle());
    }

    public function testListReturnsPaginatedResults(): void
    {
        $game = new Game('Chess');

        $repo = $this->createStub(GameRepositoryInterface::class);
        $repo->method('findPaginated')->willReturn([$game]);
        $repo->method('countAll')->willReturn(1);

        $service = new GameService(
            $repo,
            $this->createStub(GameLoveRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );
        $result = $service->list(1, 20);

        $this->assertCount(1, $result['items']);
        $this->assertSame(1, $result['total']);
    }

    public function testTopLoved(): void
    {
        $expected = [['gameId' => 1, 'title' => 'Chess', 'loveCount' => 5]];

        $loveRepo = $this->createStub(GameLoveRepositoryInterface::class);
        $loveRepo->method('findTopLovedGames')->willReturn($expected);

        $service = new GameService(
            $this->createStub(GameRepositoryInterface::class),
            $loveRepo,
            $this->createStub(EntityManagerInterface::class),
        );

        $this->assertSame($expected, $service->topLoved(10));
    }
}
