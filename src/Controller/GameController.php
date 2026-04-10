<?php

namespace App\Controller;

use App\DTO\CreateGameInput;
use App\Entity\Game;
use App\Service\GameService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/games')]
readonly class GameController
{
    public function __construct(
        private GameService $gameService,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));

        $result = $this->gameService->list($page, $limit);

        return new JsonResponse([
            'data' => array_map(fn(Game $game) => [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
            ], $result['items']),
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $input = new CreateGameInput(trim((string) ($data['title'] ?? '')));
        $violations = $this->validator->validate($input);

        if (count($violations) > 0) {
            return new JsonResponse(['error' => $violations[0]->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $game = $this->gameService->create($input->title);

        return new JsonResponse([
            'id' => $game->getId(),
            'title' => $game->getTitle(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/top', methods: ['GET'])]
    public function top(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 10);

        $results = $this->gameService->topLoved($limit);

        return new JsonResponse(array_map(fn(array $row) => [
            'gameId' => (int) $row['gameId'],
            'title' => $row['title'],
            'loves' => (int) $row['loveCount'],
        ], $results));
    }
}
