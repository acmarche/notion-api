<?php

namespace AcMarche\Notion\Lib;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class Mailer
{
    private SymfonyMailer $mailer;

    public function __construct()
    {
        $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
        $this->mailer = new SymfonyMailer($transport);
    }

    /**
     * @param \stdClass $contact
     * @return void
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendContact(\stdClass $contact): void
    {
        $email = (new Email())
            ->from(new Address($_ENV['MAIL_FROM'], $contact->name))
            ->replyTo(new Address($contact->name, $contact->email))
            ->to($_ENV['MAIL_TO_CONTACT'])
            ->subject('[E-Square] : Message du site internet')
            ->text($contact->message);

        $this->mailer->send($email);
    }

    public static function sendError(string $message): void
    {
        $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
        $mailer = new SymfonyMailer($transport);
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($_ENV['MAIL_TO_WEBMASTER'])
            ->subject('[E-Square] : Error site')
            ->text($message);

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {

        }
    }
}