<?php

namespace App\Form;

use App\Entity\Chien;
use App\Entity\Inscription;
use App\Entity\Seance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nb_Chien_Inscrit')
            ->add('seance', EntityType::class, [
                'class' => Seance::class,
                'choice_label' => 'date',
                'multiple' => false,
                'required' => false,
            ])
            ->add('chien', EntityType::class, [
                'class' => Chien::class,
                'choice_label' => 'nom',
                'multiple' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inscription::class,
        ]);
    }
}
