<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\ContactDTO;
use App\Form\Type\ContactType;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire('%admin_email%')] private string $adminEmail,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/contact', name: 'contact')]
    public function contact(Request $request): Response
    {
        $data = new ContactDTO();
        $form = $this->createForm(ContactType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // L'expéditeur reste le domaine du site : SPF et DKIM ne peuvent
            // signer que celui-là, et l'enveloppe (mailer.yaml) porte déjà la
            // même adresse. Le visiteur passe en Reply-To, ce qui garde le
            // « Répondre » naturel sans laisser usurper une adresse tierce.
            $this->mailer->send((new NotificationEmail())
                ->subject('Demande de contact')
                ->htmlTemplate('email/contact.html.twig')
                ->from(new Address($this->adminEmail, 'Formulaire de contact'))
                ->replyTo(new Address($data->email, $data->name))
                ->to($this->adminEmail)
                ->context(['contact' => $data]));

            $this->addFlash('success', 'Votre demande de contact a bien été envoyé');

            return $this->redirectToRoute('contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
