<?php

namespace App\Controller\Admin;

use App\Entity\Experience;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ExperienceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Experience::class;
    }

    public function configureFields(string $pageName): iterable
	{
		return [
			TextField::new('company'),
			TextField::new('position'),
			TextareaField::new('description'),
			TextField::new('url'),
			DateField::new('startDate'),
			DateField::new('endDate'),
			TextareaField::new('jsonTags')
		];
	}
}
