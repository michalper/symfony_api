<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GameLoveController;
use App\Entity\Game;
use App\Entity\GameLove;
use App\Entity\Player;
use App\Repository\GameLoveRepositoryInterface;
use App\Repository\GameRepositoryInterface;
use App\Repository\PlayerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GameLoveControllerTest extends TestCase
{
    public function testLoveGame(): void
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

        $controller = new GameLoveController($playerRepo, $gameRepo, $loveRepo, $em);
        $response = $controller->love(1, new Request(content: json_encode(['gameId' => 1])));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, $data['playerId']);
        $this->assertSame(1, $data['gameId']);
    }

    public function testLovePlayerNotFound(): void
    {
        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn(null);

        $controller = new GameLoveController(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $this->createStub(GameLoveRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $response = $controller->love(999, new Request(content: json_encode(['gameId' => 1])));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testLoveGameNotFound(): void
    {
        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn($this->createPlayer(1, 'Alice'));

        $gameRepo = $this->createStub(GameRepositoryInterface::class);
        $gameRepo->method('findById')->willReturn(null);

        $controller = new GameLoveController(
            $playerRepo, $gameRepo,
            $this->createStub(GameLoveRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $response = $controller->love(1, new Request(content: json_encode(['gameId' => 999])));

        $this->assertSame(404, $response->getStatusCode());
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

        $controller = new GameLoveController($playerRepo, $gameRepo, $loveRepo, $this->createStub(EntityManagerInterface::class));
        $response = $controller->love(1, new Request(content: json_encode(['gameId' => 1])));

        $this->assertSame(409, $response->getStatusCode());
    }

    public function testLoveMissingGameId(): void
    {
        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn($this->createPlayer(1, 'Alice'));

        $controller = new GameLoveController(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $this->createStub(GameLoveRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $response = $controller->love(1, new Request(content: json_encode([])));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUnloveGame(): void
    {
        $love = new GameLove($this->createPlayer(1, 'Alice'), $this->createGame(1, 'Chess'));

        $loveRepo = $this->createStub(GameLoveRepositoryInterface::class);
        $loveRepo->method('findOneByPlayerAndGame')->willReturn($love);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($love);
        $em->expects($this->once())->method('flush');

        $controller = new GameLoveController(
            $this->createStub(PlayerRepositoryInterface::class),
            $this->createStub(GameRepositoryInterface::class),
            $loveRepo, $em,
        );

        $this->assertSame(204, $controller->unlove(1, 1)->getStatusCode());
    }

    public function testUnloveNotFound(): void
    {
        $loveRepo = $this->createStub(GameLoveRepositoryInterface::class);
        $loveRepo->method('findOneByPlayerAndGame')->willReturn(null);

        $controller = new GameLoveController(
            $this->createStub(PlayerRepositoryInterface::class),
            $this->createStub(GameRepositoryInterface::class),
            $loveRepo,
            $this->createStub(EntityManagerInterface::class),
        );

        $this->assertSame(404, $controller->unlove(1, 1)->getStatusCode());
    }

    public function testListPlayerLoves(): void
    {
        $player = $this->createPlayer(1, 'Alice');
        $game = $this->createGame(1, 'Chess');

        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn($player);

        $loveRepo = $this->createStub(GameLoveRepositoryInterface::class);
        $loveRepo->method('findByPlayer')->willReturn([new GameLove($player, $game)]);

        $controller = new GameLoveController(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $loveRepo,
            $this->createStub(EntityManagerInterface::class),
        );

        $response = $controller->list(1);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $data);
        $this->assertSame('Chess', $data[0]['title']);
    }

    public function testListPlayerNotFound(): void
    {
        $playerRepo = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepo->method('findById')->willReturn(null);

        $controller = new GameLoveController(
            $playerRepo,
            $this->createStub(GameRepositoryInterface::class),
            $this->createStub(GameLoveRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

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
