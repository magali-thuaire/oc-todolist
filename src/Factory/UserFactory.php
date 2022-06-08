<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<User>
 *
 * @method static     User|Proxy createOne(array $attributes = [])
 * @method static     User[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static     User|Proxy find(object|array|mixed $criteria)
 * @method static     User|Proxy findOrCreate(array $attributes)
 * @method static     User|Proxy first(string $sortedField = 'id')
 * @method static     User|Proxy last(string $sortedField = 'id')
 * @method static     User|Proxy random(array $attributes = [])
 * @method static     User|Proxy randomOrCreate(array $attributes = [])
 * @method static     User[]|Proxy[] all()
 * @method static     User[]|Proxy[] findBy(array $attributes)
 * @method static     User[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static     User[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method User|Proxy create(array|callable $attributes = [])
 */
final class UserFactory extends ModelFactory
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher
    ) {
        parent::__construct();
    }

    public function promoteRole(string $role): self
    {
        return $this->addState([
            'roles' => [$role],
        ]);
    }

    protected function getDefaults(): array
    {
        return [
            'createdAt' => self::faker()->dateTimeBetween('-60 days', '-30 days'),
            'updatedAt' => self::faker()->dateTimeBetween('-15 days', '-1 days'),
            'username' => self::faker()->text(25),
            'email' => self::faker()->email(),
            'plainPassword' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            ->afterInstantiate(function (User $user): void {
                if ($plainPassword = $user->getPlainPassword()) {
                    $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));
                    $user->eraseCredentials();
                }
            });
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
