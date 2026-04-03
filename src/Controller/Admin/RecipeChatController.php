<?php

namespace App\Controller\Admin;

use App\Service\Cookbook\CookbookApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/recipe-chat')]
class RecipeChatController extends AbstractController
{
    #[Route('', name: 'admin_recipe_chat', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/recipe_chat/index.html.twig');
    }

    #[Route('/message', name: 'admin_recipe_chat_message', methods: ['POST'])]
    public function message(Request $request, CookbookApiService $cookbookApi): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = trim($data['message'] ?? '');
        $history = $data['history'] ?? [];

        if ('' === $userMessage) {
            return $this->json(['error' => 'Message vide.'], 400);
        }

        try {
            $recipe = $cookbookApi->chat($userMessage, $history);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json($recipe);
    }
}
