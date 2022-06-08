<?php

namespace App\Manager;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    public function __construct(
        protected readonly ManagerRegistry $managerRegistry,
        protected readonly EntityManagerInterface $em,
        protected readonly FormFactoryInterface $formFactory,
        private readonly UserPasswordHasherInterface $userPasswordHasher
    ) {
    }

    public function listUsers(): ?array
    {
        return $this->managerRegistry->getRepository(User::class)->findAll();
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

            $this->em->persist($user);
            $this->em->flush();
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

            $this->em->flush();
        }

        return $form;
    }
}
