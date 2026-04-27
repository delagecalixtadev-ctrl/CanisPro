<?php

namespace App\Tests\Controller;

use App\Entity\Chien;
use App\Entity\NiveauApprentissage;
use App\Entity\Proprietaire;
use App\Entity\Race;
use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MembreControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        foreach ([Chien::class, Proprietaire::class, Utilisateur::class, Race::class, NiveauApprentissage::class] as $class) {
            foreach ($em->getRepository($class)->findAll() as $entity) {
                $em->remove($entity);
            }
        }
        $em->flush();

        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new Utilisateur())
            ->setLogin('membre_test')
            ->setRoles(['ROLE_USER'])
            ->setPassword($passwordHasher->hashPassword(new Utilisateur(), 'mypassword'));

        $proprietaire = (new Proprietaire())
            ->setNom('Member')
            ->setPrenom('Tester')
            ->setEmail('member@example.com')
            ->setTel('0145789632')
            ->setDateNaissance('1995-07-07')
            ->setAdresse('2 rue Membre')
            ->setCodePostal(75012)
            ->setVille('Paris')
            ->setUser($user);

        $race = (new Race())->setNom('Labrador');
        $niveau = (new NiveauApprentissage())->setLibelle('Intermédiaire');

        $em->persist($user);
        $em->persist($proprietaire);
        $em->persist($race);
        $em->persist($niveau);
        $em->flush();
    }

    private function loginMember(): void
    {
        $container = static::getContainer();
        $user = $container->get('doctrine.orm.entity_manager')->getRepository(Utilisateur::class)->findOneBy(['login' => 'membre_test']);
        self::assertNotNull($user);

        $this->client->loginUser($user);
    }

    public function testMembreAreaIsProtected(): void
    {
        $this->client->request('GET', '/membre/espace-personnel');
        self::assertResponseRedirects('/connexion');
    }

    public function testMembreCanAddChien(): void
    {
        $this->loginMember();

        $crawler = $this->client->request('GET', '/membre/espace-chien/ajoutChien/1');
        self::assertResponseIsSuccessful();

        $race = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Race::class)->findOneBy(['nom' => 'Labrador']);
        $niveau = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(NiveauApprentissage::class)->findOneBy(['libelle' => 'Intermédiaire']);
        $proprietaire = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Proprietaire::class)->findOneBy(['Nom' => 'Member']);

        self::assertNotNull($race);
        self::assertNotNull($niveau);
        self::assertNotNull($proprietaire);

        $form = $crawler->selectButton('Ajouter')->form();
        $form->setValues([
            'chien[nom]' => 'Chappy',
            'chien[dateNaissance]' => '2020-11-10',
            'chien[race]' => $race->getId(),
            'chien[niveauxApprentissage]' => $niveau->getId(),
            'chien[proprietaire]' => $proprietaire->getId(),
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/membre/espace-chien');

        $added = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Chien::class)->findOneBy(['nom' => 'Chappy']);
        self::assertNotNull($added);
    }
}
