<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class LoveGameInput
{
    public function __construct(
        #[Assert\NotNull(message: 'gameId is required')]
        #[Assert\Positive(message: 'gameId must be a positive integer')]
        public ?int $gameId = null,
    ) {}
}
