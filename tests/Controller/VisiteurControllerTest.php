<?php

namespace App\Tests\Controller;

use App\Entity\Proprietaire;
use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class VisiteurControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        foreach ($em->getRepository(Proprietaire::class)->findAll() as $entity) {
            $em->remove($entity);
        }
        foreach ($em->getRepository(Utilisateur::class)->findAll() as $entity) {
            $em->remove($entity);
        }

        $em->flush();
    }

    public function testVisitorPublicPagesAreAccessible(): void
    {
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/cours');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/information');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/contact');
        self::assertResponseIsSuccessful();
    }

    public function testRegistrationFailsOnPasswordMismatch(): void
    {
        $crawler = $this->client->request('GET', '/inscription');
        self::assertResponseIsSuccessful();

        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->submitForm('INSCRIPTION', [
            'nom' => 'Mauvais',
            'prenom' => 'Motdepasse',
            'email' => 'mismatch@example.com',
            'date_naissance' => '02/03/1990',
            'tel' => '0123456789',
            'adresse' => '10 rue Erreur',
            'code_postal' => '75010',
            'ville' => 'Paris',
            'login' => 'mismatch',
            'password' => 'coucou123',
            'password_confirm' => 'coucou456',
            '_token' => $token,
        ]);

        self::assertResponseRedirects('/inscription');

        $this->client->followRedirect();

        // L'utilisateur ne doit pas être créé en cas de mismatch mot de passe.
        $user = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Utilisateur::class)->findOneBy(['login' => 'mismatch']);
        self::assertNull($user);
    }

    public function testRegistrationSucceedsAndCreatesUser(): void
    {
        $crawler = $this->client->request('GET', '/inscription');
        self::assertResponseIsSuccessful();

        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->submitForm('INSCRIPTION', [
            'nom' => 'Functional',
            'prenom' => 'Tester',
            'email' => 'functional@example.com',
            'date_naissance' => '03/03/1990',
            'tel' => '0123456789',
            'adresse' => '1 avenue Test',
            'code_postal' => '75011',
            'ville' => 'Paris',
            'login' => 'functional_user',
            'password' => 'functional123',
            'password_confirm' => 'functional123',
            '_token' => $token,
        ]);

        self::assertResponseRedirects('/membre/espace-personnel');

        $user = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(Utilisateur::class)->findOneBy(['login' => 'functional_user']);
        self::assertNotNull($user);
    }
}
