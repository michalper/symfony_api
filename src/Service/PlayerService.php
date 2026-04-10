<?php

namespace App\Service;

use App\Entity\Player;
use App\Repository\PlayerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class PlayerService
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private EntityManagerInterface $em,
    ) {}

    public function create(string $name): Player
    {
        $player = new Player($name);
        $this->em->persist($player);
        $this->em->flush();

        return $player;
    }

    /**
     * @return array{items: Player[], total: int}
     */
    public function list(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        return [
            'items' => $this->playerRepository->findPaginated($offset, $limit),
            'total' => $this->playerRepository->countAll(),
        ];
    }
}
