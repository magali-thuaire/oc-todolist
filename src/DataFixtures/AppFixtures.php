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
        // Admin
        UserFactory::new([
            'email' => 'admin@todolist.fr',
            'username' => 'admin',
            'plainPassword' => 'todolist',
        ])
            ->promoteRole('ROLE_ADMIN')
            ->create()
        ;

        // User
        $user = UserFactory::createOne([
               'email' => 'user@todolist.fr',
               'username' => 'user',
               'plainPassword' => 'todolist',
           ]);

        TaskFactory::createMany(
            10,
            ['owner' => $user]
        );

        // Others
        UserFactory::createMany(10);
        TaskFactory::createMany(10);

        // Task without owner
        TaskFactory::createMany(
            10,
            ['owner' => null]
        );
    }
}
