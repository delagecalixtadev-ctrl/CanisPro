<?php

namespace App\Tests\Controller;

use App\Entity\Chien;
use App\Entity\NiveauApprentissage;
use App\Entity\Proprietaire;
use App\Entity\Race;
use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminControllerTest extends WebTestCase
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

        $userAdmin = (new Utilisateur())
            ->setLogin('admin_test')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($passwordHasher->hashPassword(new Utilisateur(), 'adminpass'));

        $proprietaire = (new Proprietaire())
            ->setNom('Admin')
            ->setPrenom('Testing')
            ->setEmail('admin@example.com')
            ->setTel('0102030405')
            ->setDateNaissance('1980-01-01')
            ->setAdresse('1 rue Admin')
            ->setCodePostal(75000)
            ->setVille('Paris')
            ->setUser($userAdmin);

        $race = (new Race())->setNom('Beagle');
        $niveau = (new NiveauApprentissage())->setLibelle('Débutant');

        $em->persist($userAdmin);
        $em->persist($proprietaire);
        $em->persist($race);
        $em->persist($niveau);
        $em->flush();
    }

    private function loginAdmin(): void
    {
        $container = static::getContainer();
        $user = $container->get('doctrine.orm.entity_manager')->getRepository(Utilisateur::class)->findOneBy(['login' => 'admin_test']);
        self::assertNotNull($user);

        $this->client->loginUser($user);
    }

    public function testAdminPageRequiresAdminRole(): void
    {
        $this->client->request('GET', '/admin/chiens');

        self::assertResponseRedirects('/connexion');
    }

    public function testAdminCanCreateModifyAndDeleteChien(): void
    {
        $this->loginAdmin();

        $crawler = $this->client->request('GET', '/admin/chiens/ajout');
        self::assertResponseIsSuccessful();

        $race = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Race::class)->findOneBy(['nom' => 'Beagle']);
        $niveau = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(NiveauApprentissage::class)->findOneBy(['libelle' => 'Débutant']);
        $proprietaire = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Proprietaire::class)->findOneBy(['Nom' => 'Admin']);

        self::assertNotNull($race);
        self::assertNotNull($niveau);
        self::assertNotNull($proprietaire);

        $form = $crawler->selectButton('Ajouter')->form();
        $form->setValues([
            'chien[nom]' => 'Rex',
            'chien[dateNaissance]' => '2021-12-01',
            'chien[race]' => $race->getId(),
            'chien[niveauxApprentissage]' => $niveau->getId(),
            'chien[proprietaire]' => $proprietaire->getId(),
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/chiens');
        $this->client->followRedirect();
        self::assertSelectorTextContains('table', 'Rex');

        $chien = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Chien::class)->findOneBy(['nom' => 'Rex']);
        self::assertNotNull($chien);

        $crawler = $this->client->request('GET', '/admin/chiens/' . $chien->getId());
        self::assertResponseIsSuccessful();

        // Edit command
        $crawler = $this->client->request('GET', '/admin/chiens/modification/' . $chien->getId());
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form();
        $form->setValues([
            'chien[nom]' => 'RexUpdated',
            'chien[dateNaissance]' => '2021-12-01',
            'chien[race]' => $race->getId(),
            'chien[niveauxApprentissage]' => $niveau->getId(),
            'chien[proprietaire]' => $proprietaire->getId(),
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/chiens');

        $chienUpdated = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Chien::class)->findOneBy(['nom' => 'RexUpdated']);
        self::assertNotNull($chienUpdated);

        // Delete command
        $crawler = $this->client->request('GET', '/admin/chiens/' . $chienUpdated->getId());
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/chiens/suppression/' . $chienUpdated->getId(), ['_token' => $token]);

        self::assertResponseRedirects('/admin/chiens');

        $deleted = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Chien::class)->find($chienUpdated->getId());
        self::assertNull($deleted);
    }
}
