<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AppRepository;
use App\Service\Cookbook\CookbookApiService;
use App\Service\Cookbook\Exception\CookbookUnavailableException;
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
        $unavailable = false;

        try {
            $data = $cookbookApiService->getRecipes();
            $categories = $cookbookApiService->getCategories();
        } catch (CookbookUnavailableException) {
            $data = [];
            $categories = [];
            $unavailable = true;
        }

        return $this->render('app/cookbook/index.html.twig', [
            'recipes' => $data['member'] ?? [],
            'hasNextPage' => isset($data['view']['next']),
            'categories' => $categories['member'] ?? [],
            'apiDocUrl' => $cookbookApiService->getDocUrl(),
            'apiUnavailable' => $unavailable,
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

        try {
            $data = $cookbookApiService->getRecipes($page, $itemsPerPage, $filters);
        } catch (CookbookUnavailableException) {
            // 503 rather than an empty payload: the client must be able to
            // tell an outage from "no result" and show the right message.
            return $this->json([
                'html' => '',
                'count' => 0,
                'empty' => false,
                'hasNextPage' => false,
                'nextPage' => null,
                'announcement' => '',
                'unavailable' => true,
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $recipes = $data['member'] ?? [];
        $hasNextPage = isset($data['view']['next']);

        // The markup is rendered here, from the same template as the first
        // screen: the browser never assembles a card of its own.
        return $this->json([
            'html' => $this->renderView('app/cookbook/_recipe_grid_items.html.twig', ['recipes' => $recipes]),
            'count' => \count($recipes),
            'empty' => [] === $recipes && 1 === $page,
            'hasNextPage' => $hasNextPage,
            'nextPage' => $hasNextPage ? $page + 1 : null,
            'announcement' => [] === $recipes ? '' : trim($this->renderView(
                'app/cookbook/_recipe_grid_announcement.html.twig',
                ['count' => \count($recipes)]
            )),
        ]);
    }

    // Without the \d+ requirement, a non-numeric id raised a TypeError — a 500
    // where a 404 is expected.
    #[Route('/app/cookbook/recipe/{id}', name: 'cookbook_recipe', requirements: ['id' => '\d+'])]
    public function recipe(
        CookbookApiService $cookbookApiService,
        AppRepository $appRepository,
        int $id,
    ): Response {
        try {
            $recipe = $cookbookApiService->getRecipe($id);
        } catch (CookbookUnavailableException) {
            // A recipe page has nothing to degrade to: send the visitor back
            // to the list, which already carries the degraded state.
            $this->addFlash('warning', 'Le service de recettes est momentanément indisponible. Réessaie dans quelques instants.');

            return $this->redirectToRoute('cookbook');
        }

        return $this->render('app/cookbook/recipe.html.twig', [
            'recipe' => $recipe,
            'app_detail' => $appRepository->findBySlug('cookbook/recipe'),
        ]);
    }
}
