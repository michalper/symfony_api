<?php

namespace App\Controller;

use App\DTO\CreatePlayerInput;
use App\Entity\Player;
use App\Service\PlayerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/players')]
readonly class PlayerController
{
    public function __construct(
        private PlayerService $playerService,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));

        $result = $this->playerService->list($page, $limit);

        return new JsonResponse([
            'data' => array_map(fn(Player $player) => [
                'id' => $player->getId(),
                'name' => $player->getName(),
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

        $input = new CreatePlayerInput(trim((string) ($data['name'] ?? '')));
        $violations = $this->validator->validate($input);

        if (count($violations) > 0) {
            return new JsonResponse(['error' => $violations[0]->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $player = $this->playerService->create($input->name);

        return new JsonResponse([
            'id' => $player->getId(),
            'name' => $player->getName(),
        ], Response::HTTP_CREATED);
    }
}
