<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\ResetPasswordManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;

#[Route('/reset-password')]
#[IsGranted('ROLE_ADMIN')]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly ResetPasswordManager $resetPasswordManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/reset/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(Request $request, string $token = null): Response
    {

        $form = $this->resetPasswordManager->resetPassword($token, $request);

        if (!$form instanceof FormInterface) {
            $this->addFlash('reset_password_error', sprintf(
                '%s',
                $this->translator->trans(
                    ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                    [],
                    'ResetPasswordBundle'
                )
            ));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash(
                'success',
                $this->translator->trans('user.change-password.success', [], 'flashes')
            );

            return $this->redirectToRoute('login');
        }

        return $this->renderForm('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

    #[Route('/send-email/{id}', name: 'app_reset_password_email', methods: 'GET')]
    public function sendEmail(User $user): RedirectResponse
    {
        $isEmailSent = $this->resetPasswordManager->isResetPasswordEmailSent($user);

        if ($isEmailSent) {
            $this->addFlash(
                'success',
                $this->translator->trans('user.reset-password.success', ['user.email' => $user->getEmail()], 'flashes')
            );
        } else {
            $this->addFlash(
                'error',
                $this->translator->trans('user.reset-password.error', [], 'flashes')
            );
        }

        return $this->redirectToRoute('user_edit', ['id' => $user->getId()]);
    }
}
