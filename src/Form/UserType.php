<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $builder->getData();
        $isEdit = $user->getId();

        $builder
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
            ])
            ->add('role', ChoiceType::class, [
                'mapped' => false,
                'choices' => array_flip(User::ROLES),
                'data' => ($isEdit && $user->isAdmin()) ? 'ROLE_ADMIN' : 'ROLE_USER',
            ])
        ;

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var User|null $user */
                $user = $event->getData();
                if (!$user) {
                    return;
                }
                $role = $event->getForm()->get('role')->getData();

                $user->setRoles([]);

                if ($role === 'ROLE_ADMIN') {
                    $user->setRoles(['ROLE_ADMIN']);
                }
            }
        );
    }
}
