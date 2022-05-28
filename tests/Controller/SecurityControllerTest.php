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
    public function testUnauthorizedAction($uri)
    {
        $this->unauthorizedAction($uri);
    }

    public function testSecurityGETLogin()
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/login');

        // Response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // New User button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-primary'));

        $newUserUri = $this->getRouter()->generate('user_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer un utilisateur', $newUserButton->text());

        // Image
        $this->assertNotEmpty($crawler->filter('img.slide-image'));

        // Form
        $this->assertNotEmpty($form = $crawler->filter('form'));
        $checkLoginUri = $this->getRouter()->generate('login_check');

        $this->assertEquals($checkLoginUri, $form->attr('action'));
        $this->assertNotEmpty($form->filter('input[type=text]#username'));
        $this->assertNotEmpty($form->filter('input[type=password]#password'));
        $this->assertNotEmpty($submitBtn = $form->filter('button.btn.btn-success[type=submit]'));
        $this->assertEquals('Se connecter', $submitBtn->text());
    }

    public function testSecurityPOSTLogin()
    {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/login');

        // User
        $user = $this->createAuthorizedUser();

        // Form
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $user->getUsername(),
            '_password' => 'todolist',
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $crawler = $this->client->followRedirect();

        $homepageUri = $this->getRouter()->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertEquals($homepageUri, $crawler->getUri());
    }

    public function testSecurityPOSTLoginWithErrors()
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/login');

        // User
        $this->createAuthorizedUser();

        // Form
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => '',
            '_password' => '',
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $crawler = $this->client->followRedirect();

        // Errors
        $credentialsError = $crawler->filter('div.alert.alert-danger')->text();
        $this->assertSame($credentialsError, 'Invalid credentials.');
    }

    public function getUnauthorizedActions(): array
    {
        return [
            ['/logout'],
        ];
    }
}
