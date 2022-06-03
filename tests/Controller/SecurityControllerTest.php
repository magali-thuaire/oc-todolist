<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Tests\Utils\BaseWebTestCase;

class SecurityControllerTest extends BaseWebTestCase
{
    /**
     * @dataProvider getUnauthorizedActions
     */
    public function testUnauthorizedAction(string $method, string $uri)
    {
        $this->unauthorizedAction($method, $uri);
    }

    public function testSecurityGETLogin()
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/login');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // New User button
        $this->assertSelectorExists('a.btn.btn-info');
        $newUserButton = $crawler->filter('a.btn.btn-info');

        $newUserUri = $this->getRouter()->generate('user_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer un utilisateur', $newUserButton->text());

        // Image
        $this->assertNotEmpty($crawler->filter('img.slide-image'));

        // Form
        $this->assertSelectorExists('form');
        $form = $crawler->filter('form');
        $checkLoginUri = $this->getRouter()->generate('login_check');

        $this->assertEquals($checkLoginUri, $form->attr('action'));
        $this->assertSelectorExists('input[type=text]#username');
        $this->assertSelectorExists('input[type=password]#password');
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Se connecter');
    }

    public function testSecurityPOSTLogin()
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
        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();

        $homepageUri = $this->getRouter()->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertEquals($homepageUri, $crawler->getUri());
    }

    public function testSecurityPOSTLoginWithCSRFError()
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/login');

        // User
        $user = $this->createUser();

        // Form
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $user->getUsername(),
            '_password' => 'todolist',
            '_csrf_token' => 'csrf_token_invalid'
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Errors
        $this->assertSelectorTextSame('div.alert.alert-danger', 'Jeton CSRF invalide.');
    }

    public function testSecurityPOSTLoginWithErrors()
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
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Errors
        $this->assertSelectorTextSame('div.alert.alert-danger', 'Identifiants invalides.');
    }

    public function getUnauthorizedActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/logout'],
        ];
    }
}
