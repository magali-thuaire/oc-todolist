<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Factory\UserFactory;
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
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-info'));

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
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-info'));

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
        $this->submitCreateForm($crawler, [
            'user[username]' => 'test',
            'user[plainPassword][first]' => 'todolist',
            'user[plainPassword][second]' => 'todolist',
            'user[email]' => 'test@totdolist.fr'
        ]);

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans('user.create.success', [], 'flashes')
        );

        // Created User
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $createdUser = $userRepository->findOneBy(['username' => 'test']);
        $this->assertNotEmpty($createdUser, 'user created not found');
        $userPasswodHasher = $this->getPasswordHasher();
        $this->assertTrue($userPasswodHasher->isPasswordValid($createdUser, 'todolist'));
    }

    /**
     * @dataProvider getValidationErrors()
     */
    public function testUserPOSTCreateWithErrors(
        string $fieldName,
        ?string $fieldValue,
        string $selector,
        string $idValidationMessage
    ) {

        // New user
        $this->createUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users/create');

        // Form
        $crawler = $this->submitCreateForm($crawler, [
            $fieldName => $fieldValue,
        ]);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Errors
        $fieldError = $crawler->filter($selector)->siblings()->filter('.help-block')->text();
        $this->assertEquals($this->getValidationMessage($idValidationMessage), $fieldError);
    }

    /**
     * @dataProvider getPasswordValidationErrors()
     */
    public function testUserPOSTCreateWithErrorsPassword(
        ?string $firstPasswordValue,
        ?string $secondPasswordValue,
        string $idValidationMessage
    ) {
        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users/create');

        // Form
        $crawler = $this->submitCreateForm($crawler, [
            'user[plainPassword][first]' => $firstPasswordValue,
            'user[plainPassword][second]' => $secondPasswordValue,
        ]);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Errors
        $passwordError = $crawler->filter('#user_plainPassword_first')->siblings()->filter('.help-block')->text();
        $this->assertEquals($this->getValidationMessage($idValidationMessage), $passwordError);
    }

    public function testUserGETEdit()
    {
        // User
        $user = $this->createUser();

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
        $user = $this->createUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/users/%d/edit', $user->getId()));

        // Form
        $this->submitEditForm($crawler, [
            'user[username]' => 'user_username_updated',
            'user[plainPassword][first]' => 'user_password_updated',
            'user[plainPassword][second]' => 'user_password_updated',
            'user[email]' => 'user_email_updated@todolist.fr'
        ]);

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans('user.edit.success', [], 'flashes')
        );

        // Updated User
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $updatedUser = $userRepository->findOneBy(['id' => $user->getId()]);
        $this->assertNotEmpty($updatedUser, 'user updated not found');
        $userPasswodHasher = $this->getPasswordHasher();
        $this->assertTrue($userPasswodHasher->isPasswordValid($updatedUser, 'user_password_updated'));
    }

    /**
     * @dataProvider getValidationErrors()
     */
    public function testUserPOSTEditWithErrors(
        string $fieldName,
        ?string $fieldValue,
        string $selector,
        string $idValidationMessage
    ) {
        // Initial User
        $this->createUser();
        $updatedUser = UserFactory::createOne();

        // Request
        $crawler = $this->client->request(Request::METHOD_POST, sprintf('/users/%d/edit', $updatedUser->getId()));

        // Form
        $crawler = $this->submitEditForm($crawler, [
            $fieldName => $fieldValue,
        ]);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Errors
        $fieldError = $crawler->filter($selector)->siblings()->filter('.help-block')->text();
        $this->assertEquals($this->getValidationMessage($idValidationMessage), $fieldError);
    }

    /**
     * @dataProvider getPasswordValidationErrors()
     */
    public function testUserPOSTEditWithErrorsPassword(
        ?string $firstPasswordValue,
        ?string $secondPasswordValue,
        string $idValidationMessage
    ) {
        // Initial User
        $user = $this->createUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_POST, sprintf('/users/%d/edit', $user->getId()));

        // Form
        $crawler = $this->submitEditForm($crawler, [
            'user[plainPassword][first]' => $firstPasswordValue,
            'user[plainPassword][second]' => $secondPasswordValue,
        ]);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Errors
        $passwordError = $crawler->filter('#user_plainPassword_first')->siblings()->filter('.help-block')->text();
        $this->assertEquals($this->getValidationMessage($idValidationMessage), $passwordError);
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

    public function getValidationErrors(): array
    {
        $usernameField = 'user[username]';
        $usernameSelector = 'input[type=text]#user_username';
        $emailField = 'user[email]';
        $emailSelector = 'input[type=email]#user_email';

        // fieldName, fieldValue, selector, idValidationMessage
        return [
            // Username not blank
            [
                $usernameField,
                null,
                $usernameSelector,
                'user.username.not_blank'
            ],
            // Username max length
            [
                $usernameField,
                str_repeat('a', 26),
                $usernameSelector,
                'user.username.max'
            ],
            // Username unique
            [
                $usernameField,
                'user_username',
                $usernameSelector,
                'user.username.unique'
            ],
            // Email not blank
            [
                $emailField,
                null,
                $emailSelector,
                'user.email.not_blank'
            ],
            // Email max length
            [
                $emailField,
                sprintf("%s@%s.fr", str_repeat('e', 30), str_repeat('e', 30)),
                $emailSelector,
                'user.email.max'
            ],
            // Email unique
            [
                $emailField,
                'user_username@todolist.fr',
                $emailSelector,
                'user.email.unique'
            ],
        ];
    }

    public function getPasswordValidationErrors(): array
    {
        // firstPasswordValue, secondPasswordValue, idValidationMessage
        return [
            // Password not blank
            [null, null, 'user.password.not_blank'],
            // Password identical
            ['password', 'password_not_same', 'user.password.fields'],
            // Password max length
            [str_repeat('a', 65), str_repeat('a', 65), 'user.password.max'],
        ];
    }
}
