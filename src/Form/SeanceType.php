<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Inscription;
use App\Entity\Seance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date')
            ->add('heure')
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => function(Cours $unCours) {
            return $unCours->getTypeEntrainement() . ' (' . ($unCours->isEsCollectif()? "Collectif":"Individuel").")";}
            ])
            ->add('inscriptions', EntityType::class, [
                'class' => Inscription::class,
                'choice_label' => 'id',
                'multiple' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seance::class,
        ]);
    }
}
