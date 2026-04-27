<?php

namespace App\Tests;

use App\Entity\Proprietaire;
use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $proprietaireRepository = $em->getRepository(Proprietaire::class);
        foreach ($proprietaireRepository->findAll() as $proprietaire) {
            $em->remove($proprietaire);
        }

        $userRepository = $em->getRepository(Utilisateur::class);
        foreach ($userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new Utilisateur())
            ->setLogin('testuser')
            ->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $proprietaire = (new Proprietaire())
            ->setNom('Test')
            ->setPrenom('User')
            ->setEmail('email@example.com')
            ->setTel('0123456789')
            ->setDateNaissance('01/01/1990')
            ->setAdresse('1 rue Test')
            ->setCodePostal(75000)
            ->setVille('Paris')
            ->setUser($user);

        $em->persist($proprietaire);
        $em->persist($user);
        $em->flush();
    }

    public function testConnexionPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/connexion');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_username"]');
        self::assertSelectorExists('input[name="_password"]');
        self::assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testLoginFailsWithInvalidCredentials(): void
    {
        $this->client->request('GET', '/connexion');

        $this->client->submitForm('CONNEXION', [
            '_username' => 'doesNotExist',
            '_password' => 'bad-password',
        ]);

        self::assertResponseRedirects('/connexion');
        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert-danger', 'Invalid credentials.');
    }

    public function testLoginSucceedsWithValidCredentials(): void
    {
        $this->client->request('GET', '/connexion');

        $this->client->submitForm('CONNEXION', [
            '_username' => 'testuser',
            '_password' => 'password',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.alert-danger');
    }

    public function testProtectedMembrePageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/membre/espace-personnel');

        self::assertResponseRedirects('/connexion');
    }

    public function testAuthenticatedUserCanAccessMembrePage(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['login' => 'testuser']);

        self::assertNotNull($user);

        $this->client->loginUser($user);
        $this->client->request('GET', '/membre/espace-personnel');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.welcome-title', 'Bienvenue, User');
    }

    public function testRegistrationWorks(): void
    {
        $crawler = $this->client->request('GET', '/inscription');
        self::assertResponseIsSuccessful();

        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->submitForm('INSCRIPTION', [
            'nom' => 'New',
            'prenom' => 'User',
            'email' => 'newuser@example.com',
            'date_naissance' => '02/02/1992',
            'tel' => '0123456789',
            'adresse' => '2 rue du Test',
            'code_postal' => '75002',
            'ville' => 'Paris',
            'login' => 'newuser',
            'password' => 'newuserpass',
            'password_confirm' => 'newuserpass',
            '_token' => $token,
        ]);

        self::assertResponseRedirects('/membre/espace-personnel');

        $container = static::getContainer();
        $user = $container->get('doctrine.orm.entity_manager')->getRepository(Utilisateur::class)->findOneBy(['login' => 'newuser']);

        self::assertNotNull($user);
    }
}
