<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Utils\BaseWebTestCase;

class UserControllerTest extends BaseWebTestCase
{
    public function testUserGETList()
    {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // New User button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-primary'));

        $newUserUri = $this->getRouter()->generate('user_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer un utilisateur', $newUserButton->text());

        // Main Title
        $this->assertSelectorTextSame('h1', 'Liste des utilisateurs');
    }

    public function testUserGETCreate()
    {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users/create');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // New User button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-primary'));

        $newUserUri = $this->getRouter()->generate('user_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer un utilisateur', $newUserButton->text());

        // Main Title
        $this->assertSelectorTextSame('h1', 'Créer un utilisateur');

        // Form
        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($newUserUri, $form->attr('action'));
        $this->assertSelectorExists('input[type=text]#user_username');
        $this->assertSelectorExists('input[type=password]#user_plainPassword_first');
        $this->assertSelectorExists('input[type=password]#user_plainPassword_second');
        $this->assertSelectorExists('input[type=email]#user_email');

        // Submit button
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Ajouter');
    }

    public function testUserPOSTCreate()
    {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users/create');

        // Form
        $form = $crawler->selectButton('Ajouter')->form([
            'user[username]' => 'test',
            'user[plainPassword][first]' => 'todolist',
            'user[plainPassword][second]' => 'todolist',
            'user[email]' => 'test@totdolist.fr'
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextSame(
            'div.alert.alert-success',
            'Superbe ! L\'utilisateur a bien été ajouté.'
        );

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
            'user[plainPassword][first]' => 'todolist',
            'user[plainPassword][second]' => 'todolist',
            'user[email]' => null
        ]);
        $crawler = $this->client->submit($form);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Errors
        $usernameError = $crawler->filter('.help-block')->first()->text();
        $this->assertEquals('Vous devez saisir un nom d\'utilisateur.', $usernameError);
        $emailError = $crawler->filter('.help-block')->last()->text();
        $this->assertEquals('Vous devez saisir une adresse email.', $emailError);
    }

    public function testUserGETEdit()
    {
        // User
        $user = $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/users/%d/edit', $user->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

//        // New User button
//        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-primary'));
//
//        $newUserUri = $this->getRouter()->generate('user_create');
//
//        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
//        $this->assertEquals('Créer un utilisateur', $newUserButton->text());

        // Main Title
        $this->assertSelectorTextSame('h1', sprintf('Modifier %s', $user->getUsername()));

        // Form
        $updateUserUri = $this->getRouter()->generate('user_edit', ['id' => $user->getId()]);

        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($updateUserUri, $form->attr('action'));

        $this->assertSelectorExists('input[type=text]#user_username');
        $this->assertSelectorExists('input[type=password]#user_plainPassword_first');
        $this->assertSelectorExists('input[type=password]#user_plainPassword_second');

        // Submit button
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Modifier');
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
            'user[plainPassword][first]' => 'user_password_updated',
            'user[plainPassword][second]' => 'user_password_updated',
            'user[email]' => 'user_email_updated@todolist.fr'
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextSame(
            'div.alert.alert-success',
            'Superbe ! L\'utilisateur a bien été modifié'
        );

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
            'user[plainPassword][first]' => 'todolist_updated',
            'user[plainPassword][second]' => 'todolist_updated',
            'user[email]' => null
        ]);
        $crawler = $this->client->submit($form);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Errors
        $usernameError = $crawler->filter('.help-block')->first()->text();
        $this->assertEquals('Vous devez saisir un nom d\'utilisateur.', $usernameError);
        $emailError = $crawler->filter('.help-block')->last()->text();
        $this->assertEquals('Vous devez saisir une adresse email.', $emailError);
    }

    /**
     * @dataProvider getNotFoundActions()
     */
    public function testUser404Exception($uri)
    {
        $this->notFound404Exception($uri);
    }

    public function getNotFoundActions(): array
    {
        return [
            ['/users/fake/edit'],
        ];
    }
}
