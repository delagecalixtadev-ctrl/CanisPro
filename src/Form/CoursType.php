<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\NiveauApprentissage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeEntrainement')
            ->add('description')
            ->add('prix')
            ->add('esCollectif')
            ->add('nbChienMax')
            ->add('duree')
          
            ->add('niveauxApprentissage', EntityType::class, [
                'class' => NiveauApprentissage::class,
                'choice_label' => 'libelle',
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
        ]);
    }
}
