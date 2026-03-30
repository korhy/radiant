<?php

namespace App\Controller;

use App\Entity\Experience;
use App\Entity\PersonalProject;
use App\Repository\AppRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PortfolioController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(EntityManagerInterface $entityManager, AppRepository $appRepository): Response
    {
        $experiences = $entityManager->getRepository(Experience::class)->findBy(
            [],
            ['start_date' => 'DESC']
        );

        $projetcs = $entityManager->getRepository(PersonalProject::class)->findAll();

        $apps = $appRepository->findAllOrderedByPosition();

        return $this->render('portfolio/layout.html.twig', [
            'experiences' => $experiences,
            'projects' => $projetcs,
            'apps' => $apps,
        ]);
    }
}
