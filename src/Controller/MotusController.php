<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AppRepository;
use App\Service\Motus\MotusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MotusController extends AbstractController
{
    #[Route('/app/motus', name: 'motus')]
    public function index(MotusService $motusService, AppRepository $appRepository): Response
    {
        $word = $motusService->getWordOfTheDay();

        return $this->render('app/motus/index.html.twig', [
            'word_length' => mb_strlen($word),
            'first_letter' => mb_substr($word, 0, 1),
            'app_detail' => $appRepository->findBySlug('motus'),
        ]);
    }

    #[Route('/app/motus/guess', name: 'motus_guess', methods: ['POST'])]
    public function guess(MotusService $motusService, Request $request): JsonResponse
    {
        // Le corps est du JSON libre : il peut être vide, ou ne pas être un objet.
        $payload = json_decode($request->getContent(), true);
        $guess = \is_array($payload) ? trim(mb_strtoupper((string) ($payload['guess'] ?? ''))) : '';

        $word = $motusService->getWordOfTheDay();

        if (mb_strlen($guess) !== mb_strlen($word)) {
            return $this->json(['error' => 'Longueur incorrecte'], Response::HTTP_BAD_REQUEST);
        }

        $result = $motusService->checkGuess($guess, $word);

        return $this->json([
            'result' => $result,
            'won' => $motusService->isWinning($result),
        ]);
    }
}
