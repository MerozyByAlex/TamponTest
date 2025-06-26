<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerTest extends WebTestCase
{
    public function testEmailSending(): void
    {
        self::bootKernel();

        /** @var MailerInterface $mailer */
        $mailer = static::getContainer()->get(MailerInterface::class);

        $email = (new Email())
            ->from('test@local.dev')
            ->to('admin@local.dev')
            ->subject('Test Mailpit via PHPUnit')
            ->text('Mail fonctionnel depuis un test Symfony')
            ->html('<p><strong>Test OK</strong> envoyé depuis PHPUnit.</p>');

        // Exécution
        $mailer->send($email);

        // Pas d'assertion ici : si ça plante, le test échoue
        $this->assertTrue(true);
    }
}