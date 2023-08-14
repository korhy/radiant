<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Document\Experience as mExperience;
use Doctrine\ODM\MongoDB\DocumentManager;

use App\Entity\Experience;
use Doctrine\ORM\EntityManagerInterface;


class PortfolioController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(EntityManagerInterface $entityManager) : Response
    {
        $experiences = $entityManager->getRepository(Experience::class)->findAll();

        return $this->render('portfolio/layout.html.twig', ['experiences' => $experiences]);
    }
}