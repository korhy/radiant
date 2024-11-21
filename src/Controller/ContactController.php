<?php

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
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
        private MailerInterface $mailer,
        #[Autowire('%admin_email%')] private string $adminEmail
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
            $this->mailer->send((new NotificationEmail())
                ->subject('Demande de contact')
                ->htmlTemplate('email/contact.html.twig')
                ->from($form->get('email')->getData())
                ->to($this->adminEmail)
                ->context(['contact' => $form->getData()]));

            $this->addFlash('success', 'Votre demande de contact a bien été envoyé');
            return $this->redirectToRoute('contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form
        ]);
    }
}