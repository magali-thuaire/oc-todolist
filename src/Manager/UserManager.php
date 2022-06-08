<?php

namespace App\Manager;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    public function __construct(
        protected readonly UserRepository $userRepository,
        protected readonly FormFactoryInterface $formFactory,
        private readonly UserPasswordHasherInterface $userPasswordHasher
    ) {
    }

    public function listUsers(): ?array
    {
        return $this->userRepository->findAll();
    }

    public function createUser(Request $request): FormInterface
    {
        $user = new User();
        $form = $this->formFactory->create(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->userPasswordHasher->hashPassword($user, $user->getPlainPassword());
            $user
                ->setPassword($password)
                ->eraseCredentials()
            ;

            $this->userRepository->add($user, true);
        }

        return $form;
    }

    public function editUser(Request $request, User $user): FormInterface
    {
        $form = $this->formFactory->create(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->userPasswordHasher->hashPassword($user, $user->getPlainPassword());
            $user
                ->setPassword($password)
                ->eraseCredentials()
            ;

            $this->userRepository->update($user, true);
        }

        return $form;
    }

    public function deleteUser(User $user)
    {
        $this->userRepository->remove($user, true);
    }
}
