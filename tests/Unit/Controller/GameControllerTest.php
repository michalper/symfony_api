<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GameController;
use App\Entity\Game;
use App\Repository\GameLoveRepositoryInterface;
use App\Repository\GameRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GameControllerTest extends TestCase
{
    public function testListReturnsAllGames(): void
    {
        $game1 = new Game('Chess');
        $game2 = new Game('Go');
        $this->setId($game1, 1);
        $this->setId($game2, 2);

        $repo = $this->createStub(GameRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$game1, $game2]);

        $controller = new GameController($repo, $this->createStub(GameLoveRepositoryInterface::class), $this->createStub(EntityManagerInterface::class));
        $response = $controller->list();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $data);
        $this->assertSame('Chess', $data[0]['title']);
        $this->assertSame('Go', $data[1]['title']);
    }

    public function testListReturnsEmptyArray(): void
    {
        $repo = $this->createStub(GameRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $controller = new GameController($repo, $this->createStub(GameLoveRepositoryInterface::class), $this->createStub(EntityManagerInterface::class));
        $response = $controller->list();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], json_decode($response->getContent(), true));
    }

    public function testCreateGame(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Game::class));
        $em->expects($this->once())->method('flush');

        $controller = new GameController($this->createStub(GameRepositoryInterface::class), $this->createStub(GameLoveRepositoryInterface::class), $em);
        $response = $controller->create(new Request(content: json_encode(['title' => 'Chess'])));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Chess', json_decode($response->getContent(), true)['title']);
    }

    public function testCreateGameRequiresTitle(): void
    {
        $controller = new GameController($this->createStub(GameRepositoryInterface::class), $this->createStub(GameLoveRepositoryInterface::class), $this->createStub(EntityManagerInterface::class));
        $response = $controller->create(new Request(content: json_encode([])));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testTopLovedGames(): void
    {
        $repo = $this->createStub(GameLoveRepositoryInterface::class);
        $repo->method('findTopLovedGames')->willReturn([
            ['gameId' => 1, 'title' => 'Chess', 'loveCount' => 5],
            ['gameId' => 2, 'title' => 'Go', 'loveCount' => 3],
        ]);

        $controller = new GameController($this->createStub(GameRepositoryInterface::class), $repo, $this->createStub(EntityManagerInterface::class));
        $response = $controller->top(new Request(query: ['limit' => '2']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $data);
        $this->assertSame(5, $data[0]['loves']);
        $this->assertSame('Go', $data[1]['title']);
    }

    public function testTopDefaultsToLimit10(): void
    {
        $repo = $this->createMock(GameLoveRepositoryInterface::class);
        $repo->expects($this->once())->method('findTopLovedGames')->with(10)->willReturn([]);

        $controller = new GameController($this->createStub(GameRepositoryInterface::class), $repo, $this->createStub(EntityManagerInterface::class));
        $response = $controller->top(new Request());

        $this->assertSame(200, $response->getStatusCode());
    }

    private function setId(object $entity, int $id): void
    {
        (new \ReflectionProperty($entity, 'id'))->setValue($entity, $id);
    }
}
