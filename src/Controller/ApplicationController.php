<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApplicationController extends AbstractController
{
    #[Route('/app/taquin', name: 'taquin')]
    public function taquin(): Response
    {
        return $this->render('app/taquin/index.html.twig');
    }
}
