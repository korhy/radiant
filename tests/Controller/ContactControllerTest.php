<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;

/**
 * Le formulaire de contact envoie un mail depuis une adresse publique : c'est
 * le seul endroit du site où un visiteur anonyme déclenche un envoi.
 */
final class ContactControllerTest extends WebTestCase
{
    /**
     * L'en-tête From doit rester le domaine du site — SPF et DKIM ne signent
     * que celui-là — et l'adresse saisie repartir en Reply-To (constat S4).
     */
    public function testTheVisitorAddressLandsInReplyToNotInFrom(): void
    {
        $client = static::createClient();
        $adminEmail = static::getContainer()->getParameter('admin_email');

        $crawler = $client->request('GET', '/contact');

        $client->submit($crawler->selectButton('Envoyer')->form([
            'contact[name]' => 'Visiteuse Curieuse',
            'contact[email]' => 'visiteuse@example.org',
            'contact[message]' => 'Bonjour, un message assez long pour passer la validation.',
        ]));

        self::assertResponseRedirects('/contact');
        // Les mails partent par Messenger (routing async) : c'est le message
        // mis en file qu'il faut inspecter, pas un envoi synchrone.
        self::assertQueuedEmailCount(1);

        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);

        self::assertSame($adminEmail, $email->getFrom()[0]->getAddress());
        self::assertSame('visiteuse@example.org', $email->getReplyTo()[0]->getAddress());
        self::assertSame($adminEmail, $email->getTo()[0]->getAddress());
    }

    public function testAnInvalidSubmissionSendsNothing(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/contact');

        $client->submit($crawler->selectButton('Envoyer')->form([
            'contact[name]' => 'X',
            'contact[email]' => 'pas-une-adresse',
            'contact[message]' => 'court',
        ]));

        // 422 : le formulaire est réaffiché avec ses erreurs, rien n'est envoyé.
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertQueuedEmailCount(0);
    }
}
