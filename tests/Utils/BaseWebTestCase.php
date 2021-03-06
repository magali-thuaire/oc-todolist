<?php

namespace App\Tests\Utils;

use App\Entity\User;
use App\Factory\UserFactory;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class BaseWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->purgeDatabase();
    }

    protected function getRouter(): ?Router
    {
        return $this->client->getContainer()->get('router');
    }

    protected function getDoctrine(): ?Registry
    {
        return $this->client->getContainer()->get('doctrine');
    }

    protected function getEntityManager(): ?EntityManager
    {
        return $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function getPasswordHasher(): ?UserPasswordHasher
    {
        return $this->client->getContainer()->get('security.user_password_hasher');
    }

    protected function getResetPasswordHelper(): ?ResetPasswordHelperInterface
    {
        return $this->client->getContainer()->get('symfonycasts.reset_password.helper');
    }

    protected function getTranslator(): ?TranslatorInterface
    {
        return $this->client->getContainer()->get('translator');
    }

    protected function getValidationMessage(string $id, array $params = []): ?string
    {
        return $this->getTranslator()->trans($id, $params, 'validators');
    }

    protected function unauthorizedAction(string $method, string $uri)
    {
        // Request
        $this->client->request($method, $uri);

        $loginUrl = $this->getRouter()->generate('login', [], 0);

        // Response
        $this->assertResponseRedirects($loginUrl);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    protected function forbiddenAction(string $method, string $uri)
    {
        // Request
        $this->client->request($method, $uri);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    protected function notFound404Exception(string $method, string $uri)
    {
        // Logged User
        $this->createUserAndLogin();

        // Request
        $this->client->request($method, $uri);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    protected function createUser(): User
    {
        return UserFactory::createOne([
                'username' => 'user_username',
                'email' => 'user_username@todolist.fr',
                'plainPassword' => 'todolist'
            ])
            ->object();
    }

    protected function createAdminUserAndLogin(): User
    {
        $user = UserFactory::new([
                'username' => 'admin_username',
                'email' => 'admin_username@todolist.fr',
                'plainPassword' => 'todolist'
            ])
            ->promoteRole('ROLE_ADMIN')
            ->create()
            ->object();

        $this->client->loginUser($user);

        return $user;
    }

    protected function createUserAndLogin(): User
    {
        $user = $this->createUser();

        $this->client->loginUser($user);

        return $user;
    }

    protected function logoutUser(): void
    {
        $this->client->request(Request::METHOD_GET, '/logout');
    }

    protected function submitCreateForm(Crawler $crawler, array $fields): Crawler
    {
        $form = $crawler->selectButton('Ajouter')->form($fields);

        return $this->client->submit($form);
    }

    protected function submitEditForm(Crawler $crawler, array $fields): Crawler
    {
        $form = $crawler->selectButton('Modifier')->form($fields);

        return $this->client->submit($form);
    }

    private function purgeDatabase()
    {
        $purger = new ORMPurger($this->getEntityManager());
        $purger->purge();
    }
}
