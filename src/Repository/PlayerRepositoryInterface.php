<?php

namespace App\Repository;

use App\Entity\Player;

interface PlayerRepositoryInterface
{
    public function findById(int $id): ?Player;

    /** @return Player[] */
    public function findAll(): array;

    /** @return Player[] */
    public function findPaginated(int $offset, int $limit): array;

    public function countAll(): int;
}
