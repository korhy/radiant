<?php

namespace App\Controller\Admin;

use App\Entity\App;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AppCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return App::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('slug'),
            TextField::new('label'),
            TextField::new('route'),
            TextareaField::new('description'),
            IntegerField::new('position'),
            TextareaField::new('jsonTechStack')->setHelp('[{"name": "Symfony", "category": "Backend"}]'),
            TextareaField::new('jsonChallenges')->setHelp('[{"title": "...", "description": "..."}]'),
            TextareaField::new('jsonImprovements')->setHelp('[{"description": "..."}]'),
            TextareaField::new('jsonResources')->setHelp('[{"label": "GitHub", "url": "https://..."}]'),
        ];
    }
}
