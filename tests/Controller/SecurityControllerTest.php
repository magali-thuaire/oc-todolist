<?php

namespace App\Tests\Controller;

use App\Tests\Utils\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityControllerTest extends BaseWebTestCase
{
    /**
     * @dataProvider getUnauthorizedActions
     */
    public function testUnauthorizedAction(string $method, string $uri): void
    {
        $this->unauthorizedAction($method, $uri);
    }

    public function testSecurityGETLogin(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/login');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString('Connexion', $title->text());

        // Image
        $this->assertNotEmpty($crawler->filter('img.slide-image'));

        // Form
        $this->assertSelectorExists('form');
        $form = $crawler->filter('form');
        $checkLoginUri = $this->getRouter()->generate('login');

        $this->assertEquals($checkLoginUri, $form->attr('action'));
        $this->assertSelectorExists('input[type=text]#username');
        $this->assertSelectorExists('input[type=password]#password');
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Se connecter');
    }

    public function testSecurityPOSTLogin(): void
    {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/login');

        // User
        $user = $this->createUser();

        // Form
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $user->getUsername(),
            '_password' => 'todolist',
        ]);
        $this->client->submit($form);

        // Redirection
        $homepageUri = $this->getRouter()->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->assertResponseRedirects($homepageUri);
        $this->client->followRedirect();
    }

    public function testSecurityPOSTLoginWithCSRFError(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/login');

        // User
        $user = $this->createUser();

        // Form
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $user->getUsername(),
            '_password' => 'todolist',
            '_csrf_token' => 'csrf_token_invalid',
        ]);
        $this->client->submit($form);

        // Redirection
        $loginUri = $this->getRouter()->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertResponseRedirects($loginUri);
        $this->client->followRedirect();

        // Errors
        $this->assertSelectorTextSame('div.alert.alert-danger', 'Jeton CSRF invalide.');
    }

    public function testSecurityPOSTLoginWithErrors(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/login');

        // User
        $this->createUser();

        // Form
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => '',
            '_password' => '',
        ]);
        $this->client->submit($form);

        // Redirection
        $loginUri = $this->getRouter()->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertResponseRedirects($loginUri);
        $this->client->followRedirect();

        // Errors
        $this->assertSelectorTextSame('div.alert.alert-danger', 'Identifiants invalides.');
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function getUnauthorizedActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/logout'],
        ];
    }
}
