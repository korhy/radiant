<?php

namespace App\Controller;

use App\Entity\Experience;
use App\Entity\PersonalProject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PortfolioController extends AbstractController
{
    #[Route('/', name: 'homepage')]
	public function index(EntityManagerInterface $entityManager): Response
	{
		$experiences = $entityManager->getRepository(Experience::class)->findBy([],
            ['start_date' => 'DESC']
        );

        $projetcs = $entityManager->getRepository(PersonalProject::class)->findAll();

        return $this->render('portfolio/layout.html.twig', [
            'experiences' => $experiences,
            'projects' => $projetcs
        ]);
    }
}
