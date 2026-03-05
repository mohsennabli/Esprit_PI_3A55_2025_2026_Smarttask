<?php

namespace App\Form;

use App\Entity\Formation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex : Gestion du temps et productivité',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                    'rows'  => 4,
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('dateFin', DateType::class, [
                'label'  => 'Date de fin',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('duree', IntegerType::class, [
                'label'    => 'Durée (heures)',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'min'         => 0,
                    'placeholder' => 'Ex : 20',
                ],
            ])
            ->add('niveau', ChoiceType::class, [
                'label'   => 'Niveau',
                'choices' => [
                    'Débutant'      => Formation::NIVEAU_DEBUTANT,
                    'Intermédiaire' => Formation::NIVEAU_INTERMEDIAIRE,
                    'Avancé'        => Formation::NIVEAU_AVANCE,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('categorie', TextType::class, [
                'label'    => 'Catégorie',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex : Organisation, Productivité…',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'Active'   => Formation::STATUT_ACTIVE,
                    'Terminée' => Formation::STATUT_TERMINEE,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('capacity', IntegerType::class, [
                'label'    => 'Capacité max. (inscriptions)',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'min'         => 1,
                    'placeholder' => 'Illimité si vide',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
