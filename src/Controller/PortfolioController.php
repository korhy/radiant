<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Document\Experience;
use Doctrine\ODM\MongoDB\DocumentManager;

class PortfolioController extends AbstractController
{
    #[Route('/')]
    public function index(DocumentManager $dm): Response
    {
        $repository = $dm->getRepository(Experience::class);

        $experiences =  $repository->findAll();


        return $this->render('portfolio/layout.html.twig', ['experiences' => $experiences]);
    }

    #[Route('/portfolio/createExperience')]
    public function createExperience(DocumentManager $dm)
    {
        $experience = new Experience();

        $experience->setCompany('Indépendant');
        $experience->setPosition('Développeur Back-End');
        $experience->setDescription([
            'Projet de fin d’étude que j’ai poursuivie',
            'Projet de site e-commerce de vente en direct de petit producteur/artisan',
            'Développement et maintenance de l’application',
            'Conception de la base de donnée',
            'Mise en place des services de paiement'
        ]);
        $experience->setUrl('');
        $experience->setFrom('sept 2017');
        $experience->setTo('jan 2018');
        $experience->setTags([
            'PHP', 'Symfony', 'MySQL', 'Stripe', 'PayPal'
        ]);

        $dm->persist($experience);
        $dm->flush();

        return new Response('Created experience id ' . $experience->getId());
    }
}