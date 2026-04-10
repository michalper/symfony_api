<?php

namespace App\Exception;

class LoveNotFoundException extends \RuntimeException
{
    public function __construct(int $playerId, int $gameId)
    {
        parent::__construct(sprintf('Love not found for player %d and game %d', $playerId, $gameId));
    }
}
