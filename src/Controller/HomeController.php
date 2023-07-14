<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class HomeController
{
    #[Route('/home')]
    public function index(): Response
    {
        return new Response('<html><body>Clément BOUDINEL</body></html>');
    }
}