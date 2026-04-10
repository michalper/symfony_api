<?php

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameLoveApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function post(string $uri, array $data): void
    {
        $this->client->request('POST', $uri, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    // --- Game CRUD ---

    public function testCreateGame(): void
    {
        $this->post('/api/games', ['title' => 'Chess']);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('Chess', $this->json()['title']);
    }

    public function testCreateGameRequiresTitle(): void
    {
        $this->post('/api/games', []);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateGameRejectsWhitespaceOnlyTitle(): void
    {
        $this->post('/api/games', ['title' => '   ']);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateGameInvalidJson(): void
    {
        $this->client->request('POST', '/api/games', [], [], ['CONTENT_TYPE' => 'application/json'], '{invalid');

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('Invalid JSON', $this->json()['error']);
    }

    public function testListGames(): void
    {
        $this->post('/api/games', ['title' => 'Chess']);
        $this->post('/api/games', ['title' => 'Go']);

        $this->client->request('GET', '/api/games');

        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertCount(2, $data['data']);
        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['page']);
    }

    public function testListGamesPagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/api/games', ['title' => "Game $i"]);
        }

        $this->client->request('GET', '/api/games?page=2&limit=2');

        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertCount(2, $data['data']);
        $this->assertSame(5, $data['total']);
        $this->assertSame(2, $data['page']);
        $this->assertSame(2, $data['limit']);
    }

    // --- Player CRUD ---

    public function testCreatePlayer(): void
    {
        $this->post('/api/players', ['name' => 'Alice']);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('Alice', $this->json()['name']);
    }

    public function testCreatePlayerRejectsWhitespaceOnlyName(): void
    {
        $this->post('/api/players', ['name' => '   ']);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testListPlayersPagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/api/players', ['name' => "Player $i"]);
        }

        $this->client->request('GET', '/api/players?page=1&limit=3');

        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertCount(3, $data['data']);
        $this->assertSame(5, $data['total']);
    }

    // --- Love / Unlove ---

    public function testLoveGame(): void
    {
        $this->post('/api/games', ['title' => 'Chess']);
        $gameId = $this->json()['id'];
        $this->post('/api/players', ['name' => 'Alice']);
        $playerId = $this->json()['id'];

        $this->post("/api/players/{$playerId}/loves", ['gameId' => $gameId]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($gameId, $this->json()['gameId']);
        $this->assertSame($playerId, $this->json()['playerId']);
    }

    public function testCannotLoveSameGameTwice(): void
    {
        $this->post('/api/games', ['title' => 'Chess']);
        $gameId = $this->json()['id'];
        $this->post('/api/players', ['name' => 'Alice']);
        $playerId = $this->json()['id'];

        $this->post("/api/players/{$playerId}/loves", ['gameId' => $gameId]);
        $this->post("/api/players/{$playerId}/loves", ['gameId' => $gameId]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testUnloveGame(): void
    {
        $this->post('/api/games', ['title' => 'Chess']);
        $gameId = $this->json()['id'];
        $this->post('/api/players', ['name' => 'Alice']);
        $playerId = $this->json()['id'];

        $this->post("/api/players/{$playerId}/loves", ['gameId' => $gameId]);
        $this->client->request('DELETE', "/api/players/{$playerId}/loves/{$gameId}");

        $this->assertResponseStatusCodeSame(204);

        $this->client->request('GET', "/api/players/{$playerId}/loves");
        $this->assertCount(0, $this->json());
    }

    public function testUnloveNonExistentPlayerReturnsPlayerNotFound(): void
    {
        $this->client->request('DELETE', '/api/players/999/loves/1');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('Player not found', $this->json()['error']);
    }

    public function testListPlayerLoves(): void
    {
        $this->post('/api/games', ['title' => 'Chess']);
        $game1 = $this->json()['id'];
        $this->post('/api/games', ['title' => 'Go']);
        $game2 = $this->json()['id'];
        $this->post('/api/players', ['name' => 'Alice']);
        $playerId = $this->json()['id'];

        $this->post("/api/players/{$playerId}/loves", ['gameId' => $game1]);
        $this->post("/api/players/{$playerId}/loves", ['gameId' => $game2]);

        $this->client->request('GET', "/api/players/{$playerId}/loves");

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $this->json());
    }

    public function testTopLovedGames(): void
    {
        $this->post('/api/games', ['title' => 'Chess']);
        $game1 = $this->json()['id'];
        $this->post('/api/games', ['title' => 'Go']);
        $game2 = $this->json()['id'];
        $this->post('/api/games', ['title' => 'Poker']);
        $game3 = $this->json()['id'];

        $this->post('/api/players', ['name' => 'Alice']);
        $player1 = $this->json()['id'];
        $this->post('/api/players', ['name' => 'Bob']);
        $player2 = $this->json()['id'];
        $this->post('/api/players', ['name' => 'Charlie']);
        $player3 = $this->json()['id'];

        // Chess: 3 loves, Go: 2, Poker: 1
        $this->post("/api/players/{$player1}/loves", ['gameId' => $game1]);
        $this->post("/api/players/{$player2}/loves", ['gameId' => $game1]);
        $this->post("/api/players/{$player3}/loves", ['gameId' => $game1]);
        $this->post("/api/players/{$player1}/loves", ['gameId' => $game2]);
        $this->post("/api/players/{$player2}/loves", ['gameId' => $game2]);
        $this->post("/api/players/{$player1}/loves", ['gameId' => $game3]);

        $this->client->request('GET', '/api/games/top?limit=2');

        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertCount(2, $data);
        $this->assertSame('Chess', $data[0]['title']);
        $this->assertSame(3, $data[0]['loves']);
        $this->assertSame('Go', $data[1]['title']);
        $this->assertSame(2, $data[1]['loves']);
    }

    public function testLoveNonExistentPlayer(): void
    {
        $this->post('/api/games', ['title' => 'Chess']);
        $gameId = $this->json()['id'];

        $this->post('/api/players/999/loves', ['gameId' => $gameId]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testLoveNonExistentGame(): void
    {
        $this->post('/api/players', ['name' => 'Alice']);
        $playerId = $this->json()['id'];

        $this->post("/api/players/{$playerId}/loves", ['gameId' => 999]);

        $this->assertResponseStatusCodeSame(404);
    }
}
