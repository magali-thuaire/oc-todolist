<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class TaskController extends BaseController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[Route(path: '/tasks', name: 'task_list', methods: 'GET')]
    public function listAction(): Response
    {
        $undoneTasks = $this->managerRegistry
            ->getRepository(Task::class)
             ->findBy(
                 ['isDone' => false],
                 ['id' => 'DESC' ]
             );
        return $this->render('task/list.html.twig', [
            'tasks' => $undoneTasks,
        ]);
    }

    #[Route(path: '/tasks/done', name: 'task_list_done', methods: 'GET')]
    public function listDoneAction(): Response
    {
        $doneTasks = $this->managerRegistry
            ->getRepository(Task::class)
            ->findBy(
                ['isDone' => true],
                ['id' => 'DESC']
            );

        return $this->render('task/list.html.twig', [
            'tasks' => $doneTasks,
        ]);
    }

    #[Route(path: '/tasks/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): RedirectResponse|Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $task->setOwner($this->getUser());
            $this->em->persist($task);
            $this->em->flush();

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

    #[Route(path: '/tasks/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function editAction(Task $task, Request $request): RedirectResponse|Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

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

    #[Route(path: '/tasks/{id}/toggle', name: 'task_toggle', methods: 'GET')]
    public function toggleTaskAction(Task $task): RedirectResponse
    {
        $task->toggle();
        $this->em->flush();

        if (!$task->isDone()) {
            $this->addFlash(
                'success',
                $this->translator->trans('task.toggle.undone.success', ['task.title' => $task->getTitle()], 'flashes')
            );
            return $this->redirectToRoute('task_list_done');
        }

        $this->addFlash(
            'success',
            $this->translator->trans('task.toggle.done.success', ['task.title' => $task->getTitle()], 'flashes')
        );

        return $this->redirectToRoute('task_list');
    }

    #[Route(path: '/tasks/{id}/delete', name: 'task_delete', methods: 'GET')]
    public function deleteTaskAction(Task $task): RedirectResponse
    {
        $this->em->remove($task);
        $this->em->flush();
        $this->addFlash(
            'success',
            $this->translator->trans('task.delete.success', [], 'flashes')
        );

        return $this->redirectToRoute('task_list');
    }
}
