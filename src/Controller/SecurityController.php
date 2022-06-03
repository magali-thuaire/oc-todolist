<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends BaseController
{
    #[Route(path: '/login', name: 'login', methods: 'GET')]
    public function loginAction(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/login_check', name: 'login_check', methods: 'POST')]
    public function loginCheck(): void
    {
        // This code is never executed.
    }

    #[Route(path: '/logout', name: 'logout', methods: 'GET')]
    public function logoutCheck(): void
    {
        // This code is never executed.
    }
}
