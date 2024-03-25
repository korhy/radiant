<?php

namespace App\Controller\Admin;

use App\Entity\PersonalProject;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PersonalProjectCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return PersonalProject::class;
	}


	public function configureFields(string $pageName): iterable
	{
		return [
			TextField::new('name'),
			TextareaField::new('description'),
			TextField::new('url'),
			TextareaField::new('jsonTags')
		];
	}

}
