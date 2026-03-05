<?php

namespace App\Form;

use App\Entity\Formation;
use App\Entity\Inscription;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'label' => 'Utilisateur',
                'choice_label' => function (User $user): string {
                    return $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'placeholder' => 'Choisir un utilisateur',
                'attr' => [
                    'class' => 'form-select'
                ],
            ])

            ->add('formation', EntityType::class, [
                'class' => Formation::class,
                'label' => 'Formation',
                'choice_label' => 'titre',
                'placeholder' => 'Choisir une formation',
                'attr' => [
                    'class' => 'form-select'
                ],
            ])

            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En cours' => Inscription::STATUT_EN_COURS,
                    'Complétée' => Inscription::STATUT_COMPLETEE,
                    'Abandonnée' => Inscription::STATUT_ABANDONNEE,
                    'Absent' => Inscription::STATUT_ABSENT,
                ],
                'placeholder' => 'Choisir un statut',
                'attr' => [
                    'class' => 'form-select'
                ],
            ])

            ->add('progression', IntegerType::class, [
                'label' => 'Progression (%)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 100,
                ],
            ])

            ->add('certificat', CheckboxType::class, [
                'label' => 'Certificat obtenu',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inscription::class,
        ]);
    }
}