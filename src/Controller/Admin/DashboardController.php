<?php

namespace App\Controller\Admin;

use App\Entity\Experience;
use App\Entity\PersonalProject;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $routeBuilder = $this->container->get(AdminUrlGenerator::class);
		$url = $routeBuilder->setController(ExperienceCrudController::class)->generateUrl();

		return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Radiant');
    }

    public function configureMenuItems(): iterable
	{
		yield MenuItem::linktoRoute('Back to the website', 'fas fa-home', 'homepage');
		yield MenuItem::linkToCrud('Experiences', 'fa-solid fa-book', Experience::class);
		yield MenuItem::linkToCrud('Projects', 'fa-solid fa-list', PersonalProject::class);
	}

}
