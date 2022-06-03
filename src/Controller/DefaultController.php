<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends BaseController
{
    #[Route(path: '/', name: 'homepage', methods: 'GET')]
    public function indexAction(): Response
    {
        return $this->render('default/index.html.twig');
    }
}
