<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Task;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Utils\BaseWebTestCase;

class TaskControllerTest extends BaseWebTestCase
{
    /**
     * @dataProvider getUnauthorizedActions
     */
    public function testUnauthorizedAction($uri)
    {
        $this->unauthorizedAction($uri);
    }

    public function testTaskGETListAuthorized()
    {
        // Tasks
        $tasksFixture = $this->createTask(5);
        $firstTaskFixture = current($tasksFixture);

        // Logged User
        $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks', [], [], [
            'PHP_AUTH_USER' => 'user_username',
            'PHP_AUTH_PW'   => 'todolist',
        ]);

        // Response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

//        // New User button
//        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-primary'));
//
//        $newUserUri = $this->getRouter()->generate('user_create');
//
//        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
//        $this->assertEquals('Créer un utilisateur', $newUserButton->text());
//
//        // Logout button
//        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-danger'));
//
//        $newUserUri = $this->getRouter()->generate('logout');
//
//        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
//        $this->assertEquals('Se déconnecter', $newUserButton->text());

        // New Task button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-info'));

        $newUserUri = $this->getRouter()->generate('task_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer une tâche', $newUserButton->text());

        // Tasks
        $nbTasks = $crawler->filter('div.thumbnail')->count();
        $this->assertEquals(5, $nbTasks);

        // First Task
        $firstTask = $crawler->filter('div.thumbnail')->first();

        // First Task - Edit
        $editTaskLink = $firstTask->filter('h4 > a');
        $editTaskUri = $this->getRouter()->generate('task_edit', ['id' => $firstTaskFixture->getId()]);

        $this->assertEquals($editTaskUri, $editTaskLink->attr('href'));
        $this->assertEquals($firstTaskFixture->getTitle(), $editTaskLink->text());

        // First Task - Delete
        $deleteTaskForm = $firstTask->filter('form')->last();
        $deleteTaskUri = $this->getRouter()->generate('task_delete', ['id' => $firstTaskFixture->getId()]);
        $deleteBtn = $firstTask->filter('button.btn.btn-danger');

        $this->assertEquals($deleteTaskUri, $deleteTaskForm->attr('action'));
        $this->assertEquals('Supprimer', $deleteBtn->text());

        // First Task - Toggle
        $toggleTaskForm = $firstTask->filter('form')->first();
        $toggleTaskUri = $this->getRouter()->generate('task_toggle', ['id' => $firstTaskFixture->getId()]);
        $toggleBtn = $firstTask->filter('button.btn.btn-success');

        $this->assertEquals($toggleTaskUri, $toggleTaskForm->attr('action'));
        if ($firstTaskFixture->isDone()) {
            $this->assertContains('Marquer non terminée', $toggleBtn->text());
        } else {
            $this->assertContains('Marquer comme faite', $toggleBtn->text());
        }
    }

    public function testTaskGETCreateAuthorized()
    {
        // Logged User
        $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create', [], [], [
            'PHP_AUTH_USER' => 'user_username',
            'PHP_AUTH_PW'   => 'todolist',
        ]);

        // Response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

//        // New User button
//        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-primary'));
//
//        $newUserUri = $this->urlGenerator->generate('user_create');
//
//        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
//        $this->assertEquals('Créer un utilisateur', $newUserButton->text());
//
//        // logout button
//        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-danger'));
//
//        $newUserUri = $this->urlGenerator->generate('logout');
//
//        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
//        $this->assertEquals('Se déconnecter', $newUserButton->text());

//        // Main Title
//        $this->assertNotEmpty($h1 = $crawler->filter('h1'));
//        $this->assertEquals('Créer une tâche', $h1->text());

        // Form
        $newTaskUri = $this->getRouter()->generate('task_create');

        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($newTaskUri, $form->attr('action'));
        $this->assertNotEmpty($form->filter('input[type=text]#task_title'));
        $this->assertNotEmpty($form->filter('textarea#task_content'));

        // Submit button
        $this->assertNotEmpty($submitBtn = $form->filter('button.btn.btn-success[type=submit]'));
        $this->assertEquals('Ajouter', $submitBtn->text());

        // Return button
        $this->assertNotEmpty($returnBtn = $crawler->filter('a.btn.btn-primary')->last());
        $this->assertEquals('Retour à la liste des tâches', $returnBtn->text());
    }

    public function testTaskPOSTCreateAuthorized()
    {
        // Logged User
        $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create', [], [], [
            'PHP_AUTH_USER' => 'user_username',
            'PHP_AUTH_PW'   => 'todolist',
        ]);

        // Form
        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]' => 'task_title',
            'task[content]' => 'task_content',
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $crawler = $this->client->followRedirect();

        // Success Message
        $successMessage = $crawler->filter('div.alert.alert-success')->text();
        $this->assertContains('Superbe ! La tâche a été bien été ajoutée.', $successMessage);

        // Created Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $createdTask = $taskRepository->findOneBy(['title' => 'task_title']);
        $this->assertNotEmpty($createdTask, 'task created not found');
        $this->assertEquals('task_content', $createdTask->getContent());
    }

    public function testTaskPOSTCreateAuthorizedWithErrors()
    {
        // Logged User
        $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create', [], [], [
            'PHP_AUTH_USER' => 'user_username',
            'PHP_AUTH_PW'   => 'todolist',
        ]);

        // Form
        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]' => '',
            'task[content]' => '',
        ]);
        $crawler = $this->client->submit($form);

        // Errors
        $titleError = $crawler->filter('.help-block')->first()->text();
        $this->assertContains('Vous devez saisir un titre.', $titleError);
        $contentError = $crawler->filter('.help-block')->last()->text();
        $this->assertContains('Vous devez saisir du contenu.', $contentError);
    }

    public function testTaskGETEditAuthorized()
    {
        // Initial Task
        $task = current($this->createTask());

        // Logged User
        $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()), [], [], [
            'PHP_AUTH_USER' => 'user_username',
            'PHP_AUTH_PW'   => 'todolist',
        ]);

        // Response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Form
        $updateTaskUri = $this->getRouter()->generate('task_edit', ['id' => $task->getId()]);

        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($updateTaskUri, $form->attr('action'));

        $this->assertNotEmpty($title = $form->filter('input[type=text]#task_title'));
        $this->assertSame('task_title_1', $title->attr('value'));

        $this->assertNotEmpty($content = $form->filter('textarea#task_content'));
        $this->assertSame('task_content_1', $content->text());

        $this->assertNotEmpty($submitBtn = $form->filter('button.btn.btn-success[type=submit]'));
        $this->assertEquals('Modifier', $submitBtn->text());
    }

    public function testTaskPOSTEditAuthorized()
    {
        // Initial Task
        $task = current($this->createTask());

        // Logged User
        $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()), [], [], [
            'PHP_AUTH_USER' => 'user_username',
            'PHP_AUTH_PW'   => 'todolist',
        ]);

        // Form
        $form = $crawler->selectButton('Modifier')->form([
            'task[title]' => 'task_title_updated',
            'task[content]' => 'task_content_updated',
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $crawler = $this->client->followRedirect();

        // Success Message
        $successMessage = $crawler->filter('div.alert.alert-success')->text();
        $this->assertContains('Superbe ! La tâche a bien été modifiée.', $successMessage);

        // Updated Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $updatedTask = $taskRepository->findOneBy(['id' => $task->getId()]);
        $this->assertNotEmpty($updatedTask, 'task updated not found');
        $this->assertEquals('task_title_updated', $updatedTask->getTitle());
        $this->assertEquals('task_content_updated', $updatedTask->getContent());
    }

    public function testTaskPOSTEditAuthorizedWithErrors()
    {
        // Initial Task
        $this->createTask();

        // Logged User
        $this->createAuthorizedUser();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create', [], [], [
            'PHP_AUTH_USER' => 'user_username',
            'PHP_AUTH_PW'   => 'todolist',
        ]);

        // Form
        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]' => '',
            'task[content]' => '',
        ]);
        $crawler = $this->client->submit($form);

        // Errors
        $titleError = $crawler->filter('.help-block')->first()->text();
        $this->assertContains('Vous devez saisir un titre.', $titleError);
        $contentError = $crawler->filter('.help-block')->last()->text();
        $this->assertContains('Vous devez saisir du contenu.', $contentError);
    }

    public function testTaskGETToggleAuthorized()
    {
        // Initial Task
        $task = current($this->createTask());
        $isDone = $task->isDone();

        // Logged User
        $this->createAuthorizedUser();

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/toggle', $task->getId()), [], [], [
            'PHP_AUTH_USER' => 'user_username',
            'PHP_AUTH_PW'   => 'todolist',
        ]);

        // Redirection
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $crawler = $this->client->followRedirect();

        if (!$task->isDone()) {
            $successMessage = $crawler->filter('div.alert.alert-success')->text();
            $this->assertContains(
                sprintf('Superbe ! La tâche %s a bien été marquée comme faite.', $task->getTitle()),
                $successMessage
            );
        }
//        else  {
//            $this->assertSelectorTextContains('div.alert.alert-success', sprintf('Superbe ! La tâche %s a bien été marquée comme non terminée.', $task->getTitle()));
//        }

        // Toggled Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $toggledTask = $taskRepository->findOneBy(['id' => $task->getId()]);
        $this->assertEquals(!$isDone, $toggledTask->isDone());
    }

    /**
     * @dataProvider getNotFoundActions()
     */
    public function testTask404Exception($uri)
    {
        $this->notFound404Exception($uri);
    }

    public function testTaskGETDeleteAuthorized()
    {
        // Initial Task
        $task = current($this->createTask());
        $taskId = $task->getId();

        // Logged User
        $this->createAuthorizedUser();

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/delete', $task->getId()), [], [], [
            'PHP_AUTH_USER' => 'user_username',
            'PHP_AUTH_PW'   => 'todolist',
        ]);

        // Redirection
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $crawler = $this->client->followRedirect();

        // Success Message
        $successMessage = $crawler->filter('div.alert.alert-success')->text();
        $this->assertContains('Superbe ! La tâche a bien été supprimée.', $successMessage);

        // Deleted Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $deletedTask = $taskRepository->findOneBy(['id' => $taskId]);
        $this->assertNull($deletedTask);
    }

    public function getUnauthorizedActions()
    {
        return [
            ['/tasks'],
            ['/tasks/create'],
            ['/tasks/fake/edit'],
            ['/tasks/fake/toggle'],
            ['/tasks/fake/delete'],
        ];
    }

    public function getNotFoundActions()
    {
        return [
            ['/tasks/fake/edit'],
            ['/tasks/fake/toggle'],
            ['/tasks/fake/delete'],
        ];
    }

    private function createTask($number = 1)
    {
        $tasks = [];
        for ($i = 1; $i <= $number; $i++) {
            $task = new Task();
            $task->setTitle('task_title_' . $i);
            $task->setContent('task_content_' . $i);
            $task->toggle(random_int(0, 1));

            $this->getEntityManager()->persist($task);
            $this->getEntityManager()->flush();

            $tasks[] = $task;
        }

        return $tasks;
    }
}
