<?php

namespace App\Repository;

use App\Entity\GameLove;

interface GameLoveRepositoryInterface
{
    /** @return GameLove[] */
    public function findByPlayer(int $playerId): array;

    /** @return array<array{gameId: int, title: string, loveCount: int}> */
    public function findTopLovedGames(int $limit): array;

    public function findOneByPlayerAndGame(int $playerId, int $gameId): ?GameLove;
}
