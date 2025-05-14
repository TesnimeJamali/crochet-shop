<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;  // <-- ICI : Instruction use
use Symfony\Component\Form\Extension\Core\Type\TelType;  // <-- ICI : Instruction use


class RegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre email.',
                    ]),
                ],
            ])
            ->add('nom', TextType::class, [ // Ajout du champ nom
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom.']),
                ],
            ])
            ->add('prenom', TextType::class, [ // Ajout du champ prénom
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre prénom.']),
                ],
            ])
            ->add('telephone', TelType::class, [ // Ajout du champ téléphone
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre numéro de téléphone.']),
                ],
            ])
            ->add('adresse', TextType::class, [ // Ajout du champ adresse
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre adresse.']),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter nos conditions.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe.',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['registration'],
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'registration_form',
        ]);
    }
}
