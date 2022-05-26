<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Utils\BaseWebTestCase;

class UserControllerTest extends BaseWebTestCase
{
    public function testUserGETList()
    {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users');

        // Response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // New User button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-primary'));

        $newUserUri = $this->getRouter()->generate('user_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer un utilisateur', $newUserButton->text());

        // Main Title
        $this->assertNotEmpty($h1 = $crawler->filter('h1'));
        $this->assertEquals('Liste des utilisateurs', $h1->text());
    }

    public function testUserGETCreate()
    {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users/create');

        // Response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // New User button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-primary'));

        $newUserUri = $this->getRouter()->generate('user_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer un utilisateur', $newUserButton->text());

        // Main Title
        $this->assertNotEmpty($h1 = $crawler->filter('h1'));
        $this->assertEquals('Créer un utilisateur', $h1->text());

        // Form
        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($newUserUri, $form->attr('action'));
        $this->assertNotEmpty($form->filter('input[type=text]#user_username'));
        $this->assertNotEmpty($form->filter('input[type=password]#user_password_first'));
        $this->assertNotEmpty($form->filter('input[type=password]#user_password_second'));
        $this->assertNotEmpty($form->filter('input[type=email]#user_email'));

        // Submit button
        $this->assertNotEmpty($submitBtn = $form->filter('button.btn.btn-success[type=submit]'));
        $this->assertEquals('Ajouter', $submitBtn->text());
    }

    public function testUserPOSTCreate()
    {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users/create');

        // Form
        $form = $crawler->selectButton('Ajouter')->form([
            'user[username]' => 'test',
            'user[password][first]' => 'todolist',
            'user[password][second]' => 'todolist',
            'user[email]' => 'test@totdolist.fr'
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $crawler = $this->client->followRedirect();

        // Success Message
        $successMessage = $crawler->filter('div.alert.alert-success')->text();
        $this->assertContains('Superbe ! L\'utilisateur a bien été ajouté.', $successMessage);

        // Created User
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $createdUser = $userRepository->findOneBy(['username' => 'test']);
        $this->assertNotEmpty($createdUser, 'user created not found');
        $userPasswodHasher = $this->getPasswordHasher();
        $this->assertTrue($userPasswodHasher->isPasswordValid($createdUser, 'todolist'));
    }

    public function testUserPOSTCreateWithErrors()
    {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users/create');

        // Form
        $form = $crawler->selectButton('Ajouter')->form([
            'user[username]' => null,
            'user[password][first]' => 'todolist',
            'user[password][second]' => 'todolist',
            'user[email]' => null
        ]);
        $crawler = $this->client->submit($form);

        // Errors
        $usernameError = $crawler->filter('.help-block')->first()->text();
        $this->assertContains('Vous devez saisir un nom d\'utilisateur.', $usernameError);
        $emailError = $crawler->filter('.help-block')->last()->text();
        $this->assertContains('Vous devez saisir une adresse email.', $emailError);
    }

    public function testUserGETEdit()
    {
        // User
        $user = $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/users/%d/edit', $user->getId()));

        // Response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

//        // New User button
//        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-primary'));
//
//        $newUserUri = $this->getRouter()->generate('user_create');
//
//        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
//        $this->assertEquals('Créer un utilisateur', $newUserButton->text());

        // Main Title
        $this->assertNotEmpty($h1 = $crawler->filter('h1'));
        $this->assertEquals(sprintf('Modifier %s', $user->getUsername()), $h1->text());

        // Form
        $updateUserUri = $this->getRouter()->generate('user_edit', ['id' => $user->getId()]);

        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($updateUserUri, $form->attr('action'));

        $this->assertNotEmpty($username = $form->filter('input[type=text]#user_username'));
        $this->assertSame('user_username', $username->attr('value'));

        $this->assertNotEmpty($password_first = $form->filter('input[type=password]#user_password_first'));
        $this->assertNotEmpty($password_second = $form->filter('input[type=password]#user_password_second'));
        $this->assertEmpty($password_first->attr('value'));
        $this->assertEmpty($password_second->attr('value'));

        $this->assertNotEmpty($email = $form->filter('input[type=email]#user_email'));
        $this->assertSame('user_username@todolist.fr', $email->attr('value'));

        $this->assertNotEmpty($submitBtn = $form->filter('button.btn.btn-success[type=submit]'));
        $this->assertEquals('Modifier', $submitBtn->text());
    }

    public function testUserPOSTEdit()
    {
        // Initial User
        $user = $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/users/%d/edit', $user->getId()));

        // Form
        $form = $crawler->selectButton('Modifier')->form([
            'user[username]' => 'user_username_updated',
            'user[password][first]' => 'user_password_updated',
            'user[password][second]' => 'user_password_updated',
            'user[email]' => 'user_email_updated@todolist.fr'
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $crawler = $this->client->followRedirect();

        // Success Message
        $successMessage = $crawler->filter('div.alert.alert-success')->text();
        $this->assertContains('Superbe ! L\'utilisateur a bien été modifié', $successMessage);

        // Updated User
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $updatedUser = $userRepository->findOneBy(['id' => $user->getId()]);
        $this->assertNotEmpty($updatedUser, 'user updated not found');
        $userPasswodHasher = $this->getPasswordHasher();
        $this->assertTrue($userPasswodHasher->isPasswordValid($updatedUser, 'user_password_updated'));
    }

    public function testUserPOSTEditWithErrors()
    {
        // Initial User
        $user = $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_POST, sprintf('/users/%d/edit', $user->getId()));

        // Form
        $form = $crawler->selectButton('Modifier')->form([
            'user[username]' => null,
            'user[password][first]' => 'todolist_updated',
            'user[password][second]' => 'todolist_updated',
            'user[email]' => null
        ]);
        $crawler = $this->client->submit($form);

        // Errors
        $usernameError = $crawler->filter('.help-block')->first()->text();
        $this->assertContains('Vous devez saisir un nom d\'utilisateur.', $usernameError);
        $emailError = $crawler->filter('.help-block')->last()->text();
        $this->assertContains('Vous devez saisir une adresse email.', $emailError);
    }

    /**
     * @dataProvider getNotFoundActions()
     */
    public function testUser404Exception($uri)
    {
        $this->notFound404Exception($uri);
    }

    public function getNotFoundActions()
    {
        return [
            ['/users/fake/edit'],
        ];
    }
}
