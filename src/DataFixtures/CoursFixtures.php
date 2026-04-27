<?php

namespace App\DataFixtures;

use App\Entity\Cours;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CoursFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cours = new Cours();
        $cours ->setTypeEntrainement("Sociabilisation")
                ->setDescription("Apprennez a vos doggos a être ami")
                ->setPrix(40)
                ->setEsCollectif(true)
                ->setNbChienMax(15)
                ->setDuree(45);
        $manager->persist($cours);

        $cours2 = new Cours();
        $cours2 ->setTypeEntrainement("Sociabilisation")
                ->setDescription("Apprennez a votre doggo a être ami avec d'autres")
                ->setPrix(60)
                ->setEsCollectif(false)
                ->setNbChienMax(1)
                ->setDuree(45);
        $manager->persist($cours2);

        $cours3 = new Cours();
        $cours3 ->setTypeEntrainement("Agility")
                ->setDescription("Apprennez a vos doggos a être usain bolt")
                ->setPrix(50)
                ->setEsCollectif(true)
                ->setNbChienMax(15)
                ->setDuree(60);
        $manager->persist($cours3);

        $cours4 = new Cours();
        $cours4 ->setTypeEntrainement("Agility")
                ->setDescription("Apprennez a votre doggo a être usain bolt")
                ->setPrix(60)
                ->setEsCollectif(false)
                ->setNbChienMax(1)
                ->setDuree(60);
        $manager->persist($cours4);

        $cours5 = new Cours();
        $cours5 ->setTypeEntrainement("Obeissance")
                ->setDescription("Apprennez a vos doggos a être de bons servants")
                ->setPrix(50)
                ->setEsCollectif(true)
                ->setNbChienMax(15)
                ->setDuree(90);
        $manager->persist($cours5);

        $cours6 = new Cours();
        $cours6 ->setTypeEntrainement("Obeissance")
                ->setDescription("Apprennez a votre doggo a être un bon servant")
                ->setPrix(60)
                ->setEsCollectif(false)
                ->setNbChienMax(1)
                ->setDuree(90);
        $manager->persist($cours6);

        $manager->flush();
    }
}
