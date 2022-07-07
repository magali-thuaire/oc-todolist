<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use App\Tests\Utils\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends BaseWebTestCase
{
    /**
     * @dataProvider getForbiddenActions
     */
    public function testForbiddenAction(string $method, string $uri): void
    {
        $this->createUserAndLogin();

        $this->forbiddenAction($method, $uri);
    }

    /**
     * @dataProvider getForbiddenActionsWithExistingUser
     */
    public function testForbiddenActionWithExistingUser(string $method, string $uri): void
    {
        $this->createUserAndLogin();

        // User
        $user = UserFactory::createOne();

        // Request
        $this->client->request($method, sprintf($uri, $user->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUserGETList(): void
    {
        $this->createAdminUserAndLogin();

        UserFactory::createMany(10);

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString('Liste des utilisateurs', $title->text());

        // New User button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-info'));

        $newUserUri = $this->getRouter()->generate('user_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer un nouvel utilisateur', $newUserButton->text());

        // Main Title
        $this->assertSelectorTextSame('h1', 'Liste des utilisateurs');

        // Table - Head
        $idColumn = $crawler->filter('table > thead > tr > th:nth-child(1)')->text();
        $this->assertEquals('#', $idColumn);
        $usernameColumn = $crawler->filter('table > thead > tr > th:nth-child(2)')->text();
        $this->assertEquals('Nom d\'utilisateur', $usernameColumn);
        $emailColumn = $crawler->filter('table > thead > tr > th:nth-child(3)')->text();
        $this->assertEquals('Adresse d\'utilisateur', $emailColumn);
        $roleColumn = $crawler->filter('table > thead > tr > th:nth-child(4)')->text();
        $this->assertEquals('Rôle', $roleColumn);
        $actionColumn = $crawler->filter('table > thead > tr > th:nth-child(5)')->text();
        $this->assertEquals('Actions', $actionColumn);

        // Table - Body
        $nbUsers = $crawler->filter('table > tbody')->children()->count();
        $this->assertEquals(11, $nbUsers);
        // Table - Body Second line
        $secondUser = $crawler->filter('table > tbody > tr:nth-child(2)');
        $userId = $secondUser->filter('th')->text();
        // Table - Body Second line - Edit Button
        $editUserUri = $this->getRouter()->generate('user_edit', ['id' => $userId]);
        $userAction = $secondUser->filter('td:last-child');
        $editAction = $userAction->filter('a:first-child');
        $this->assertEquals($editUserUri, $editAction->attr('href'));
        // Table - Body Second line - Delete Button
        $deleteUserUri = $this->getRouter()->generate('user_confirm_delete', ['id' => $userId]);
        $deleteAction = $userAction->filter('a:last-child');
        $this->assertEquals($deleteUserUri, $deleteAction->attr('data-href'));
    }

    public function testUserGETCreate(): void
    {
        $this->createAdminUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users/create');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString('Nouvel utilisateur', $title->text());

        // Main Title
        $this->assertSelectorTextSame('h1', 'Créer un nouvel utilisateur');

        // Form
        $newUserUri = $this->getRouter()->generate('user_create');

        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($newUserUri, $form->attr('action'));
        $this->assertSelectorExists('input[type=text]#user_username');
        $this->assertSelectorExists('input[type=email]#user_email');
        $this->assertSelectorExists('select#user_role');
        $userRoleSelected = $crawler->filter('select#user_role')->filter('option[value=ROLE_USER]')->attr('selected');
        $this->assertEquals('selected', $userRoleSelected);

        // Return button
        $this->assertSelectorTextSame('a.btn.btn-primary', 'Retour à la liste des utilisateurs');

        // Submit button
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Ajouter');
    }

    /**
     * @dataProvider getRoleUserOrRoleAdmin()
     */
    public function testUserPOSTCreate(string $role): void
    {
        $this->createAdminUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/users/create');

        // Form
        $this->submitCreateForm($crawler, [
            'user[username]' => 'test',
            'user[email]' => 'test@totdolist.fr',
            'user[role]' => $role,
        ]);

        // Redirection
        $userListUri = $this->getRouter()->generate('user_list');
        $this->assertResponseRedirects($userListUri);
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
        $this->assertContains($role, $createdUser->getRoles());
    }

    /**
     * @dataProvider getValidationErrors()
     */
    public function testUserPOSTCreateWithErrors(
        string $fieldName,
        ?string $fieldValue,
        string $selector,
        string $idValidationMessage
    ): void {
        $this->createAdminUserAndLogin();

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

    public function testUserGETEdit(): void
    {
        $this->createAdminUserAndLogin();

        // User
        $user = $this->createUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/users/%d/edit', $user->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString(sprintf('Modification de %s', $user->getUsername()), $title->text());

        // Main Title
        $this->assertSelectorTextSame('h1', sprintf('Modifier %s', $user->getUsername()));

        // CreatedAt date
        $this->assertSelectorTextSame(
            '.created_at',
            sprintf(
                'Crée le %s',
                $user->getCreatedAt()->format('d/m/Y')
            )
        );

        // UpdatedAt date
        $this->assertSelectorTextSame(
            '.updated_at',
            sprintf(
                'Dernière mise à jour le %s',
                $user->getUpdatedAt()->format('d/m/Y H:m:s')
            )
        );

        // Form
        $updateUserUri = $this->getRouter()->generate('user_edit', ['id' => $user->getId()]);

        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($updateUserUri, $form->attr('action'));

        $this->assertInputValueSame('user[username]', $user->getUsername());
        $this->assertInputValueSame('user[email]', $user->getEmail());
        $resetPasswordLink = $crawler->filter('.reset-password__link');
        $resetPasswordUri = $this->getRouter()->generate('app_reset_password_email', ['id' => $user->getId()]);
        $this->assertEquals('Envoyer un lien de réinitialisation de mot de passe', $resetPasswordLink->text());
        $this->assertEquals($resetPasswordUri, $resetPasswordLink->attr('href'));

        $userRoleSelected = $crawler->filter('select#user_role')->filter('option[value=ROLE_USER]')->attr('selected');
        $this->assertEquals('selected', $userRoleSelected);

        // Return button
        $this->assertSelectorTextSame('a.btn.btn-primary', 'Retour à la liste des utilisateurs');

        // Submit button
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Modifier');
    }

    /**
     * @dataProvider getRoleUserOrRoleAdmin()
     */
    public function testUserPOSTEdit(string $role): void
    {
        $this->createAdminUserAndLogin();

        // Initial User
        $user = $this->createUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/users/%d/edit', $user->getId()));

        // Form
        $this->submitEditForm($crawler, [
            'user[username]' => 'user_username_updated',
            'user[email]' => 'user_email_updated@todolist.fr',
            'user[role]' => $role,
        ]);

        // Redirection
        $userListUri = $this->getRouter()->generate('user_list');
        $this->assertResponseRedirects($userListUri);
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
        $this->assertContains($role, $updatedUser->getRoles());
    }

    /**
     * @dataProvider getValidationErrors()
     */
    public function testUserPOSTEditWithErrors(
        string $fieldName,
        ?string $fieldValue,
        string $selector,
        string $idValidationMessage
    ): void {
        $this->createAdminUserAndLogin();

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

    public function testUserPOSTDelete(): void
    {
        $this->createAdminUserAndLogin();

        // Initial User
        $user = $this->createUser();
        $userId = $user->getId();
        TaskFactory::createMany(5);

        // Request confirm_delete
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/users/%d/confirm-delete', $user->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Modal
        $modal = $crawler->filter('#user__modal__delete');

        // Modal header
        $modalHeader = $modal->filter('.modal-header');
        $this->assertEquals(
            sprintf(
                '%s - %s - Utilisateur créé le %s',
                $user->getUsername(),
                $user->getEmail(),
                $user->getCreatedAt()->format('d/m/Y')
            ),
            $modalHeader->text()
        );

        // Modal body
        $modalBody = $modal->filter('.modal-body');
        $this->assertEquals(
            'Etes-vous certain(e) de vouloir supprimer cet utilisateur ?',
            $modalBody->text()
        );

        // Modal Form Action --> request to '/users/id/delete'
        $deleteUserUri = $this->getRouter()->generate('user_delete', ['id' => $user->getId()]);
        $formAction = $crawler->filter('form')->attr('action');
        $this->assertEquals($deleteUserUri, $formAction);

        // Submit form
        $form = $crawler->selectButton('Oui')->form();
        $this->client->submit($form);

        // Redirection
        $userListUri = $this->getRouter()->generate('user_list');
        $this->assertResponseRedirects($userListUri);
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans('user.delete.success', [], 'flashes')
        );

        // Deleted User
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $deletedUser = $userRepository->findOneBy(['id' => $userId]);
        $this->assertNull($deletedUser);
    }

    /**
     * @dataProvider getNotFoundActions()
     */
    public function testUser404Exception(string $method, string $uri): void
    {
        $this->createAdminUserAndLogin();

        $this->notFound404Exception($method, $uri);
    }

    /**
     * @return array<array<int, string>>
     */
    public function getNotFoundActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/users/fake/edit'],
            [Request::METHOD_POST, '/users/fake/edit'],
            [Request::METHOD_GET, '/users/fake/confirm-delete'],
            [Request::METHOD_POST, '/users/fake/delete'],
        ];
    }

    /**
     * @return array<array<?string>>
     */
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
                'user.username.not_blank',
            ],
            // Username max length
            [
                $usernameField,
                str_repeat('a', 26),
                $usernameSelector,
                'user.username.max',
            ],
            // Username unique
            [
                $usernameField,
                'user_username',
                $usernameSelector,
                'user.username.unique',
            ],
            // Email not blank
            [
                $emailField,
                null,
                $emailSelector,
                'user.email.not_blank',
            ],
            // Email max length
            [
                $emailField,
                sprintf('%s@%s.fr', str_repeat('e', 30), str_repeat('e', 30)),
                $emailSelector,
                'user.email.max',
            ],
            // Email unique
            [
                $emailField,
                'user_username@todolist.fr',
                $emailSelector,
                'user.email.unique',
            ],
        ];
    }

    /**
     * @return array<array<int, string>>
     */
    public function getForbiddenActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/users'],
            [Request::METHOD_GET, '/users/create'],
            [Request::METHOD_POST, '/users/create'],
        ];
    }

    /**
     * @return array<array<int, string>>
     */
    public function getForbiddenActionsWithExistingUser(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/users/%d/edit'],
            [Request::METHOD_GET, '/users/%d/confirm-delete'],
            [Request::METHOD_POST, '/users/%d/delete'],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public function getRoleUserOrRoleAdmin(): array
    {
        return [
            ['ROLE_ADMIN'],
            ['ROLE_USER'],
        ];
    }
}
