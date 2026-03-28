<?php

namespace App\Controller;

use App\Entity\Game;
use App\Repository\GameLoveRepositoryInterface;
use App\Repository\GameRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/games')]
readonly class GameController
{
    public function __construct(
        private GameRepositoryInterface     $gameRepository,
        private GameLoveRepositoryInterface $gameLoveRepository,
        private EntityManagerInterface      $em,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $games = $this->gameRepository->findAll();

        return new JsonResponse(array_map(fn(Game $game) => [
            'id' => $game->getId(),
            'title' => $game->getTitle(),
        ], $games));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['title'])) {
            return new JsonResponse(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        $game = new Game($data['title']);
        $this->em->persist($game);
        $this->em->flush();

        return new JsonResponse([
            'id' => $game->getId(),
            'title' => $game->getTitle(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/top', methods: ['GET'])]
    public function top(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 10);

        $results = $this->gameLoveRepository->findTopLovedGames($limit);

        return new JsonResponse(array_map(fn(array $row) => [
            'gameId' => (int) $row['gameId'],
            'title' => $row['title'],
            'loves' => (int) $row['loveCount'],
        ], $results));
    }
}
