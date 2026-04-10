<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreatePlayerInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(max: 255, maxMessage: 'Name must not exceed 255 characters')]
        public string $name = '',
    ) {}
}
