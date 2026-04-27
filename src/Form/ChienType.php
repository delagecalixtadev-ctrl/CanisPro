<?php

namespace App\Form;

use App\Entity\Chien;
use App\Entity\Inscription;
use App\Entity\NiveauApprentissage;
use App\Entity\Proprietaire;
use App\Entity\Race;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChienType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('dateNaissance')
            ->add('race', EntityType::class, [
                'class' => Race::class,
                'choice_label' => 'nom',
            ])
            ->add('niveauxApprentissage', EntityType::class, [
                'class' => NiveauApprentissage::class,
                'choice_label' => 'libelle',
            ])
            ->add('proprietaire', EntityType::class, [
                'class' => Proprietaire::class,
                'choice_label' =>function(Proprietaire $unProprietaire) {
        return $unProprietaire->getNom() . ' ' . $unProprietaire->getPrenom();}
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
            'data_class' => Chien::class,
        ]);
    }
}
