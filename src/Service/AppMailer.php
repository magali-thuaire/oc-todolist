<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class AppMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function sendEmailResetPassword(User $user, ResetPasswordToken $resetToken): void
    {
        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject($this->translator->trans('user.password.reset.subject', [], 'email'))
            ->htmlTemplate('mail/password/reset.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
        }
    }

    public function sendEmailNewPassword(User $user, ResetPasswordToken $resetToken): void
    {
        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject($this->translator->trans('user.password.new.subject', [], 'email'))
            ->htmlTemplate('mail/password/new.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
        }
    }
}
