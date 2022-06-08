<?php

namespace App\Manager;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class TaskManager
{
    public function __construct(
        protected readonly ManagerRegistry $managerRegistry,
        protected readonly TaskRepository $taskRepository,
        protected readonly EntityManagerInterface $em,
        protected readonly FormFactoryInterface $formFactory
    ) {
    }

    public function listUndoneTasks(): ?array
    {
        return $this->taskRepository->findUndoneTasks();
    }

    public function listDoneTasks(): ?array
    {
        return $this->taskRepository->findDoneTasks();
    }

    public function createTask(Request $request, User $user): FormInterface
    {
        $task = new Task();

        $form = $this->formFactory->create(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setOwner($user);
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
