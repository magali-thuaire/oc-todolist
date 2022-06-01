<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly EntityManagerInterface $em
    ) {
    }

    #[Route(path: '/tasks', name: 'task_list', methods: 'GET')]
    public function listAction(): Response
    {
        return $this->render('task/list.html.twig', [
            'tasks' => $this->managerRegistry->getRepository(Task::class)->findAll(),
        ]);
    }

    #[Route(path: '/tasks/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): RedirectResponse|Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($task);
            $this->em->flush();

            $this->addFlash('success', 'La tâche a été bien été ajoutée.');

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

            $this->addFlash('success', 'La tâche a bien été modifiée.');

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
        $task->toggle(!$task->isDone());
        $this->em->flush();
        $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle()));

        return $this->redirectToRoute('task_list');
    }

    #[Route(path: '/tasks/{id}/delete', name: 'task_delete', methods: 'GET')]
    public function deleteTaskAction(Task $task): RedirectResponse
    {
        $this->em->remove($task);
        $this->em->flush();
        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }
}
