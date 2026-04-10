<?php

namespace App\Repository;

use App\Entity\Game;

interface GameRepositoryInterface
{
    public function findById(int $id): ?Game;

    /** @return Game[] */
    public function findAll(): array;

    /** @return Game[] */
    public function findPaginated(int $offset, int $limit): array;

    public function countAll(): int;
}
