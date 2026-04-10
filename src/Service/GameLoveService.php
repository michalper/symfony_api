<?php

namespace App\Service;

use App\Entity\GameLove;
use App\Exception\GameAlreadyLovedException;
use App\Exception\GameNotFoundException;
use App\Exception\LoveNotFoundException;
use App\Exception\PlayerNotFoundException;
use App\Repository\GameLoveRepositoryInterface;
use App\Repository\GameRepositoryInterface;
use App\Repository\PlayerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class GameLoveService
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private GameRepositoryInterface $gameRepository,
        private GameLoveRepositoryInterface $gameLoveRepository,
        private EntityManagerInterface $em,
    ) {}

    public function love(int $playerId, int $gameId): GameLove
    {
        $player = $this->playerRepository->findById($playerId)
            ?? throw new PlayerNotFoundException($playerId);

        $game = $this->gameRepository->findById($gameId)
            ?? throw new GameNotFoundException($gameId);

        if ($this->gameLoveRepository->findOneByPlayerAndGame($playerId, $gameId)) {
            throw new GameAlreadyLovedException($playerId, $gameId);
        }

        $love = new GameLove($player, $game);
        $this->em->persist($love);
        $this->em->flush();

        return $love;
    }

    public function unlove(int $playerId, int $gameId): void
    {
        $this->playerRepository->findById($playerId)
            ?? throw new PlayerNotFoundException($playerId);

        $love = $this->gameLoveRepository->findOneByPlayerAndGame($playerId, $gameId)
            ?? throw new LoveNotFoundException($playerId, $gameId);

        $this->em->remove($love);
        $this->em->flush();
    }

    /**
     * @return GameLove[]
     */
    public function listByPlayer(int $playerId): array
    {
        $this->playerRepository->findById($playerId)
            ?? throw new PlayerNotFoundException($playerId);

        return $this->gameLoveRepository->findByPlayer($playerId);
    }
}
