<?php

namespace App\DataFixtures;

use App\Entity\Race;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RaceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $race = new Race();
        $race->setNom("Malinois");
        $manager->persist($race);

        $race2 = new Race();
        $race2->setNom("Samoyede");
        $manager->persist($race2);

        $race3 = new Race();
        $race3->setNom("Chihuahua");
        $manager->persist($race3);

        $race4 = new Race();
        $race4->setNom("Golden retriever");
        $manager->persist($race4);

        $race5 = new Race();
        $race5->setNom("Berger allemand");
        $manager->persist($race5);

        $manager->flush();
    }
}
