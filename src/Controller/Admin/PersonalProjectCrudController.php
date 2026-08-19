<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\PersonalProject;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class PersonalProjectCrudController extends AbstractCrudController
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
            TextField::new('file')->setFormType(VichImageType::class),
            TextareaField::new('jsonTags'),
        ];
    }
}
