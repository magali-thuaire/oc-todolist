<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'user.password.fields',
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'user.password.not_blank',
                        ]),
                        new Length([
                            'max' => 64,
                            'maxMessage' => 'user.password.max',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Tapez le mot de passe à nouveau',
                ],
            ])
        ;
    }
}
