<?php

namespace App\Tests\Unit\Service;

use App\Entity\Game;
use App\Entity\GameLove;
use App\Entity\Player;
use App\Exception\GameAlreadyLovedException;
use App\Exception\GameNotFoundException;
use App\Exception\LoveNotFoundException;
use App\Exception\PlayerNotFoundException;
use App\Repository\GameLoveRepositoryInterface;
use App\Repository\GameRepositoryInterface;
use App\Repository\PlayerRepositoryInterface;
use App\Service\GameLoveService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class GameLoveServiceTest extends TestCase
{
    public function testLove(): void
    {
        $player = $this->createPlayer(1, 'Alice');
        $game = $this->createGame(1, 'Chess');

        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn($player);

        $gameRepo = $this->createStub(GameRepositoryInterface::class);
        $gameRepo->method('findById')->willReturn($game);

        $loveRepo = $this->createStub(GameLoveRepositoryInterface::class);
        $loveRepo->method('findOneByPlayerAndGame')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(GameLove::class));
        $em->expects($this->once())->method('flush');

        $service = new GameLoveService($playerRepo, $gameRepo, $loveRepo, $em);
        $love = $service->love(1, 1);

        $this->assertSame($player, $love->getPlayer());
        $this->assertSame($game, $love->getGame());
    }

    public function testLovePlayerNotFound(): void
    {
        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn(null);

        $service = new GameLoveService(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $this->createStub(GameLoveRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $this->expectException(PlayerNotFoundException::class);
        $service->love(999, 1);
    }

    public function testLoveGameNotFound(): void
    {
        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn($this->createPlayer(1, 'Alice'));

        $gameRepo = $this->createStub(GameRepositoryInterface::class);
        $gameRepo->method('findById')->willReturn(null);

        $service = new GameLoveService(
            $playerRepo, $gameRepo,
            $this->createStub(GameLoveRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $this->expectException(GameNotFoundException::class);
        $service->love(1, 999);
    }

    public function testLoveDuplicate(): void
    {
        $player = $this->createPlayer(1, 'Alice');
        $game = $this->createGame(1, 'Chess');

        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn($player);

        $gameRepo = $this->createStub(GameRepositoryInterface::class);
        $gameRepo->method('findById')->willReturn($game);

        $loveRepo = $this->createStub(GameLoveRepositoryInterface::class);
        $loveRepo->method('findOneByPlayerAndGame')->willReturn(new GameLove($player, $game));

        $service = new GameLoveService(
            $playerRepo, $gameRepo, $loveRepo,
            $this->createStub(EntityManagerInterface::class),
        );

        $this->expectException(GameAlreadyLovedException::class);
        $service->love(1, 1);
    }

    public function testUnlove(): void
    {
        $player = $this->createPlayer(1, 'Alice');
        $game = $this->createGame(1, 'Chess');
        $love = new GameLove($player, $game);

        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn($player);

        $loveRepo = $this->createStub(GameLoveRepositoryInterface::class);
        $loveRepo->method('findOneByPlayerAndGame')->willReturn($love);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($love);
        $em->expects($this->once())->method('flush');

        $service = new GameLoveService(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $loveRepo, $em,
        );

        $service->unlove(1, 1);
    }

    public function testUnlovePlayerNotFound(): void
    {
        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn(null);

        $service = new GameLoveService(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $this->createStub(GameLoveRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $this->expectException(PlayerNotFoundException::class);
        $service->unlove(999, 1);
    }

    public function testUnloveLoveNotFound(): void
    {
        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn($this->createPlayer(1, 'Alice'));

        $loveRepo = $this->createStub(GameLoveRepositoryInterface::class);
        $loveRepo->method('findOneByPlayerAndGame')->willReturn(null);

        $service = new GameLoveService(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $loveRepo,
            $this->createStub(EntityManagerInterface::class),
        );

        $this->expectException(LoveNotFoundException::class);
        $service->unlove(1, 1);
    }

    public function testListByPlayer(): void
    {
        $player = $this->createPlayer(1, 'Alice');
        $game = $this->createGame(1, 'Chess');

        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn($player);

        $loveRepo = $this->createStub(GameLoveRepositoryInterface::class);
        $loveRepo->method('findByPlayer')->willReturn([new GameLove($player, $game)]);

        $service = new GameLoveService(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $loveRepo,
            $this->createStub(EntityManagerInterface::class),
        );

        $loves = $service->listByPlayer(1);
        $this->assertCount(1, $loves);
        $this->assertSame('Chess', $loves[0]->getGame()->getTitle());
    }

    public function testListByPlayerNotFound(): void
    {
        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn(null);

        $service = new GameLoveService(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $this->createStub(GameLoveRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $this->expectException(PlayerNotFoundException::class);
        $service->listByPlayer(999);
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
