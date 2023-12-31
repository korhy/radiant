<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class PortfolioController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('portfolio/layout.html.twig', [
            'articles' => [
                [
                    'title' => 'TEST',
                    'body' => 'Body Test'
                ]
            ],
        ]);
    }
}