<?php

namespace App\Tests\Utils;

use App\Entity\User;
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

//        $loginUrl = $this->getRouter()->generate('login', [], 0);

        // Response
//        $this->assertTrue($response->isRedirect($loginUrl));
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function notFound404Exception($uri)
    {
        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $this->client->request(Request::METHOD_GET, $uri);

        // Response
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    private function purgeDatabase()
    {
        $purger = new ORMPurger($this->getEntityManager());
        $purger->purge();
    }

    protected function createAuthorizedUser(): User
    {
        $user = new User();
        $user->setUsername('user_username');
        $user->setEmail('user_username@todolist.fr');
        $plainPassword = 'todolist';
        $user->setPassword($this->getPasswordHasher()->hashPassword($user, $plainPassword));

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        return $user;
    }

    protected function createAuthorizedUserAndLogin(): void
    {
        $user = $this->createAuthorizedUser();

        $this->client->loginUser($user);
    }
}
