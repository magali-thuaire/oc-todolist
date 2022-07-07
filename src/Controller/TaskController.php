<?php

namespace App\Controller;

use App\Entity\Task;
use App\Manager\TaskManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/tasks')]
class TaskController extends BaseController
{
    public function __construct(
        private readonly TaskManager $taskManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '', name: 'task_list', methods: 'GET')]
    public function listUndoneAction(Request $request): Response
    {
        $undoneTasks = $this->taskManager->listUndoneTasks($request);

        return $this->render('task/list.html.twig', [
            'tasks' => $undoneTasks,
        ]);
    }

    #[Route(path: '/done', name: 'task_list_done', methods: 'GET')]
    public function listDoneAction(Request $request): Response
    {
        $doneTasks = $this->taskManager->listDoneTasks($request);

        return $this->render('task/list.html.twig', [
            'tasks' => $doneTasks,
        ]);
    }

    #[Route(path: '/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): RedirectResponse|Response
    {
        $form = $this->taskManager->createTask($request, $this->getUser());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash(
                'success',
                $this->translator->trans('task.create.success', [], 'flashes')
            );

            return $this->redirectToRoute('task_list');
        }

        return $this->renderForm('task/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    #[IsGranted('TASK_EDIT', 'task')]
    public function editAction(Task $task, Request $request): RedirectResponse|Response
    {
        $form = $this->taskManager->editTask($request, $task);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash(
                'success',
                $this->translator->trans('task.edit.success', [], 'flashes')
            );

            return $this->redirectToRoute('task_list');
        }

        return $this->renderForm('task/edit.html.twig', [
            'form' => $form,
            'task' => $task,
        ]);
    }

    #[Route(path: '/{id}/toggle', name: 'task_toggle', methods: 'GET')]
    public function toggleTaskAction(Task $task): RedirectResponse
    {
        $task = $this->taskManager->toggle($task);

        if ($task->isDone()) {
            $this->addFlash(
                'success',
                $this->translator->trans('task.toggle.done.success', ['task.title' => $task->getTitle()], 'flashes')
            );

            return $this->redirectToRoute('task_list');
        }

        $this->addFlash(
            'success',
            $this->translator->trans('task.toggle.undone.success', ['task.title' => $task->getTitle()], 'flashes')
        );

        return $this->redirectToRoute('task_list_done');
    }

    #[Route(path: '/{id}/confirm-delete', name: 'task_confirm_delete', methods: 'GET')]
    #[IsGranted('TASK_DELETE', subject: 'task')]
    public function confirmDeleteTaskAction(Task $task): Response
    {
        return $this->render('task/delete_modal.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'task_delete', methods: 'POST')]
    #[IsGranted('TASK_DELETE', subject: 'task')]
    public function deleteTaskAction(Task $task, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete'.$task->getId(), $request->get('_token'))) {
            throw new InvalidCsrfTokenException();
        }

        $this->taskManager->deleteTask($task);

        $this->addFlash(
            'success',
            $this->translator->trans('task.delete.success', [], 'flashes')
        );

        if ($task->isDone()) {
            return $this->redirectToRoute('task_list_done');
        }

        return $this->redirectToRoute('task_list');
    }
}
