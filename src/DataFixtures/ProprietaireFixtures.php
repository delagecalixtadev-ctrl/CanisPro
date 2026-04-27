<?php

namespace App\DataFixtures;

use App\Entity\Proprietaire;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProprietaireFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $proprietaire = new Proprietaire();
        $proprietaire->setNom("Nunes")
                    ->setPrenom("Florence")
                    ->setEmail("sjp.nunes.florence@gmail.com")
                    ->setTel("0600000000")
                    ->setDateNaissance("01/01/1900")
                    ->setAdresse("St John Perse")
                    ->setCodePostal("64000")
                    ->setVille("Pau");
        $manager->persist($proprietaire);

        $proprietaire2 = new Proprietaire();
        $proprietaire2->setNom("Iribarren")
                    ->setPrenom("Yohan")
                    ->setEmail("iribarren.yohan@gmail.comm")
                    ->setTel("06752672582")
                    ->setDateNaissance("18/12/1998")
                    ->setAdresse("3 rue chantilly")
                    ->setCodePostal("64000")
                    ->setVille("Pau");
        $manager->persist($proprietaire2);

        $manager->flush();
    }
}
