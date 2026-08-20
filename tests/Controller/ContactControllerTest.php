<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;

final class ContactControllerTest extends WebTestCase
{
    /**
     * SPF and DKIM only sign our own domain, so the visitor address must never
     * reach the From header (audit finding S4).
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
        // Messenger routes mails to an async transport: the message is queued,
        // never sent synchronously, so assertEmailCount would see nothing.
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

        // 422: the form is re-rendered with its errors, nothing is sent.
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertQueuedEmailCount(0);
    }
}
