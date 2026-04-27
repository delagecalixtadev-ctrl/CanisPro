<?php

namespace App\Controller;

use App\Entity\Proprietaire;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SeanceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * VisiteurController - Contrôleur dédié aux pages publiques de l'application CanisPro.
 *
 * Ce contrôleur gère toutes les pages accessibles sans authentification,
 * c'est-à-dire les pages destinées aux visiteurs non connectés de l'école
 * de dressage canin.
 *
 * Fonctionnalités couvertes :
 * - Page d'accueil publique
 * - Consultation des cours et des séances disponibles
 * - Pages d'information et de contact
 * - Inscription d'un nouveau membre (création compte Utilisateur + Proprietaire)
 *
 * Contrairement à AdminController et MembreController, ce contrôleur n'a pas
 * de préfixe de route au niveau de la classe — chaque route est définie
 * individuellement depuis la racine "/".
 *
 * Aucune authentification n'est requise pour accéder aux routes de ce contrôleur.
 * La sécurité d'accès est gérée au niveau du pare-feu Symfony (security.yaml).
 *
 * @package App\Controller
 */
final class VisiteurController extends AbstractController
{
    // ==========================================================================
    //  SECTION PAGES STATIQUES
    // ==========================================================================

    /**
     * Page d'accueil publique de l'application CanisPro.
     *
     * Point d'entrée principal du site, accessible à l'URL racine "/".
     * Affiche la page d'accueil générale présentant l'école de dressage canin.
     * Aucune donnée dynamique n'est transmise à la vue.
     *
     * Route : GET /
     *
     * @return Response La vue "visiteur/index.html.twig".
     */
    #[Route('', name: 'app_visiteur')]
    public function index(): Response
    {
        return $this->render('visiteur/index.html.twig');
    }

    /**
     * Page de présentation des cours proposés par l'école.
     *
     * Affiche la liste statique ou dynamique des cours disponibles.
     * Actuellement, aucune donnée n'est récupérée depuis la base —
     * la vue reçoit uniquement le nom du contrôleur. Cette méthode
     * devrait idéalement injecter un CoursRepository pour afficher
     * les cours réels depuis la base de données.
     *
     * Route : GET /cours
     *
     * @return Response La vue "visiteur/listeCours.html.twig".
     */
    #[Route('/cours', name: 'app_listeCours')]
    public function cours(): Response
    {
        return $this->render('visiteur/listeCours.html.twig', [
            'controller_name' => 'VisiteurController',
        ]);
    }

    /**
     * Page listant toutes les séances disponibles à venir.
     *
     * Récupère l'intégralité des séances depuis la base de données via
     * SeanceRepository::findAll() et les transmet à la vue. Cette page
     * permet aux visiteurs de consulter le planning des séances avant
     * de s'inscrire ou de créer un compte.
     *
     * Note : findAll() retourne toutes les séances sans filtre de date.
     * Il serait pertinent d'ajouter une méthode dans SeanceRepository
     * (ex: findUpcoming()) pour n'afficher que les séances futures,
     * triées chronologiquement.
     *
     * Route : GET /seance
     *
     * @param SeanceRepository $seanceRepository Repository injecté pour récupérer les séances.
     * @return Response La vue "visiteur/seance.html.twig" avec toutes les séances.
     */
    #[Route('/seance', name: 'app_seance')]
    public function seance(SeanceRepository $seanceRepository): Response
    {
        $seance = $seanceRepository->findAll();

        return $this->render('visiteur/seance.html.twig', [
            'seances' => $seance,
        ]);
    }

    /**
     * Page d'information générale sur l'école CanisPro.
     *
     * Affiche une page statique contenant les informations sur l'école
     * (présentation, méthodes pédagogiques, équipe, etc.).
     * Aucune donnée dynamique n'est transmise à la vue.
     *
     * Route : GET /information
     *
     * @return Response La vue "visiteur/information.html.twig".
     */
    #[Route('/information', name: 'app_information')]
    public function information(): Response
    {
        return $this->render('visiteur/information.html.twig', [
            'controller_name' => 'VisiteurController',
        ]);
    }

    /**
     * Page de contact de l'école CanisPro.
     *
     * Affiche une page statique avec les coordonnées de l'école
     * (adresse, téléphone, email, formulaire de contact, etc.).
     * Aucune donnée dynamique n'est transmise à la vue.
     *
     * Note : si un formulaire de contact est présent dans la vue,
     * cette méthode devrait gérer le POST et l'envoi d'email via
     * le composant Mailer de Symfony.
     *
     * Route : GET /contact
     *
     * @return Response La vue "visiteur/contact.html.twig".
     */
    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('visiteur/contact.html.twig', [
            'controller_name' => 'VisiteurController',
        ]);
    }

    // ==========================================================================
    //  SECTION INSCRIPTION
    // ==========================================================================

    /**
     * Gère l'inscription d'un nouveau membre sur la plateforme CanisPro.
     *
     * Cette méthode est le point central de création de compte. Elle crée
     * simultanément deux entités liées : un Utilisateur (compte de connexion)
     * et un Proprietaire (profil personnel du membre).
     *
     * ── Fonctionnement en GET ────────────────────────────────────────────────
     * Affiche simplement le formulaire d'inscription HTML (visiteur/inscription.html.twig).
     * Aucun FormType Symfony n'est utilisé ici — le formulaire est géré manuellement
     * via les données brutes de la requête POST.
     *
     * ── Fonctionnement en POST ───────────────────────────────────────────────
     * Le traitement se déroule en plusieurs étapes :
     *
     * 1. RÉCUPÉRATION DES CHAMPS
     *    Tous les champs du formulaire sont extraits depuis $request->request->get().
     *    Les champs email et tel utilisent l'opérateur ?: null pour stocker NULL
     *    en base si la valeur est vide (champs optionnels).
     *    Le code postal est casté en (int) pour garantir le bon type.
     *
     * 2. VALIDATION CSRF
     *    Vérifie que le token "_token" soumis correspond au token "register"
     *    généré côté serveur. En cas d'échec, un message flash "error" est ajouté
     *    et l'utilisateur est redirigé vers le formulaire.
     *
     * 3. VALIDATIONS MÉTIER
     *    - Correspondance des deux mots de passe saisis.
     *    - Longueur minimale du mot de passe (8 caractères).
     *    Chaque erreur ajoute un message flash "error" et redirige vers le formulaire.
     *
     * 4. CRÉATION DE L'UTILISATEUR
     *    Instancie un Utilisateur, définit le login, attribue le rôle ROLE_USER
     *    et hache le mot de passe via UserPasswordHasherInterface.
     *    Le hachage est effectué après la création de l'objet $user car
     *    hashPassword() requiert l'entité pour accéder à l'algorithme configuré.
     *
     * 5. CRÉATION DU PROPRIETAIRE
     *    Instancie un Proprietaire et renseigne toutes ses propriétés personnelles.
     *    La liaison avec l'Utilisateur est établie via setUser($user) côté Proprietaire.
     *    La relation inverse (Utilisateur → Proprietaire) est gérée automatiquement
     *    si le cascade est configuré dans l'entité.
     *
     * 6. PERSISTANCE
     *    Les deux entités sont persistées explicitement ($em->persist()) bien que
     *    le cascade: ['persist'] sur la relation suffise. Cette double persistance
     *    est intentionnelle pour la clarté du code. Un seul flush() envoie
     *    les deux INSERT en base dans la même transaction.
     *
     * 7. REDIRECTION
     *    Après succès, un message flash "success" est ajouté et l'utilisateur
     *    est redirigé vers son espace personnel (route 'espace_personnel').
     *
     * ── Améliorations possibles ──────────────────────────────────────────────
     * - Vérifier l'unicité du login et de l'email avant création (éviter les doublons
     *   en base qui lèveraient une exception Doctrine non gérée).
     * - Utiliser un FormType Symfony (RegisterType) pour bénéficier de la validation
     *   automatique, des contraintes et de la protection CSRF intégrée.
     * - Connecter automatiquement l'utilisateur après inscription via le composant
     *   Security de Symfony (login programmatique).
     *
     * Route : GET|POST /inscription
     *
     * @param Request                     $request        La requête HTTP (GET pour affichage, POST pour traitement).
     * @param EntityManagerInterface      $em             Le gestionnaire Doctrine pour persister les entités.
     * @param UserPasswordHasherInterface $passwordHasher Service de hachage du mot de passe.
     * @return Response Formulaire d'inscription (GET), redirection après succès ou erreur (POST).
     */
    #[Route('/inscription', name: 'inscription', methods: ['GET', 'POST'])]
    public function inscription(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($request->isMethod('POST')) {

            // ── Récupération des champs ──────────────────────────────
            $login           = $request->request->get('login');
            $email           = $request->request->get('email')    ?: null;
            $password        = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');
            $nom             = $request->request->get('nom');
            $prenom          = $request->request->get('prenom');
            $dateNaissance   = $request->request->get('date_naissance');
            $tel             = $request->request->get('tel')       ?: null;
            $adresse         = $request->request->get('adresse');
            $codePostal      = (int) $request->request->get('code_postal');
            $ville           = $request->request->get('ville');

            // ── Validation CSRF ──────────────────────────────────────
            if (!$this->isCsrfTokenValid('register', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('inscription');
            }

            // ── Validations basiques ─────────────────────────────────
            if ($password !== $passwordConfirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('inscription');
            }

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('inscription');
            }

            // ── Création de l'Utilisateur ────────────────────────────
            $user = new Utilisateur();
            $user->setLogin($login)
                 ->setRoles(['ROLE_USER'])
                 ->setPassword($passwordHasher->hashPassword($user, $password));

            // ── Création du Proprietaire ─────────────────────────────
            $proprietaire = new Proprietaire();
            $proprietaire->setNom($nom)
                         ->setPrenom($prenom)
                         ->setEmail($email)
                         ->setTel($tel)
                         ->setDateNaissance($dateNaissance)
                         ->setAdresse($adresse)
                         ->setCodePostal($codePostal)
                         ->setVille($ville)
                         ->setUser($user);

            // ── Persistance ──────────────────────────────────────────
            $em->persist($user);
            $em->persist($proprietaire);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès !');
            return $this->redirectToRoute('espace_personnel');
        }

        // ── GET : affichage du formulaire ────────────────────────────
        return $this->render('visiteur/inscription.html.twig');
    }
}