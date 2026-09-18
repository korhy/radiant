<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SiteContent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Vich\UploaderBundle\Form\Type\VichFileType;

final class SiteContentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SiteContent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Site content')
            ->setEntityLabelInPlural('Site content');
    }

    /**
     * The table is meant to hold exactly one row, seeded by migration. Removing NEW and DELETE
     * makes that an invariant the back-office enforces rather than a convention.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextareaField::new('aboutText', 'À propos')
                ->setNumOfRows(8)
                // The only place the convention is ever shown to the author: drop this help and
                // the markers become folklore.
                ->setHelp('Entourez une expression de <code>**</code> pour la mettre en couleur : <code>un **mot** en valeur</code>. C’est le seul effet disponible.'),
            TextField::new('cvFile', 'CV (PDF)')
                ->setFormType(VichFileType::class)
                ->setHelp('PDF, 5 Mo maximum. Le fichier déposé remplace celui proposé au téléchargement dans le pied de page.')
                ->onlyOnForms(),
            TextField::new('cvOriginalName', 'CV publié')
                ->onlyOnIndex(),
        ];
    }
}
