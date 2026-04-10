<?php

namespace App\Controller;

use App\DTO\LoveGameInput;
use App\Entity\GameLove;
use App\Exception\GameAlreadyLovedException;
use App\Exception\GameNotFoundException;
use App\Exception\LoveNotFoundException;
use App\Exception\PlayerNotFoundException;
use App\Service\GameLoveService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/players/{playerId}/loves')]
readonly class GameLoveController
{
    public function __construct(
        private GameLoveService $gameLoveService,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', methods: ['POST'])]
    public function love(int $playerId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $gameId = $data['gameId'] ?? null;
        $input = new LoveGameInput(is_int($gameId) ? $gameId : null);
        $violations = $this->validator->validate($input);

        if (count($violations) > 0) {
            return new JsonResponse(['error' => $violations[0]->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        try {
            $love = $this->gameLoveService->love($playerId, $input->gameId);
        } catch (PlayerNotFoundException) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        } catch (GameNotFoundException) {
            return new JsonResponse(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        } catch (GameAlreadyLovedException) {
            return new JsonResponse(['error' => 'Already loved'], Response::HTTP_CONFLICT);
        }

        return new JsonResponse([
            'id' => $love->getId(),
            'playerId' => $love->getPlayer()->getId(),
            'gameId' => $love->getGame()->getId(),
            'createdAt' => $love->getCreatedAt()->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{gameId}', methods: ['DELETE'])]
    public function unlove(int $playerId, int $gameId): JsonResponse
    {
        try {
            $this->gameLoveService->unlove($playerId, $gameId);
        } catch (PlayerNotFoundException) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        } catch (LoveNotFoundException) {
            return new JsonResponse(['error' => 'Love not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', methods: ['GET'])]
    public function list(int $playerId): JsonResponse
    {
        try {
            $loves = $this->gameLoveService->listByPlayer($playerId);
        } catch (PlayerNotFoundException) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(array_map(fn(GameLove $love) => [
            'gameId' => $love->getGame()->getId(),
            'title' => $love->getGame()->getTitle(),
            'lovedAt' => $love->getCreatedAt()->format('c'),
        ], $loves));
    }
}
