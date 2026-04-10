<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateGameInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(max: 255, maxMessage: 'Title must not exceed 255 characters')]
        public string $title = '',
    ) {}
}
