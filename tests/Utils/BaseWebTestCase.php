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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

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

    protected function unauthorizedAction($uri)
    {
        // Request
        $this->client->request(Request::METHOD_GET, $uri);

        $loginUrl = $this->getRouter()->generate('login', [], 0);

        // Response
        $this->assertTrue($this->client->getResponse()->isRedirect($loginUrl));
        $this->assertResponseRedirects($loginUrl);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function notFound404Exception($uri)
    {
        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $this->client->request(Request::METHOD_GET, $uri);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function purgeDatabase()
    {
        $purger = new ORMPurger($this->getEntityManager());
        $purger->purge();
    }

    protected function createAuthorizedUser(): User
    {
        return UserFactory::new()
            ->withAttributes([
                'username' => 'user_username',
                'email' => 'user_username@todolist.fr',
                'password' => 'todolist'
            ])
            ->create()
            ->object();
    }

    protected function createAuthorizedUserAndLogin(): void
    {
        $user = $this->createAuthorizedUser();

        $this->client->loginUser($user);
    }
}
