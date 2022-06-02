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
    public function testUnauthorizedAction($uri)
    {
        $this->unauthorizedAction($uri);
    }

    public function testDefaulGETHomepageAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // New User button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-info'));

        $newUserUri = $this->getRouter()->generate('user_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer un utilisateur', $newUserButton->text());

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

    public function getUnauthorizedActions(): array
    {
        return [
            ['/'],
        ];
    }
}
