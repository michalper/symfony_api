<?php

namespace App\Exception;

class GameNotFoundException extends \RuntimeException
{
    public function __construct(int $gameId)
    {
        parent::__construct(sprintf('Game with ID %d not found', $gameId));
    }
}
