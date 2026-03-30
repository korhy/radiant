<?php

namespace App\Controller;

use App\Repository\AppRepository;
use App\Service\Cookbook\CookbookApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        return $this->render('app/cookbook/index.html.twig', [
            'recipes' => $cookbookApiService->getRecipes(),
            'apiDocUrl' => $apiUrl.'/api/'.$apiVersion.'/docs',
            'app_detail' => $appRepository->findBySlug('cookbook'),
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
