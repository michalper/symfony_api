<?php

namespace App\Exception;

class PlayerNotFoundException extends \RuntimeException
{
    public function __construct(int $playerId)
    {
        parent::__construct(sprintf('Player with ID %d not found', $playerId));
    }
}
