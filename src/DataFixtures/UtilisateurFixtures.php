<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UtilisateurFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setLogin("Annaelle")
                    ->setPassword('$2y$13$kPJWBGe6cFSsyEhfzdL/PeR6OlCMMUmykKRIoSXdssM.jIkxWtkpy')
                    ->setRoles(["ROLE_ADMIN"]);
        $manager->persist($utilisateur);

        $utilisateur2 = new Utilisateur();
        $utilisateur2->setLogin("Yohan")
                    ->setPassword('$2y$13$QyIpcdRJqDuOITCawveMwuA9b3Wng1gg3M3xIV/sxPYgnrJCI/RtW')
                    ->setRoles(["ROLE_USER"]);
        $manager->persist($utilisateur2);

        $utilisateur3 = new Utilisateur();
        $utilisateur3->setLogin("Nunes")
                    ->setPassword('$2y$13$KyAhRA9OBaus2XT9Pr6AZ.xqNzmQQTsN7xVdsmdm4FYBBpq8vsvaS')
                    ->setRoles(["ROLE_USER"]);
        $manager->persist($utilisateur3);

        $manager->flush();
    }
}
