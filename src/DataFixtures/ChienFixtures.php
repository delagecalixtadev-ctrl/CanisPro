<?php

namespace App\DataFixtures;

use App\Entity\Chien;
use App\Entity\Race;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ChienFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {   
        $chien = new Chien();
        $chien ->setNom("Pinto")
                ->setDateNaissance("10/03/2024");
        $manager->persist($chien);

        $chien2 = new Chien();
        $chien2 ->setNom("Rex")
                ->setDateNaissance("20/10/2020");
        $manager->persist($chien2);

        $chien3 = new Chien();
        $chien3 ->setNom("Medor")
                ->setDateNaissance("02/01/2025");
        $manager->persist($chien3);

        $chien4 = new Chien();
        $chien4 ->setNom("Calixta")
                ->setDateNaissance("15/11/2004");
        $manager->persist($chien4);

        $chien5 = new Chien();
        $chien5 ->setNom("Marina")
                ->setDateNaissance("01/01/2004");
        $manager->persist($chien5);

        $manager->flush();
    }
}
