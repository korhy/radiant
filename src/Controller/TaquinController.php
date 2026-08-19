<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AppRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TaquinController extends AbstractController
{
    #[Route('/app/taquin', name: 'taquin')]
    public function index(AppRepository $appRepository): Response
    {
        return $this->render('app/taquin/index.html.twig', [
            'app_detail' => $appRepository->findBySlug('taquin'),
        ]);
    }
}
