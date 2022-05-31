<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Factory\TaskFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Utils\BaseWebTestCase;

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
        $tasksFixture = TaskFactory::createMany(5);
        $firstTaskFixture = current($tasksFixture);

        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

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
        $this->assertCount(5, $crawler->filter('div.thumbnail'));

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
            $this->assertEquals('Marquer non terminée', $toggleBtn->text());
        } else {
            $this->assertEquals('Marquer comme faite', $toggleBtn->text());
        }
    }

    public function testTaskGETCreateAuthorized()
    {
        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

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
        $this->assertSelectorExists('input[type=text]#task_title');
        $this->assertSelectorExists('textarea#task_content');

        // Submit button
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Ajouter');

        // Return button
        $this->assertNotEmpty($returnBtn = $crawler->filter('a.btn.btn-primary')->last());
        $this->assertEquals('Retour à la liste des tâches', $returnBtn->text());
    }

    public function testTaskPOSTCreateAuthorized()
    {
        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create');

        // Form
        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]' => 'task_title',
            'task[content]' => 'task_content',
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextSame(
            'div.alert.alert-success',
            'Superbe ! La tâche a été bien été ajoutée.'
        );

        // Created Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $createdTask = $taskRepository->findOneBy(['title' => 'task_title']);
        $this->assertNotNull($createdTask, 'task created not found');
        $this->assertEquals('task_content', $createdTask->getContent());
    }

    public function testTaskPOSTCreateAuthorizedWithErrors()
    {
        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create');

        // Form
        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]' => '',
            'task[content]' => '',
        ]);
        $crawler = $this->client->submit($form);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Errors
        $titleError = $crawler->filter('.help-block')->first()->text();
        $this->assertEquals('Vous devez saisir un titre.', $titleError);
        $contentError = $crawler->filter('.help-block')->last()->text();
        $this->assertEquals('Vous devez saisir du contenu.', $contentError);
    }

    public function testTaskGETEditAuthorized()
    {
        // Initial Task
        $task = $this->createTask();

        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Form
        $updateTaskUri = $this->getRouter()->generate('task_edit', ['id' => $task->getId()]);

        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($updateTaskUri, $form->attr('action'));

        $this->assertInputValueSame('task[title]', 'task_title');

        $this->assertNotEmpty($content = $form->filter('textarea#task_content'));
        $this->assertSame('task_content', $content->text());

        // Submit button
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Modifier');
    }

    public function testTaskPOSTEditAuthorized()
    {
        // Initial Task
        $task = $this->createTask();

        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()));

        // Form
        $form = $crawler->selectButton('Modifier')->form([
            'task[title]' => 'task_title_updated',
            'task[content]' => 'task_content_updated',
        ]);
        $this->client->submit($form);

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextSame(
            'div.alert.alert-success',
            'Superbe ! La tâche a bien été modifiée.'
        );

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
        $this->createAuthorizedUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create');

        // Form
        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]' => '',
            'task[content]' => '',
        ]);
        $crawler = $this->client->submit($form);

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Errors
        $titleError = $crawler->filter('.help-block')->first()->text();
        $this->assertEquals('Vous devez saisir un titre.', $titleError);
        $contentError = $crawler->filter('.help-block')->last()->text();
        $this->assertEquals('Vous devez saisir du contenu.', $contentError);
    }

    public function testTaskGETToggleAuthorized()
    {
        // Initial Task
        $task = $this->createTask();
        $isDone = $task->isDone();

        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/toggle', $task->getId()));

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        if (!$task->isDone()) {
            $this->assertSelectorTextSame(
                'div.alert.alert-success',
                sprintf('Superbe ! La tâche %s a bien été marquée comme faite.', $task->getTitle())
            );
        }
//        else  {
//            $this->assertSelectorTextSame(
//                'div.alert.alert-success',
//                sprintf('Superbe ! La tâche %s a bien été marquée comme non terminée.', $task->getTitle())
//            );
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
        $task = $this->createTask();
        $taskId = $task->getId();

        // Logged User
        $this->createAuthorizedUserAndLogin();

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/delete', $task->getId()));

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextSame(
            'div.alert.alert-success',
            'Superbe ! La tâche a bien été supprimée.'
        );

        // Deleted Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $deletedTask = $taskRepository->findOneBy(['id' => $taskId]);
        $this->assertNull($deletedTask);
    }

    public function getUnauthorizedActions(): array
    {
        return [
            ['/tasks'],
            ['/tasks/create'],
            ['/tasks/fake/edit'],
            ['/tasks/fake/toggle'],
            ['/tasks/fake/delete'],
        ];
    }

    public function getNotFoundActions(): array
    {
        return [
            ['/tasks/fake/edit'],
            ['/tasks/fake/toggle'],
            ['/tasks/fake/delete'],
        ];
    }

    private function createTask(): Task
    {
        return TaskFactory::new()
                    ->withAttributes([
                       'title' => 'task_title',
                       'content' => 'task_content',
                    ])
                    ->create()
                    ->object();
    }
}
