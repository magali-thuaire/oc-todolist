<?php

namespace App\DataFixtures;

use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Users
        UserFactory::createOne([
               'email' => 'user@todolist.fr',
               'username' => 'user',
               'plainPassword' => 'todolist',
           ]);

        UserFactory::createMany(10);

        // Tasks
        TaskFactory::createMany(20);
    }
}
