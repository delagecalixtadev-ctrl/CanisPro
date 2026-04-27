<?php

namespace App\DataFixtures;

use App\Entity\NiveauApprentissage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NiveauApprentissageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $niveauApprentissage = new NiveauApprentissage();
        $niveauApprentissage->setLibelle("chiot");
        $manager->persist($niveauApprentissage);

        $niveauApprentissage2 = new NiveauApprentissage();
        $niveauApprentissage2->setLibelle("débutant");
        $manager->persist($niveauApprentissage2);

        $niveauApprentissage3 = new NiveauApprentissage();
        $niveauApprentissage3->setLibelle("confirmé");
        $manager->persist($niveauApprentissage3);
        
        $manager->flush();
    }
}
