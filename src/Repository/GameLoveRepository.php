<?php

namespace App\Repository;

use App\Entity\GameLove;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GameLoveRepository extends ServiceEntityRepository implements GameLoveRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameLove::class);
    }

    public function findByPlayer(int $playerId): array
    {
        return $this->createQueryBuilder('gl')
            ->join('gl.game', 'g')
            ->where('gl.player = :playerId')
            ->setParameter('playerId', $playerId)
            ->getQuery()
            ->getResult();
    }

    public function findTopLovedGames(int $limit): array
    {
        return $this->createQueryBuilder('gl')
            ->select('IDENTITY(gl.game) as gameId, g.title, COUNT(gl.id) as loveCount')
            ->join('gl.game', 'g')
            ->groupBy('gl.game, g.title')
            ->orderBy('loveCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneByPlayerAndGame(int $playerId, int $gameId): ?GameLove
    {
        return $this->createQueryBuilder('gl')
            ->where('gl.player = :playerId')
            ->andWhere('gl.game = :gameId')
            ->setParameter('playerId', $playerId)
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
