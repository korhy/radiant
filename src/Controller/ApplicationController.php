<?php

namespace App\Controller;

use App\Service\Cookbook\CookbookApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApplicationController extends AbstractController
{
    #[Route('/app/taquin', name: 'taquin')]
    public function taquin(): Response
    {
        return $this->render('app/taquin/index.html.twig');
    }

    #[Route('/app/cookbook', name: 'cookbook')]
    public function cookbook(
        CookbookApiService $cookbookApiService,
        #[Autowire(env: 'COOKBOOK_API_URL')] string $apiUrl,
        #[Autowire(env: 'COOKBOOK_API_VERSION')] string $apiVersion,
    ): Response {
        return $this->render('app/cookbook/index.html.twig', [
            'recipes' => $cookbookApiService->getRecipes(),
            'apiDocUrl' => $apiUrl.'/api/'.$apiVersion.'/docs',
        ]);
    }

    #[Route('/app/cookbook/recipe/{id}', name: 'cookbook_recipe')]
    public function cookbookRecipe(CookbookApiService $cookbookApiService, int $id): Response
    {
        return $this->render('app/cookbook/recipe.html.twig', [
            'recipe' => $cookbookApiService->getRecipe($id),
        ]);
    }
}
