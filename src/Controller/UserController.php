<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\UserManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class UserController extends BaseController
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/users', name: 'user_list', methods: 'GET')]
    public function listAction(): Response
    {
        $users = $this->userManager->listUsers();

        return $this->render('user/list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route(path: '/users/create', name: 'user_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): RedirectResponse|Response
    {
        $form = $this->userManager->createUser($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        $form = $this->userManager->editUser($request, $user);

        if ($form->isSubmitted() && $form->isValid()) {
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
