<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use DateTime;
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
        $user = $this->createUserAndLogin();

        // Undone Tasks
        $this->createTasks(5, false);
        // Done Tasks
        $this->createTasks(5, true);

        // First Task undone owned by user
        $firstTaskFixture = $this->createTask($user, false, true);

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // New Task button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-info'));

        $newUserUri = $this->getRouter()->generate('task_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer une tâche', $newUserButton->text());

        // Tasks
        $this->assertCount(6, $crawler->filter('div.thumbnail'));

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
        $user = $this->createUserAndLogin();

        // Done Tasks
        $this->createTasks(5, true);
        $firstTaskFixture = $this->createTask($user, true, true);

        // Undone Tasks
        $this->createTasks(5, false);

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/done');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // New Task button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-info'));

        $newUserUri = $this->getRouter()->generate('task_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer une tâche', $newUserButton->text());

        // Tasks
        $this->assertCount(6, $crawler->filter('div.thumbnail'));

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
        $toggleBtn = $firstTask->filter('button.btn.btn-warning');

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

        // Main Title
        $this->assertSelectorTextSame('h1', 'Créer une nouvelle tâche');

        // Form
        $newTaskUri = $this->getRouter()->generate('task_create');

        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($newTaskUri, $form->attr('action'));
        $this->assertSelectorExists('input[type=text]#task_title');
        $this->assertSelectorExists('textarea#task_content');

        // Return button
        $this->assertSelectorTextSame('a.btn.btn-primary', 'Retour à la liste des tâches à faire');

        // Submit button
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Ajouter');
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

        // Main Title
        $this->assertSelectorTextSame('h1', sprintf('Modifier %s', $task->getTitle()));

        // Form
        $updateTaskUri = $this->getRouter()->generate('task_edit', ['id' => $task->getId()]);

        $this->assertNotEmpty($form = $crawler->filter('form'));
        $this->assertEquals($updateTaskUri, $form->attr('action'));

        $this->assertInputValueSame('task[title]', 'task_title');

        $this->assertNotEmpty($content = $form->filter('textarea#task_content'));
        $this->assertSame('task_content', $content->text());

        $this->assertSelectorNotExists('select#task_owner');

        // Return button
        $this->assertSelectorTextSame('a.btn.btn-primary', 'Retour à la liste des tâches à faire');

        // Submit button
        $this->assertSelectorTextSame('button.btn.btn-success[type=submit]', 'Modifier');
    }

    public function testTaskAnonymousGETEditAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Initial Task
        $task = TaskFactory::createOne([
            'owner' => null
        ]);

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Form
        $this->assertSelectorTextContains('p.text-muted.pull-right', Task::ANONYMOUS_TASK);
    }

    public function testTaskPOSTEditAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Initial Task
        $task = $this->createTask();
        $initialOwner = $task->getOwner();

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
        $this->assertEquals($initialOwner, $updatedTask->getOwner());
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

    /**
     * @dataProvider getRoleUserOrRoleAdminAndTask()
     */
    public function testTaskGETDeleteAuthorized(string $role, bool $anonymousTask)
    {
        if ($role === 'ROLE_ADMIN') {
            $this->createAdminUserAndLogin();
            $user = $this->createUser();
        } elseif ($role === 'ROLE_USER') {
            $user = $this->createUserAndLogin();
        }

        // Initial Task owned by user not admin
        $task = $this->createTask($anonymousTask ? null : $user);
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

    /**
     * @dataProvider getTaskAnonymousOrNot()
     */
    public function testTaskGETDeleteForbidden(bool $anonymousTask)
    {
        // Logged User
        $this->createUserAndLogin();

        // Initial Task anonymous or owned by an other user
        if ($anonymousTask) {
            $task = $this->createTask();
        } else {
            $owner = UserFactory::createOne()->object();
            $task = $this->createTask($owner);
        }

        // Response
        $this->forbiddenAction(Request::METHOD_GET, sprintf('/tasks/%d/delete', $task->getId()));
    }

    private function getUnauthorizedActions(): array
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

    private function getNotFoundActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/tasks/fake/edit'],
            [Request::METHOD_POST, '/tasks/fake/edit'],
            [Request::METHOD_GET, '/tasks/fake/toggle'],
            [Request::METHOD_GET, '/tasks/fake/delete'],
        ];
    }

    private function getValidationErrors(): array
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

    private function createTask(?User $owner = null, bool $isDone = null, bool $isCreatedNow = false): Task
    {
        return TaskFactory::createOne([
            'title' => 'task_title',
            'content' => 'task_content',
            'owner' => $owner,
            'isDone' => is_bool($isDone) ? $isDone : (bool) random_int(0, 1),
            'createdAt' => $isCreatedNow ? new DateTime('NOW') : new DateTime('-1day'),
        ])
        ->object();
    }

    private function createTasks(int $number, bool $isDone = null): void
    {
        TaskFactory::createMany($number, [
            'isDone' => is_bool($isDone) ? $isDone : (bool) random_int(0, 1)
        ]);
    }

    private function getRoleUserOrRoleAdminAndTask(): array
    {
        // User Role, anonymous task
        return [
            ['ROLE_ADMIN', true],
            ['ROLE_ADMIN', false],
            ['ROLE_USER', false],
        ];
    }

    private function getTaskAnonymousOrNot(): array
    {
        // Anonymous Task
        return [
            [true],
            [false]
        ];
    }
}
