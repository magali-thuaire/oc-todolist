<?php

namespace App\Manager;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class TaskManager
{
    public function __construct(
        protected readonly ManagerRegistry $managerRegistry,
        protected readonly EntityManagerInterface $em,
        protected readonly FormFactoryInterface $formFactory
    ) {
    }

    public function listUndoneTasks(): ?array
    {
        return $this->managerRegistry
            ->getRepository(Task::class)
            ->findBy(
                ['isDone' => false],
                ['createdAt' => 'DESC' ]
            )
        ;
    }

    public function listDoneTasks(): ?array
    {
        return $this->managerRegistry
            ->getRepository(Task::class)
            ->findBy(
                ['isDone' => true],
                ['createdAt' => 'DESC' ]
            )
        ;
    }

    public function createTask(Request $request, User $user): FormInterface
    {
        $task = new Task();

        $form = $this->formFactory->create(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setOwner($user);
            $this->em->persist($task);
            $this->em->flush();
        }

        return $form;
    }

    public function editTask(Request $request, Task $task): FormInterface
    {
        $form = $this->formFactory->create(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
        }

        return $form;
    }

    public function toggle(Task $task): Task
    {
        $task->toggle();
        $this->em->flush();

        return $task;
    }

    public function deleteTask(Task $task)
    {
        $this->em->remove($task);
        $this->em->flush();
    }
}
