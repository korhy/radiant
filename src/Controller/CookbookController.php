<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AppRepository;
use App\Service\Cookbook\CookbookApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CookbookController extends AbstractController
{
    #[Route('/app/cookbook', name: 'cookbook')]
    public function index(CookbookApiService $cookbookApiService, AppRepository $appRepository): Response
    {
        $data = $cookbookApiService->getRecipes();
        $categories = $cookbookApiService->getCategories();

        return $this->render('app/cookbook/index.html.twig', [
            'recipes' => $data['member'] ?? [],
            'hasNextPage' => isset($data['view']['next']),
            'categories' => $categories['member'] ?? [],
            'apiDocUrl' => $cookbookApiService->getDocUrl(),
            'app_detail' => $appRepository->findBySlug('cookbook'),
        ]);
    }

    #[Route('/app/cookbook/recipes', name: 'cookbook_recipes_json')]
    public function recipesJson(CookbookApiService $cookbookApiService, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $itemsPerPage = max(1, (int) $request->query->get('itemsPerPage', 10));

        $order = $request->query->all('order');

        $filters = array_filter([
            'title' => $request->query->get('query'),
            'category' => $request->query->get('category'),
            'order[slug]' => $order['title'] ?? null,
            'order[duration]' => $order['duration'] ?? null,
            'order[createdAt]' => $order['createdAt'] ?? null,
        ]);

        $data = $cookbookApiService->getRecipes($page, $itemsPerPage, $filters);

        return $this->json([
            'recipes' => $data['member'] ?? [],
            'hasNextPage' => isset($data['view']['next']),
            'nextPage' => isset($data['view']['next']) ? $page + 1 : null,
        ]);
    }

    // Sans la contrainte \d+, un identifiant non numérique produisait une 500
    // (TypeError) au lieu d'une 404.
    #[Route('/app/cookbook/recipe/{id}', name: 'cookbook_recipe', requirements: ['id' => '\d+'])]
    public function recipe(
        CookbookApiService $cookbookApiService,
        AppRepository $appRepository,
        int $id,
    ): Response {
        return $this->render('app/cookbook/recipe.html.twig', [
            'recipe' => $cookbookApiService->getRecipe($id),
            'app_detail' => $appRepository->findBySlug('cookbook/recipe'),
        ]);
    }
}
