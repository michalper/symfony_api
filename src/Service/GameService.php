<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\GameLoveRepositoryInterface;
use App\Repository\GameRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class GameService
{
    public function __construct(
        private GameRepositoryInterface $gameRepository,
        private GameLoveRepositoryInterface $gameLoveRepository,
        private EntityManagerInterface $em,
    ) {}

    public function create(string $title): Game
    {
        $game = new Game($title);
        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }

    /**
     * @return array{items: Game[], total: int}
     */
    public function list(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        return [
            'items' => $this->gameRepository->findPaginated($offset, $limit),
            'total' => $this->gameRepository->countAll(),
        ];
    }

    /**
     * @return array<array{gameId: int, title: string, loveCount: int}>
     */
    public function topLoved(int $limit): array
    {
        return $this->gameLoveRepository->findTopLovedGames($limit);
    }
}
