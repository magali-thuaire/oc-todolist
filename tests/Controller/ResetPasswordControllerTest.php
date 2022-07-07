<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Utils\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordControllerTest extends BaseWebTestCase
{
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

    /**
     * @dataProvider getNotFoundActions()
     */
    public function testUser404Exception(string $method, string $uri): void
    {
        $this->createAdminUserAndLogin();

        $this->notFound404Exception($method, $uri);
    }

    public function testResetPasswordGETSendEmail(): void
    {
        $this->createAdminUserAndLogin();

        $user = $this->createUser();

        // Request
        $this->client->request(
            Request::METHOD_GET,
            sprintf('/reset-password/send-email/%d', $user->getId())
        );

        // Redirection
        $userEditUri = $this->getRouter()->generate('user_edit', ['id' => $user->getId()]);
        $this->assertResponseRedirects($userEditUri);
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans('user.reset-password.success', ['user.email' => $user->getEmail()], 'flashes')
        );
    }

    public function testResetPasswordPOSTReset(): void
    {
        $this->createAdminUserAndLogin();

        $user = $this->createUser();
        $newUser = $this->getEntityManager()->getRepository(User::class)->findOneBy(['id' => $user->getId()]);
        $token = $this->getResetPasswordHelper()->generateResetToken($newUser);

        // Logout Admin
        $this->logoutUser();

        // Request
        $crawler = $this->client->request(
            Request::METHOD_GET,
            sprintf('/reset-password/reset/%s', $token->getToken())
        );

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString('Réinitialisation de votre mot de passe', $title->text());

        // Main Title
        $this->assertSelectorTextSame('h1', 'Réinitialisation de votre mot de passe');

        // Form
        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertNull($form->attr('action'));

        $this->assertSelectorExists('input[type=password]#change_password_form_plainPassword_first');
        $this->assertSelectorExists('input[type=password]#change_password_form_plainPassword_second');

        $this->assertInputValueSame('change_password_form[plainPassword][first]', '');
        $this->assertInputValueSame('change_password_form[plainPassword][second]', '');

        // Submit button
        $this->assertSelectorTextSame('button.btn.btn-primary[type=submit]', 'Réinitialiser');

        // Submit form
        $plainPassword = 'todolist';
        $form = $crawler->selectButton('Réinitialiser')->form([
            'change_password_form[plainPassword][first]' => $plainPassword,
            'change_password_form[plainPassword][second]' => $plainPassword,
        ]);
        $this->client->submit($form);

        // Redirection
        $loginUri = $this->getRouter()->generate('login');
        $this->assertResponseRedirects($loginUri);
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans('user.change-password.success', [], 'flashes')
        );

        // Updated Password User
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $updatedUser = $userRepository->findOneBy(['id' => $user->getId()]);
        $this->assertTrue($this->getPasswordHasher()->isPasswordValid($updatedUser, $plainPassword));
    }

    /**
     * @dataProvider getPasswordValidationErrors()
     */
    public function testResetPasswordPOSTResetWithErrorsPassword(
        ?string $firstPasswordValue,
        ?string $secondPasswordValue,
        string $idValidationMessage
    ): void {
        $this->createAdminUserAndLogin();

        $user = $this->createUser();
        $newUser = $this->getEntityManager()->getRepository(User::class)->findOneBy(['id' => $user->getId()]);
        $token = $this->getResetPasswordHelper()->generateResetToken($newUser);

        // Logout Admin
        $this->logoutUser();

        // Request
        $crawler = $this->client->request(
            Request::METHOD_GET,
            sprintf('/reset-password/reset/%s', $token->getToken())
        );

        // Submit form
        $form = $crawler->selectButton('Réinitialiser')->form([
            'change_password_form[plainPassword][first]' => $firstPasswordValue,
            'change_password_form[plainPassword][second]' => $secondPasswordValue,
        ]);
        $crawler = $this->client->submit($form);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Errors
        $passwordError = $crawler->filter('#change_password_form_plainPassword_first')->siblings()->filter('.help-block')->text();
        $this->assertEquals($this->getValidationMessage($idValidationMessage), $passwordError);
    }

    /**
     * @return array<array<int, string>>
     */
    public function getForbiddenActionsWithExistingUser(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/reset-password/send-email/%d'],
        ];
    }

    /**
     * @return array<array<int, string>>
     */
    public function getNotFoundActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/reset-password/send-email/fake'],
        ];
    }

    /**
     * @return array<int, array<int, ?string>>
     */
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
