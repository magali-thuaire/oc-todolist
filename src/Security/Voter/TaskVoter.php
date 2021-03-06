<?php

namespace App\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class TaskVoter extends Voter
{
    public const DELETE = 'TASK_DELETE';
    public const EDIT = 'TASK_EDIT';

    public function __construct(
        private readonly Security $security
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::DELETE, self::EDIT])
               && $subject instanceof Task;
    }

    /**
     * @throws Exception
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$subject instanceof Task) {
            throw new Exception('Wrong type somehow passed');
        }

        if ($this->security->isGranted('ROLE_TASK_MANAGE')) {
            return true;
        }

        // ... (check conditions and return true to grant permission) ...
        return match ($attribute) {
            self::DELETE => $user === $subject->getOwner(),
            self::EDIT => !$subject->isDone(),
            default => false,
        };
    }
}
