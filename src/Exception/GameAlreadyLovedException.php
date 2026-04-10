<?php

namespace App\Exception;

class GameAlreadyLovedException extends \RuntimeException
{
    public function __construct(int $playerId, int $gameId)
    {
        parent::__construct(sprintf('Player %d already loves game %d', $playerId, $gameId));
    }
}
