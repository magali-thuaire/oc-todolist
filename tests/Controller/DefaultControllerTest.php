<?php

namespace App\Tests\Controller;

use App\Tests\Utils\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends BaseWebTestCase
{
    /**
     * @dataProvider getUnauthorizedActions
     */
    public function testUnauthorizedAction(string $method, string $uri)
    {
        $this->unauthorizedAction($method, $uri);
    }

    public function testDefaultGETHomepageAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString('Accueil', $title->text());

        // Header navigation
        $navbarNav = $crawler->filter('.navbar-nav');

        // Header navigation - Homepage
        $homepage = $navbarNav->filter('li:nth-child(1)');
        $this->assertEquals('Accueil', $homepage->text());

        $homepageUri = $this->getRouter()->generate('homepage');
        $this->assertEquals($homepageUri, $homepage->filter('a')->attr('href'));

        // Header navigation - Tasks
        $tasks = $navbarNav->filter('li:nth-child(2)');
        $this->assertEquals('Tâches', $tasks->filter('a')->text());

        // Header navigation - Tasks - Undone
        $undoneTasks = $tasks->filter('ul>li:nth-child(1)');
        $undoneTasksUri = $this->getRouter()->generate('task_list');
        $this->assertEquals($undoneTasksUri, $undoneTasks->filter('a')->attr('href'));

        // Header navigation - Tasks - Done
        $doneTasks = $tasks->filter('ul>li:nth-child(2)');
        $doneTasksUri = $this->getRouter()->generate('task_list_done');
        $this->assertEquals($doneTasksUri, $doneTasks->filter('a')->attr('href'));

        // Header navigation - Logout
        $logout = $navbarNav->filter('li:nth-child(3)');
        $this->assertEquals('Se déconnecter', $logout->text());

        $logoutUri = $this->getRouter()->generate('logout');
        $this->assertEquals($logoutUri, $logout->filter('a')->attr('href'));

        // Main Title
        $this->assertSelectorTextSame('h1', 'Bienvenue sur Todo List, l\'application vous permettant de gérer l\'ensemble de vos tâches sans effort !');

        // New task button
        $newTaskButton = $crawler->filter('a.btn.btn-success')->attr('href');
        $newTaskUri = $this->getRouter()->generate('task_create');
        $this->assertEquals($newTaskButton, $newTaskUri);

        // List undone tasks button
        $listUndoneTasksButton = $crawler->filter('a.btn.btn-info')->last()->attr('href');
        $listUndoneTasksUri = $this->getRouter()->generate('task_list');
        $this->assertEquals($listUndoneTasksButton, $listUndoneTasksUri);

        // List done tasks button
        $listDoneTasksButton = $crawler->filter('a.btn.btn-secondary')->attr('href');
        $listDoneTasksUri = $this->getRouter()->generate('task_list_done');
        $this->assertEquals($listDoneTasksButton, $listDoneTasksUri);
    }

    public function testDefaultGETHomepageAdminAuthorized()
    {
        // Logged User
        $this->createAdminUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Header navigation
        $navbarNav = $crawler->filter('.navbar-nav');

        // Header navigation - Users
        $homepage = $navbarNav->filter('li:nth-child(3)');
        $this->assertEquals('Utilisateurs', $homepage->text());
    }

    private function getUnauthorizedActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/'],
        ];
    }
}
