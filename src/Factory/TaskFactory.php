<?php

namespace App\Factory;

use App\Entity\Task;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Task>
 *
 * @method static     Task|Proxy createOne(array $attributes = [])
 * @method static     Task[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static     Task|Proxy find(object|array|mixed $criteria)
 * @method static     Task|Proxy findOrCreate(array $attributes)
 * @method static     Task|Proxy first(string $sortedField = 'id')
 * @method static     Task|Proxy last(string $sortedField = 'id')
 * @method static     Task|Proxy random(array $attributes = [])
 * @method static     Task|Proxy randomOrCreate(array $attributes = [])
 * @method static     Task[]|Proxy[] all()
 * @method static     Task[]|Proxy[] findBy(array $attributes)
 * @method static     Task[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static     Task[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method Task|Proxy create(array|callable $attributes = [])
 */
final class TaskFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            'createdAt' => self::faker()->dateTimeBetween('-30 days', '-15 days'),
            'title' => self::faker()->text(20),
            'content' => self::faker()->paragraph(3),
            'isDone' => self::faker()->boolean(),
            'owner' => UserFactory::random()
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this;
    }

    protected static function getClass(): string
    {
        return Task::class;
    }
}
