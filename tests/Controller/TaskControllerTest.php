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

    public function testTaskUndoneGETListAuthorized()
    {
        // Logged User
        $user = $this->createUserAndLogin();

        // Undone Tasks
        $this->createTasks(20, false);
        // Done Tasks
        $this->createTasks(20, true);

        // First Undone Task
        $firstTaskFixture = $this->createTask($user, false, true);

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString('Liste des tâches', $title->text());

        // Main Title
        $this->assertSelectorTextSame('h1', sprintf('Liste des tâches à faire (%s)', 21));

        // New Task button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-info'));

        $newUserUri = $this->getRouter()->generate('task_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer une nouvelle tâche', $newUserButton->text());

        // Pagination - Previous Page
        $pagination = $crawler->filter('.pagination');
        $previousPage = $pagination->filter('li:nth-child(1)');
        $this->assertEquals('Précédent', $previousPage->text());
        // Pagination - Page 1
        $firstPage = $pagination->filter('li:nth-child(2)');
        $this->assertEquals(1, $firstPage->text());
        // Pagination - Page 2
        $secondPage = $pagination->filter('li:nth-child(3)');
        $this->assertEquals(2, $secondPage->text());
        $this->assertEquals('/tasks?page=2', $secondPage->filter('a')->attr('href'));
        // Pagination - Next
        $nextPage = $pagination->filter('li:nth-child(4)');
        $this->assertEquals('Suivant', $nextPage->text());
        $this->assertEquals('/tasks?page=2', $nextPage->filter('a')->attr('href'));

        // Paginated Tasks
        $this->assertCount(12, $crawler->filter('div.thumbnail'));

        // First Task
        $firstTask = $crawler->filter('.tasks > .task')->first();

        // First Task - Edit
        $editTaskLink = $firstTask->filter('h4 > a');
        $editTaskUri = $this->getRouter()->generate('task_edit', ['id' => $firstTaskFixture->getId()]);

        $this->assertEquals($editTaskUri, $editTaskLink->attr('href'));
        $this->assertEquals($firstTaskFixture->getTitle(), $editTaskLink->text());

        // First Task - Delete
        $deleteTaskUri = $this->getRouter()->generate('task_confirm_delete', ['id' => $firstTaskFixture->getId()]);
        $deleteBtn = $firstTask->filter('.js-task-delete');

        $this->assertEquals($deleteTaskUri, $deleteBtn->attr('data-href'));
        $this->assertEquals('Supprimer', $deleteBtn->text());

        // First Task - Toggle
        $toggleTaskForm = $firstTask->filter('form:first-child');
        $toggleTaskUri = $this->getRouter()->generate('task_toggle', ['id' => $firstTaskFixture->getId()]);
        $toggleBtn = $firstTask->filter('button.btn.btn-success');

        $this->assertEquals($toggleTaskUri, $toggleTaskForm->attr('action'));
        $this->assertEquals('Marquer comme faite', $toggleBtn->text());

        // First Task - CreatedAt Date
        $createdAtTask = $firstTask->filter('.caption--footer--date > p.text-muted.small:first-child')->text();
        $this->assertEquals(sprintf(
            'Créée le %s par %s',
            $firstTaskFixture->getCreatedAt()->format('d/m/Y'),
            $firstTaskFixture->getOwner()->getUsername()
        ), $createdAtTask);

        // First Task - UpdatedAt Date
        $updatedAtTask = $firstTask->filter('.caption--footer--date > p.text-muted.small:nth-child(2)')->text();
        $this->assertEquals(sprintf(
            'Dernière mise à jour le %s',
            $firstTaskFixture->getUpdatedAt()->format('d/m/Y H:m:s')
        ), $updatedAtTask);
    }

    public function testTaskUndoneGETListAdmin()
    {
        // Logged User
        $this->createAdminUserAndLogin();

        // Undone Tasks
        $this->createTasks(20, false);
        // Done Tasks
        $this->createTasks(20, true);

        // First Undone Task
        $firstTaskFixture = $this->createTask(null, false, true);

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks');

        // First Task
        $firstTask = $crawler->filter('.tasks > .task')->first();

        // First Task - Delete
        $deleteTaskUri = $this->getRouter()->generate('task_confirm_delete', ['id' => $firstTaskFixture->getId()]);
        $deleteBtn = $firstTask->filter('.js-task-delete');

        $this->assertEquals($deleteTaskUri, $deleteBtn->attr('data-href'));
        $this->assertEquals('Supprimer', $deleteBtn->text());
    }

    public function testTaskDoneGETListAuthorized()
    {
        // Logged User
        $user = $this->createUserAndLogin();

        // Done Tasks
        $this->createTasks(20, true);
        $firstTaskFixture = $this->createTask($user, true, true);

        // Undone Tasks
        $this->createTasks(20, false);

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/done');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString('Liste des tâches', $title->text());

        // Main Title
        $this->assertSelectorTextSame('h1', sprintf('Liste des tâches terminées (%s)', 21));

        // New Task button
        $this->assertNotEmpty($newUserButton = $crawler->filter('a.btn.btn-info'));

        $newUserUri = $this->getRouter()->generate('task_create');

        $this->assertEquals($newUserUri, $newUserButton->attr('href'));
        $this->assertEquals('Créer une nouvelle tâche', $newUserButton->text());

        // Tasks
        $this->assertCount(12, $crawler->filter('div.thumbnail'));

        // First Task
        $firstTask = $crawler->filter('.tasks > .task')->first();

        // First Task - Edit
        $this->assertSelectorNotExists('h4 > a');

        // First Task - Delete
        $deleteTaskUri = $this->getRouter()->generate('task_confirm_delete', ['id' => $firstTaskFixture->getId()]);
        $deleteBtn = $firstTask->filter('.js-task-delete');

        $this->assertEquals($deleteTaskUri, $deleteBtn->attr('data-href'));
        $this->assertEquals('Supprimer', $deleteBtn->text());

        // First Task - Toggle
        $toggleTaskForm = $firstTask->filter('form:first-child');
        $toggleTaskUri = $this->getRouter()->generate('task_toggle', ['id' => $firstTaskFixture->getId()]);
        $toggleBtn = $firstTask->filter('button.btn.btn-warning');

        $this->assertEquals($toggleTaskUri, $toggleTaskForm->attr('action'));
        $this->assertEquals('Marquer comme non terminée', $toggleBtn->text());

        // First Task - CreatedAt Date
        $createdAtTask = $firstTask->filter('.caption--footer--date > p.text-muted.small:first-child')->text();
        $this->assertEquals(sprintf(
            'Créée le %s par %s',
            $firstTaskFixture->getCreatedAt()->format('d/m/Y'),
            $firstTaskFixture->getOwner()->getUsername()
        ), $createdAtTask);

        // First Task - DoneAt Date
        $doneAtTask = $firstTask->filter('.caption--footer--date > p.text-muted.small:nth-child(2)')->text();
        $this->assertEquals(sprintf(
            'Terminée le %s',
            $firstTaskFixture->getDoneAt()->format('d/m/Y H:m:s')
        ), $doneAtTask);
    }

    public function testTaskDoneGETListAdmin()
    {
        // Logged User
        $this->createAdminUserAndLogin();

        // Done Tasks
        $firstTaskFixture = $this->createDoneTask();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/done');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // First Task
        $firstTask = $crawler->filter('.tasks > .task')->first();

        // First Task - Edit
        $editTaskLink = $firstTask->filter('h4 > a');
        $editTaskUri = $this->getRouter()->generate('task_edit', ['id' => $firstTaskFixture->getId()]);

        $this->assertEquals($editTaskUri, $editTaskLink->attr('href'));
        $this->assertEquals($firstTaskFixture->getTitle(), $editTaskLink->text());

        // First Task - Delete
        $deleteTaskUri = $this->getRouter()->generate('task_confirm_delete', ['id' => $firstTaskFixture->getId()]);
        $deleteBtn = $firstTask->filter('.js-task-delete');

        $this->assertEquals($deleteTaskUri, $deleteBtn->attr('data-href'));
        $this->assertEquals('Supprimer', $deleteBtn->text());
    }

    public function testTaskGETCreateAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, '/tasks/create');

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString('Nouvelle tâche', $title->text());

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
        $undoneTaskListUri = $this->getRouter()->generate('task_list');
        $this->assertResponseRedirects($undoneTaskListUri);
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

    /**
     * @dataProvider getOwner()
     */
    public function testTaskUndoneGETEditAuthorized(bool $anonymousTask)
    {
        // Logged User
        $this->createUserAndLogin();

        // Initial Undone Task
        $owner = $anonymousTask ? null : UserFactory::createOne()->object();
        $task = $this->createTask($owner, false);

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Title
        $title = $crawler->filter('title');
        $this->assertStringContainsString(sprintf('Modification de %s', $task->getTitle()), $title->text());

        // Main Title
        $this->assertSelectorTextSame('h1', sprintf('Modifier %s', $task->getTitle()));

        // Owner
        $ownerUsername = $owner ? $owner->getUsername() : Task::ANONYMOUS_TASK;
        $this->assertSelectorTextContains('p.text-muted.pull-right', $ownerUsername);

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

    /**
     * @dataProvider getOwner()
     */
    public function testTaskDoneGETEditAuthorized(bool $anonymousTask)
    {
        // Logged User
        $this->createUserAndLogin();

        // Initial Undone Task
        $owner = $anonymousTask ? null : UserFactory::createOne()->object();
        $task = $this->createTask($owner, true);

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @dataProvider getOwner()
     */
    public function testTaskUndoneGETEditAdmin(bool $anonymousTask)
    {
        // Logged User
        $this->createAdminUserAndLogin();

        // Initial Done Task
        $owner = $anonymousTask ? null : UserFactory::createOne()->object();
        $task = $this->createTask($owner, true);

        // Request
        $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testTaskPOSTEditAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Initial Undone Task
        $task = $this->createUndoneTask();
        $initialOwner = $task->getOwner();
        $initialUpdatedAt = $task->getUpdatedAt();

        // Request
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/edit', $task->getId()));

        // Form
        $this->submitEditForm($crawler, [
            'task[title]' => 'task_title_updated',
            'task[content]' => 'task_content_updated',
        ]);

        // Redirection
        $undoneTaskListUri = $this->getRouter()->generate('task_list');
        $this->assertResponseRedirects($undoneTaskListUri);
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
        $this->assertEquals($initialOwner->getId(), $updatedTask->getOwner()->getId());
        $this->assertNotEquals($initialUpdatedAt, $updatedTask->getUpdatedAt());
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

        // Initial Undone Task
        $this->createTask();
        $updatedTask = $this->createUndoneTask();

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
        $this->createUserAndLogin();

        // Initial Undone Task
        $initialTask = $this->createUndoneTask();

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
        $this->assertNotNull($toggledTask->getDoneAt());
    }

    public function testTaskGETDoneToggleAuthorized()
    {
        // Logged User
        $this->createUserAndLogin();

        // Initial Done Task owned by user
        $initialTask = $this->createDoneTask();

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
        $this->assertNull($toggledTask->getDoneAt());
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
    public function testTaskPOSTDeleteAuthorized(string $role, bool $anonymousTask)
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
        $isDoneTask = $task->isDone();

        // Request confirm_delete
        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/tasks/%d/confirm-delete', $task->getId()));

        // Response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Modal
        $modal = $crawler->filter('#task__modal__delete');

        // Modal header
        $modalHeader = $modal->filter('.modal-header');
        $this->assertEquals(
            sprintf(
                '%s - Tâche créée le %s par %s',
                $task->getTitle(),
                $task->getCreatedAt()->format('d/m/Y'),
                ($task->getOwner()) ? $task->getOwner()->getUsername() : Task::ANONYMOUS_TASK
            ),
            $modalHeader->text()
        );

        // Modal body
        $modalBody = $modal->filter('.modal-body');
        $this->assertEquals(
            'Etes-vous certain(e) de vouloir supprimer cette tâche ?',
            $modalBody->text()
        );

        // Modal Form Action --> request to '/tasks/id/delete'
        $deleteTaskUri = $this->getRouter()->generate('task_delete', ['id' => $task->getId()]);
        $formAction = $crawler->filter('form')->attr('action');
        $this->assertEquals($deleteTaskUri, $formAction);

        // Submit form
        $form = $crawler->selectButton('Oui')->form();
        $this->client->submit($form);

        // Redirection
        $taskListUri = ($isDoneTask) ? $this->getRouter()->generate('task_list_done') : $this->getRouter()->generate('task_list');
        $this->assertResponseRedirects($taskListUri);
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
    public function testTaskPOSTDeleteForbidden(bool $anonymousTask)
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
        $this->forbiddenAction(Request::METHOD_POST, sprintf('/tasks/%d/delete', $task->getId()));
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
            [Request::METHOD_GET, '/tasks/fake/confirm-delete'],
            [Request::METHOD_POST, '/tasks/fake/delete'],
        ];
    }

    private function getNotFoundActions(): array
    {
        // Method, Uri
        return [
            [Request::METHOD_GET, '/tasks/fake/edit'],
            [Request::METHOD_POST, '/tasks/fake/edit'],
            [Request::METHOD_GET, '/tasks/fake/toggle'],
            [Request::METHOD_POST, '/tasks/fake/delete'],
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

    private function getOwner(): array
    {
        // Anonymous User
        return [
            [true],
            [false]
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

    private function createUndoneTask(bool $isCreatedNow = false): Task
    {
        return TaskFactory::createOne([
                'isDone' => false,
                'createdAt' => $isCreatedNow ? new DateTime('NOW') : new DateTime('-1day'),
                'owner' => UserFactory::createOne()
            ])
            ->object();
    }

    private function createDoneTask(bool $isCreatedNow = false): Task
    {
        return TaskFactory::createOne([
                'isDone' => true,
                'createdAt' => $isCreatedNow ? new DateTime('NOW') : new DateTime('-1day'),
                'doneAt' => new DateTime('NOW'),
                'owner' => UserFactory::createOne()
            ])
            ->object();
    }

    private function createTasks(int $number, bool $isDone = null): void
    {
        TaskFactory::createMany($number, [
            'isDone' => is_bool($isDone) ? $isDone : (bool) random_int(0, 1)
        ]);
    }
}
