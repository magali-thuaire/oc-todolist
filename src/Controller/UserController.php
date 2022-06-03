<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class UserController extends BaseController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[Route(path: '/users', name: 'user_list', methods: 'GET')]
    public function listAction(): Response
    {
        return $this->render('user/list.html.twig', [
            'users' => $this->managerRegistry->getRepository(User::class)->findAll(),
        ]);
    }

    #[Route(path: '/users/create', name: 'user_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): RedirectResponse|Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->userPasswordHasher->hashPassword($user, $user->getPlainPassword());
            $user
                ->setPassword($password)
                ->eraseCredentials()
            ;

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('user.create.success', [], 'flashes')
            );

            return $this->redirectToRoute('user_list');
        }

        return $this->renderForm('user/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/users/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function editAction(User $user, Request $request): RedirectResponse|Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->userPasswordHasher->hashPassword($user, $user->getPlainPassword());
            $user
                ->setPassword($password)
                ->eraseCredentials()
            ;

            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('user.edit.success', [], 'flashes')
            );

            return $this->redirectToRoute('user_list');
        }

        return $this->renderForm('user/edit.html.twig', [
            'form' => $form,
            'user' => $user
        ]);
    }
}
