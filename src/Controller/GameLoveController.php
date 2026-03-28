<?php

namespace App\Controller;

use App\Entity\GameLove;
use App\Repository\GameLoveRepositoryInterface;
use App\Repository\GameRepositoryInterface;
use App\Repository\PlayerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players/{playerId}/loves')]
readonly class GameLoveController
{
    public function __construct(
        private PlayerRepositoryInterface   $playerRepository,
        private GameRepositoryInterface     $gameRepository,
        private GameLoveRepositoryInterface $gameLoveRepository,
        private EntityManagerInterface      $em,
    ) {}

    #[Route('', methods: ['POST'])]
    public function love(int $playerId, Request $request): JsonResponse
    {
        if (!$player = $this->playerRepository->findById($playerId)) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['gameId'])) {
            return new JsonResponse(['error' => 'gameId is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!$game = $this->gameRepository->findById($data['gameId'])) {
            return new JsonResponse(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        if ($this->gameLoveRepository->findOneByPlayerAndGame($playerId, $data['gameId'])) {
            return new JsonResponse(['error' => 'Already loved'], Response::HTTP_CONFLICT);
        }

        $love = new GameLove($player, $game);
        $this->em->persist($love);
        $this->em->flush();

        return new JsonResponse([
            'id' => $love->getId(),
            'playerId' => $player->getId(),
            'gameId' => $game->getId(),
            'createdAt' => $love->getCreatedAt()->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{gameId}', methods: ['DELETE'])]
    public function unlove(int $playerId, int $gameId): JsonResponse
    {
        if (!$love = $this->gameLoveRepository->findOneByPlayerAndGame($playerId, $gameId)) {
            return new JsonResponse(['error' => 'Love not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($love);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', methods: ['GET'])]
    public function list(int $playerId): JsonResponse
    {
        if (!$this->playerRepository->findById($playerId)) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        $loves = $this->gameLoveRepository->findByPlayer($playerId);

        return new JsonResponse(array_map(fn(GameLove $love) => [
            'gameId' => $love->getGame()->getId(),
            'title' => $love->getGame()->getTitle(),
            'lovedAt' => $love->getCreatedAt()->format('c'),
        ], $loves));
    }
}
