<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * SecuritéController - Contrôleur gérant l'authentification des utilisateurs CanisPro.
 *
 * Ce contrôleur prend en charge les deux points d'entrée liés à la sécurité
 * de l'application : la connexion et la déconnexion des utilisateurs.
 *
 * Il s'appuie entièrement sur le composant Security de Symfony, configuré
 * dans security.yaml. Les routes définies ici doivent correspondre exactement
 * aux valeurs "login_path", "check_path" et "logout.path" du pare-feu,
 * par exemple :
 *
 *   security:
 *       firewalls:
 *           main:
 *               form_login:
 *                   login_path: connexion
 *                   check_path: connexion
 *               logout:
 *                   path: deconnexion
 *
 * Note : le nom de la classe contient un caractère accentué ("é") ce qui,
 * bien que fonctionnel en PHP 8, est déconseillé par les standards PSR.
 * Il est recommandé de renommer la classe en "SecurityController" pour
 * la cohérence et la compatibilité avec tous les outils (PHPStan, Rector,
 * PHPDocumentor, etc.).
 *
 * @package App\Controller
 */
class SecuritéController extends AbstractController
{
    /**
     * Affiche le formulaire de connexion et gère les erreurs d'authentification.
     *
     * Cette méthode ne traite PAS directement les identifiants soumis —
     * c'est le pare-feu Symfony (configuré dans security.yaml via "form_login")
     * qui intercepte la requête POST sur "check_path" et vérifie les credentials
     * avant que cette méthode ne soit appelée.
     *
     * Cette méthode est uniquement responsable de :
     *
     * 1. Récupérer la dernière erreur d'authentification via
     *    AuthenticationUtils::getLastAuthenticationError().
     *    Cette erreur est stockée en session par le pare-feu après un échec
     *    de connexion (ex : "Invalid credentials.", "Too many login attempts.").
     *    Retourne null s'il n'y a pas eu d'échec précédent.
     *
     * 2. Récupérer le dernier login saisi par l'utilisateur via
     *    AuthenticationUtils::getLastUsername(), afin de pré-remplir
     *    le champ login du formulaire après un échec et éviter à l'utilisateur
     *    de le ressaisir.
     *
     * 3. Transmettre ces deux informations à la vue Twig pour affichage.
     *
     * ── Cycle complet d'authentification ────────────────────────────────────
     * 1. L'utilisateur accède à /connexion (GET) → cette méthode affiche le formulaire.
     * 2. L'utilisateur soumet ses identifiants (POST sur /connexion).
     * 3. Le pare-feu Symfony intercepte la requête POST et vérifie les credentials.
     * 4a. Succès → redirection automatique vers "default_target_path" (security.yaml).
     * 4b. Échec  → le pare-feu stocke l'erreur en session et redirige vers
     *              /connexion (GET), cette méthode affiche à nouveau le formulaire
     *              avec l'erreur et le login pré-rempli.
     *
     * Route : GET /connexion
     *
     * @param AuthenticationUtils $authenticationUtils Service Symfony permettant de
     *                                                  récupérer les informations de
     *                                                  la dernière tentative de connexion
     *                                                  (erreur éventuelle et login saisi).
     * @return Response La vue "securité/login.html.twig" avec :
     *                  - "last_username" : le dernier login saisi (string)
     *                  - "error"         : l'erreur d'auth éventuelle (AuthenticationException|null)
     */
    #[Route(path: '/connexion', name: 'connexion')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupère l'erreur d'authentification si la tentative précédente a échoué
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupère le dernier login saisi pour pré-remplir le champ dans la vue
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('securité/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    /**
     * Point de déconnexion de l'utilisateur authentifié.
     *
     * Cette méthode est intentionnellement vide — elle ne sera JAMAIS exécutée.
     * Le pare-feu Symfony intercepte toute requête vers "/deconnexion" AVANT
     * qu'elle n'atteigne ce contrôleur, grâce à la clé "logout" configurée
     * dans security.yaml.
     *
     * Le pare-feu se charge alors automatiquement de :
     * - Invalider la session PHP de l'utilisateur (session_destroy).
     * - Supprimer le token de sécurité du TokenStorage.
     * - Supprimer le cookie "Remember Me" si la fonctionnalité est activée.
     * - Rediriger vers la route définie dans "target" (par défaut : "/").
     *
     * La LogicException levée dans le corps de la méthode sert de filet
     * de sécurité explicite : si cette méthode est appelée directement
     * (ce qui ne devrait JAMAIS arriver en configuration correcte du pare-feu),
     * une erreur claire est levée plutôt qu'un comportement silencieux inattendu.
     * C'est le pattern standard généré par "make:auth" de Symfony.
     *
     * ── Vérification de la configuration security.yaml ──────────────────────
     * Si cette exception est effectivement levée en production, cela indique
     * que la clé "logout.path" dans security.yaml ne correspond pas au nom
     * de la route "deconnexion" définie ici.
     *
     * Route : GET /deconnexion
     *
     * @throws \LogicException Systématiquement — cette méthode ne doit jamais
     *                         être exécutée. Si elle l'est, cela indique une
     *                         mauvaise configuration du pare-feu dans security.yaml.
     * @return void Cette méthode ne retourne rien — elle lève toujours une exception.
     */
    #[Route(path: '/deconnexion', name: 'deconnexion')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}