<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Factory\TaskFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Utils\BaseWebTestCase;

class TaskControllerTest extends BaseWebTestCase
{
    /**
     * @dataProvider getUnauthorizedActions
     */
    public function testUnauthorizedAction(string $method, string $uri)
    {
        $this->unauthorizedAction($method, $uri);
    }

    public function testTaskGETListAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Undone Tasks owned by user
        $undoneTasksFixture = $this->createTasks(5, false);
        $firstTaskFixture = end($undoneTasksFixture);

        // Done Tasks
        $this->createTasks(5, true);

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
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-success'));

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
        $this->assertEquals('Marquer comme faite', $toggleBtn->text());
    }

    public function testTaskGETDoneListAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Undone Tasks owned by user
        $doneTasksFixture = $this->createTasks(5, true);
        $firstTaskFixture = end($doneTasksFixture);

        // Done Tasks owned by user
        $this->createTasks(5, false);

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/done');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // New Task button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-success'));

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
        $this->assertEquals('Marquer comme non terminée', $toggleBtn->text());
    }

    public function testTaskGETCreateAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

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
        $user = $this->createUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create');

        // Form
        $this->submitCreateForm($crawler, [
            'task[title]' => 'task_title',
            'task[content]' => 'task_content',
        ]);

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans('task.create.success', [], 'flashes')
        );

        // Created Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $createdTask = $taskRepository->findOneBy(['title' => 'task_title']);
        $this->assertEquals($user->getId(), $createdTask->getOwner()->getId());
        $this->assertNotNull($createdTask, 'task created not found');
        $this->assertEquals('task_content', $createdTask->getContent());
    }

    /**
     * @dataProvider getValidationErrors()
     */
    public function testTaskPOSTCreateAuthorizedWithErrors(
        string $fieldName,
        ?string $fieldValue,
        string $selector,
        string $idValidationMessage
    ) {

        // Logged User
        $this->createUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create');

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

    public function testTaskGETEditAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Initial Task
        $task = $this->createTask();

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
        // Logged User
        $this->createUserAndLogin();

        // Initial Task
        $task = $this->createTask();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()));

        // Form
        $this->submitEditForm($crawler, [
            'task[title]' => 'task_title_updated',
            'task[content]' => 'task_content_updated',
        ]);

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans('task.edit.success', [], 'flashes')
        );

        // Updated Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $updatedTask = $taskRepository->findOneBy(['id' => $task->getId()]);
        $this->assertNotEmpty($updatedTask, 'task updated not found');
        $this->assertEquals('task_title_updated', $updatedTask->getTitle());
        $this->assertEquals('task_content_updated', $updatedTask->getContent());
    }

    /**
     * @dataProvider getValidationErrors()
     */
    public function testTaskPOSTEditAuthorizedWithErrors(
        string $fieldName,
        ?string $fieldValue,
        string $selector,
        string $idValidationMessage
    ) {
        // Logged User
        $this->createUserAndLogin();

        // Initial Task
        $this->createTask();
        $updatedTask = TaskFactory::createOne();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $updatedTask->getId()));

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

    public function testTaskGETUndoneToggleAuthorized()
    {
        // Logged User
        $user = $this->createUserAndLogin();

        // Initial Undone Task
        $initialTask = $this->createTask($user, false);

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/toggle', $initialTask->getId()));

        // Redirection
        $undoneTaskUrl = $this->getRouter()->generate('task_list');
        $this->assertResponseRedirects($undoneTaskUrl);
        $this->client->followRedirect();

        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans(
                'task.toggle.done.success',
                ['task.title' => $initialTask->getTitle()],
                'flashes'
            )
        );

        // Toggled Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $toggledTask = $taskRepository->findOneBy(['id' => $initialTask->getId()]);
        $this->assertTrue($toggledTask->isDone());
    }

    public function testTaskGETDoneToggleAuthorized()
    {
        // Logged User
        $user = $this->createUserAndLogin();

        // Initial Done Task owned by user
        $initialTask = $this->createTask($user, true);

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/toggle', $initialTask->getId()));

        // Redirection
        $doneTaskUrl = $this->getRouter()->generate('task_list_done');
        $this->assertResponseRedirects($doneTaskUrl);
        $this->client->followRedirect();

        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans(
                'task.toggle.undone.success',
                ['task.title' => $initialTask->getTitle()],
                'flashes'
            )
        );

        // Toggled Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $toggledTask = $taskRepository->findOneBy(['id' => $initialTask->getId()]);
        $this->assertFalse($toggledTask->isDone());
    }

    /**
     * @dataProvider getNotFoundActions()
     */
    public function testTask404Exception(string $method, string $uri)
    {
        $this->notFound404Exception($method, $uri);
    }

    public function testTaskGETDeleteAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Initial Task
        $task = $this->createTask();
        $taskId = $task->getId();

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/delete', $task->getId()));

        // Redirection
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Success Message
        $this->assertSelectorTextContains(
            'div.alert.alert-success',
            $this->getTranslator()->trans('task.delete.success', [], 'flashes')
        );

        // Deleted Task
        $taskRepository = $this->getDoctrine()->getRepository(Task::class);
        $deletedTask = $taskRepository->findOneBy(['id' => $taskId]);
        $this->assertNull($deletedTask);
    }

    public function getUnauthorizedActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/tasks'],
            [Request::METHOD_GET, '/tasks/create'],
            [Request::METHOD_POST, '/tasks/create'],
            [Request::METHOD_GET, '/tasks/fake/edit'],
            [Request::METHOD_POST, '/tasks/fake/edit'],
            [Request::METHOD_GET, '/tasks/fake/toggle'],
            [Request::METHOD_GET, '/tasks/fake/delete'],
        ];
    }

    public function getNotFoundActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/tasks/fake/edit'],
            [Request::METHOD_POST, '/tasks/fake/edit'],
            [Request::METHOD_GET, '/tasks/fake/toggle'],
            [Request::METHOD_GET, '/tasks/fake/delete'],
        ];
    }

    private function createTask(?User $owner = null, bool $isDone = null): Task
    {
        return TaskFactory::createOne([
                       'title' => 'task_title',
                       'content' => 'task_content',
                        'owner' => $owner,
                        'isDone' => is_bool($isDone) ? $isDone : (bool) random_int(0, 1)
                    ])
                    ->object();
    }

    private function createTasks(int $number, bool $isDone = null): array
    {
        return TaskFactory::createMany($number, [
            'isDone' => is_bool($isDone) ? $isDone : (bool) random_int(0, 1)
        ]);
    }

    public function getValidationErrors(): array
    {
        $titleField = 'task[title]';
        $titleSelector = 'input[type=text]#task_title';
        $contentField = 'task[content]';
        $contentSelector = 'textarea#task_content';

        // fieldName, fieldValue, selector, idValidationMessage
        return [
            // Title not blank
            [
                $titleField,
                null,
                $titleSelector,
                'task.title.not_blank'
            ],
            // Content not blank
            [
                $contentField,
                null,
                $contentSelector,
                'task.content.not_blank'
            ],
        ];
    }
}
