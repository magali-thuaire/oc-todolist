<?php

namespace App\Manager;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\AppMailer;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class UserManager
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly FormFactoryInterface $formFactory,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly AppMailer $mailer,
    ) {
    }

    public function listUsers(): ?array
    {
        return $this->userRepository->findAll();
    }

    /**
     * @throws ResetPasswordExceptionInterface
     */
    public function createUser(Request $request): FormInterface
    {
        $user = new User();
        $form = $this->formFactory->create(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Random password
            $password = $this->userPasswordHasher->hashPassword($user, random_bytes(15));
            $user
                ->setPassword($password)
                ->eraseCredentials()
            ;

            $this->userRepository->add($user, true);

            $resetToken = $this->resetPasswordHelper->generateResetToken($user);

            $this->mailer->sendEmailNewPassword($user, $resetToken);
        }

        return $form;
    }

    public function editUser(Request $request, User $user): FormInterface
    {
        $form = $this->formFactory->create(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->update($user, true);
        }

        return $form;
    }

    public function deleteUser(User $user)
    {
        $this->userRepository->remove($user, true);
    }
}
