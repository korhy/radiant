<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AppRepository;
use App\Repository\ExperienceRepository;
use App\Repository\PersonalProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PortfolioController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(
        ExperienceRepository $experienceRepository,
        PersonalProjectRepository $personalProjectRepository,
        AppRepository $appRepository,
    ): Response {
        return $this->render('portfolio/layout.html.twig', [
            'experiences' => $experienceRepository->findAllOrderedByStartDate(),
            'projects' => $personalProjectRepository->findAll(),
            'apps' => $appRepository->findAllOrderedByPosition(),
        ]);
    }
}
