<?php

namespace App\Controller;

use App\Entity\Player;
use App\Repository\PlayerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players')]
readonly class PlayerController
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private EntityManagerInterface    $em,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $players = $this->playerRepository->findAll();

        return new JsonResponse(array_map(fn(Player $player) => [
            'id' => $player->getId(),
            'name' => $player->getName(),
        ], $players));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return new JsonResponse(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $player = new Player($data['name']);
        $this->em->persist($player);
        $this->em->flush();

        return new JsonResponse([
            'id' => $player->getId(),
            'name' => $player->getName(),
        ], Response::HTTP_CREATED);
    }
}
