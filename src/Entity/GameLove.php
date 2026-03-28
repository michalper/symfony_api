<?php

namespace App\Entity;

use App\Repository\GameLoveRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameLoveRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_player_game', columns: ['player_id', 'game_id'])]
class GameLove
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Game $game;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Player $player, Game $game)
    {
        $this->player = $player;
        $this->game = $game;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
