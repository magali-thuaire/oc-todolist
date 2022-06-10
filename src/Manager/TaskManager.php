<?php

namespace App\Manager;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Service\PaginationService;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class TaskManager
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly FormFactoryInterface $formFactory,
        private readonly PaginationService $paginationFactory,
    ) {
    }

    public function listUndoneTasks(Request $request): iterable
    {
        $qb = $this->taskRepository->getUndoneTasks();

        return $this->paginationFactory->paginateItems($qb, $request);
    }

    public function listDoneTasks(Request $request): iterable
    {
        $qb = $this->taskRepository->getDoneTasks();

        return $this->paginationFactory->paginateItems($qb, $request);
    }

    public function createTask(Request $request, User $user): FormInterface
    {
        $task = new Task();

        $form = $this->formFactory->create(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->addTask($task);
            $this->taskRepository->add($task, true);
        }

        return $form;
    }

    public function editTask(Request $request, Task $task): FormInterface
    {
        $form = $this->formFactory->create(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->taskRepository->update($task, true);
        }

        return $form;
    }

    public function toggle(Task $task): Task
    {
        $task->toggle();
        $this->taskRepository->update($task, true);

        return $task;
    }

    public function deleteTask(Task $task)
    {
        $this->taskRepository->remove($task, true);
    }
}
