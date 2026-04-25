<?php

namespace App\Controller;

use App\Repository\AppRepository;
use App\Service\Cookbook\CookbookApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApplicationController extends AbstractController
{
    #[Route('/app/taquin', name: 'taquin')]
    public function taquin(AppRepository $appRepository): Response
    {
        return $this->render('app/taquin/index.html.twig', [
            'app_detail' => $appRepository->findBySlug('taquin'),
        ]);
    }

    #[Route('/app/cookbook', name: 'cookbook')]
    public function cookbook(
        CookbookApiService $cookbookApiService,
        AppRepository $appRepository,
        #[Autowire(env: 'COOKBOOK_API_URL')] string $apiUrl,
        #[Autowire(env: 'COOKBOOK_API_VERSION')] string $apiVersion,
    ): Response {
        $data = $cookbookApiService->getRecipes();
        $categories = $cookbookApiService->getCategories();

        return $this->render('app/cookbook/index.html.twig', [
            'recipes' => $data['member'] ?? [],
            'hasNextPage' => isset($data['view']['next']),
            'categories' => $categories['member'] ?? [],
            'apiDocUrl' => $apiUrl.'/api/'.$apiVersion.'/docs',
            'app_detail' => $appRepository->findBySlug('cookbook'),
        ]);
    }

    #[Route('/app/cookbook/recipes', name: 'cookbook_recipes_json')]
    public function cookbookRecipesJson(
        CookbookApiService $cookbookApiService,
        Request $request,
    ): JsonResponse {
        $page = max(1, (int) $request->query->get('page', 1));
        $itemsPerPage = max(1, (int) $request->query->get('itemsPerPage', 10));

        $order = $request->query->all('order');

        $filters = array_filter([
            'title' => $request->query->get('query'),
            'category' => $request->query->get('category'),
            'order[title]' => $order['title'] ?? null,
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

    #[Route('/app/cookbook/recipe/{id}', name: 'cookbook_recipe')]
    public function cookbookRecipe(
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
