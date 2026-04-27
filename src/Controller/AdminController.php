<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use App\Entity\Chien;
use App\Form\ChienType;
use App\Repository\ChienRepository;
use App\Entity\Seance;
use App\Form\SeanceType;
use App\Repository\SeanceRepository;
use App\Entity\Race;
use App\Form\RaceType;
use App\Repository\RaceRepository;
use App\Entity\Proprietaire;
use App\Form\ProprietaireType;
use App\Repository\ProprietaireRepository;
use App\Entity\NiveauApprentissage;
use App\Form\NiveauApprentissageType;
use App\Repository\NiveauApprentissageRepository;
use App\Entity\Inscription;
use App\Form\InscriptionType;
use App\Repository\InscriptionRepository;
use App\Entity\Cours;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * AdminController - Contrôleur principal de l'espace d'administration.
 *
 * Ce contrôleur centralise toutes les opérations CRUD (Create, Read, Update, Delete)
 * pour l'ensemble des entités de l'application de gestion d'une école de dressage canin.
 *
 * Entités gérées :
 * - Chien           : les chiens inscrits dans l'école
 * - Utilisateur     : les comptes utilisateurs de l'application
 * - Séance          : les séances de cours planifiées
 * - Race            : les races de chiens référencées
 * - Propriétaire    : les propriétaires des chiens
 * - NiveauApprentissage : les niveaux de progression disponibles
 * - Inscription     : les inscriptions des chiens aux cours
 * - Cours           : les cours proposés par l'école
 *
 * Toutes les routes sont préfixées par "/admin" grâce à l'attribut #[Route('/admin')]
 * posé au niveau de la classe.
 *
 * Ce contrôleur étend AbstractController de Symfony, ce qui donne accès aux helpers
 * classiques : render(), redirectToRoute(), createForm(), isCsrfTokenValid(), etc.
 *
 * @package App\Controller
 */
#[Route('/admin')]
final class AdminController extends AbstractController
{
    // ==========================================================================
    //  SECTION CHIENS
    // ==========================================================================

    /**
     * Liste tous les chiens enregistrés dans la base de données.
     *
     * Récupère l'intégralité des entités Chien via le repository et les transmet
     * à la vue Twig "chien/index.html.twig" pour affichage sous forme de tableau.
     *
     * Route : GET /admin/chiens
     *
     * @param ChienRepository $chienRepository Service repository injecté automatiquement
     *                                          par Symfony pour accéder aux données Chien.
     * @return Response La page HTML listant tous les chiens.
     */
    #[Route('/chiens', name: 'adminListeChiens', methods: ['GET'])]
    public function index(ChienRepository $chienRepository): Response
    {
        return $this->render('chien/index.html.twig', [
            'chiens' => $chienRepository->findAll(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'un nouveau chien et traite sa soumission.
     *
     * - En GET  : instancie un objet Chien vide, crée le formulaire associé et affiche
     *             la vue "chien/new.html.twig".
     * - En POST : valide les données soumises. Si le formulaire est valide, persiste
     *             le nouvel objet en base de données puis redirige vers la liste des chiens.
     *             En cas d'erreur de validation, réaffiche le formulaire avec les messages d'erreur.
     *
     * Route : GET|POST /admin/chiens/ajout
     *
     * @param Request                $request       L'objet requête HTTP (GET ou POST).
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine pour
     *                                              persister et enregistrer en base.
     * @return Response Formulaire d'ajout ou redirection après succès.
     */
    #[Route('/chiens/ajout', name: 'adminAjoutChien', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $chien = new Chien();
        $form = $this->createForm(ChienType::class, $chien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($chien);
            $entityManager->flush();

            return $this->redirectToRoute('adminListeChiens', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chien/new.html.twig', [
            'chien' => $chien,
            'form'  => $form,
        ]);
    }

    /**
     * Affiche le détail d'un chien identifié par son ID.
     *
     * Symfony résout automatiquement l'entité Chien à partir du paramètre {id}
     * grâce au ParamConverter (EntityValueResolver). Si l'ID n'existe pas en base,
     * une exception 404 est levée automatiquement.
     *
     * Route : GET /admin/chiens/{id}
     *
     * @param Chien $chien L'entité Chien automatiquement résolue depuis l'URL.
     * @return Response La page HTML affichant les informations du chien.
     */
    #[Route('/chiens/{id}', name: 'adminAfficherUnChien', methods: ['GET'])]
    public function show(Chien $chien): Response
    {
        return $this->render('chien/show.html.twig', [
            'chien' => $chien,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'un chien existant et traite sa soumission.
     *
     * - En GET  : pré-remplit le formulaire avec les données actuelles du chien
     *             et affiche la vue "chien/edit.html.twig".
     * - En POST : valide et applique les modifications. Si valide, appelle flush()
     *             pour enregistrer les changements en base (persist() inutile car l'entité
     *             est déjà managée par Doctrine), puis redirige vers la liste.
     *
     * Route : GET|POST /admin/chiens/modification/{id}
     *
     * @param Request                $request       La requête HTTP.
     * @param Chien                  $chien         L'entité Chien à modifier, résolue depuis l'URL.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire pré-rempli ou redirection après succès.
     */
    #[Route('/chiens/modification/{id}', name: 'adminModifierUnChien', methods: ['GET', 'POST'])]
    public function edit(Request $request, Chien $chien, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChienType::class, $chien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('adminListeChiens', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chien/edit.html.twig', [
            'chien' => $chien,
            'form'  => $form,
        ]);
    }

    /**
     * Supprime un chien de la base de données après vérification du token CSRF.
     *
     * La suppression n'est effectuée que si le token CSRF transmis dans le corps
     * de la requête POST est valide (protection contre les attaques CSRF).
     * Le token attendu est nommé "delete{id}" (ex : "delete42").
     * Après suppression (ou si le token est invalide), redirige vers la liste des chiens.
     *
     * Route : POST /admin/chiens/suppression/{id}
     *
     * @param Request                $request       La requête HTTP POST contenant le token CSRF.
     * @param Chien                  $chien         L'entité Chien à supprimer, résolue depuis l'URL.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Redirection vers la liste des chiens.
     */
    #[Route('/chiens/suppression/{id}', name: 'adminSupprimerUnChien', methods: ['POST'])]
    public function delete(Request $request, Chien $chien, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $chien->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($chien);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adminListeChiens', [], Response::HTTP_SEE_OTHER);
    }

    // ==========================================================================
    //  SECTION UTILISATEURS
    // ==========================================================================

    /**
     * Liste tous les utilisateurs enregistrés dans la base de données.
     *
     * Récupère l'intégralité des entités Utilisateur via le repository et les transmet
     * à la vue Twig "utilisateur/index.html.twig".
     *
     * Route : GET /admin/utilisateurs
     *
     * @param UtilisateurRepository $utilisateurRepository Repository injecté pour accéder aux Utilisateurs.
     * @return Response La page HTML listant tous les utilisateurs.
     */
    #[Route('/utilisateurs', name: 'adminListeUtilisateurs', methods: ['GET'])]
    public function indexUtilitaire(UtilisateurRepository $utilisateurRepository): Response
    {
        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'un nouvel utilisateur et traite sa soumission.
     *
     * - En GET  : affiche un formulaire vide basé sur UtilisateurType.
     * - En POST : persiste le nouvel utilisateur en base si le formulaire est valide,
     *             puis redirige vers la liste des utilisateurs.
     *
     * Route : GET|POST /admin/utilisateurs/ajout
     *
     * @param Request                $request       La requête HTTP.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire d'ajout ou redirection après succès.
     */
    #[Route('/utilisateurs/ajout', name: 'adminAjoutUtilisateur', methods: ['GET', 'POST'])]
    public function newUtilitaire(Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('adminListeUtilisateurs', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form'        => $form,
        ]);
    }

    /**
     * Affiche le détail d'un utilisateur identifié par son ID.
     *
     * Route : GET /admin/utilisateurs/{id}
     *
     * @param Utilisateur $utilisateur L'entité Utilisateur résolue automatiquement depuis l'URL.
     * @return Response La page HTML affichant les informations de l'utilisateur.
     */
    #[Route('/utilisateurs/{id}', name: 'adminAfficheUnUtilisateur', methods: ['GET'])]
    public function showUtilitaire(Utilisateur $utilisateur): Response
    {
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'un utilisateur et traite sa soumission.
     *
     * Route : GET|POST /admin/utilisateurs/modification/{id}
     *
     * @param Request                $request       La requête HTTP.
     * @param Utilisateur            $utilisateur   L'entité Utilisateur à modifier.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire pré-rempli ou redirection après succès.
     */
    #[Route('/utilisateurs/modification/{id}', name: 'adminModifierUnUtilisateur', methods: ['GET', 'POST'])]
    public function editUtilitaire(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('adminListeUtilisateurs', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form'        => $form,
        ]);
    }

    /**
     * Supprime un utilisateur après vérification du token CSRF.
     *
     * Route : POST /admin/utilisateurs/suppression/{id}
     *
     * @param Request                $request       La requête HTTP POST.
     * @param Utilisateur            $utilisateur   L'entité Utilisateur à supprimer.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Redirection vers la liste des utilisateurs.
     */
    #[Route('/utilisateurs/suppression/{id}', name: 'adminSupprimerUnUtilisateur', methods: ['POST'])]
    public function deleteUtilitaire(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $utilisateur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adminListeUtilisateurs', [], Response::HTTP_SEE_OTHER);
    }

    // ==========================================================================
    //  SECTION SÉANCES
    // ==========================================================================

    /**
     * Liste toutes les séances enregistrées en base de données.
     *
     * Route : GET /admin/seances
     *
     * @param SeanceRepository $seanceRepository Repository injecté pour accéder aux Séances.
     * @return Response La page HTML listant toutes les séances.
     */
    #[Route('/seances', name: 'adminListeSeances', methods: ['GET'])]
    public function indexSeance(SeanceRepository $seanceRepository): Response
    {
        return $this->render('seance/index.html.twig', [
            'seances' => $seanceRepository->findAll(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'une nouvelle séance et traite sa soumission.
     *
     * Route : GET|POST /admin/seances/ajout
     *
     * @param Request                $request       La requête HTTP.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire d'ajout ou redirection après succès.
     */
    #[Route('/seances/ajout', name: 'adminAjoutSeance', methods: ['GET', 'POST'])]
    public function newSeance(Request $request, EntityManagerInterface $entityManager): Response
    {
        $seance = new Seance();
        $form = $this->createForm(SeanceType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($seance);
            $entityManager->flush();

            return $this->redirectToRoute('adminListeSeances', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('seance/new.html.twig', [
            'seance' => $seance,
            'form'   => $form,
        ]);
    }

    /**
     * Affiche le détail d'une séance identifiée par son ID.
     *
     * Route : GET /admin/seances/{id}
     *
     * @param Seance $seance L'entité Séance résolue automatiquement depuis l'URL.
     * @return Response La page HTML affichant les informations de la séance.
     */
    #[Route('/seances/{id}', name: 'adminAfficheUneSeance', methods: ['GET'])]
    public function showSeance(Seance $seance): Response
    {
        return $this->render('seance/show.html.twig', [
            'seance' => $seance,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'une séance et traite sa soumission.
     *
     * Route : GET|POST /admin/seances/modification/{id}
     *
     * @param Request                $request       La requête HTTP.
     * @param Seance                 $seance        L'entité Séance à modifier.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire pré-rempli ou redirection après succès.
     */
    #[Route('/seances/modification/{id}', name: 'adminModifierUneSeance', methods: ['GET', 'POST'])]
    public function editSeance(Request $request, Seance $seance, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeanceType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('adminListeSeances', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('seance/edit.html.twig', [
            'seance' => $seance,
            'form'   => $form,
        ]);
    }

    /**
     * Supprime une séance après vérification du token CSRF.
     *
     * Route : POST /admin/seances/suppression/{id}
     *
     * @param Request                $request       La requête HTTP POST.
     * @param Seance                 $seance        L'entité Séance à supprimer.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Redirection vers la liste des séances.
     */
    #[Route('/seances/suppression/{id}', name: 'adminSupprimerUneSeance', methods: ['POST'])]
    public function deleteSeance(Request $request, Seance $seance, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $seance->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($seance);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adminListeSeances', [], Response::HTTP_SEE_OTHER);
    }

    // ==========================================================================
    //  SECTION RACES
    // ==========================================================================

    /**
     * Liste toutes les races de chiens référencées en base de données.
     *
     * Route : GET /admin/races
     *
     * @param RaceRepository $raceRepository Repository injecté pour accéder aux Races.
     * @return Response La page HTML listant toutes les races.
     */
    #[Route('/races', name: 'adminListeRaces', methods: ['GET'])]
    public function indexRace(RaceRepository $raceRepository): Response
    {
        return $this->render('race/index.html.twig', [
            'races' => $raceRepository->findAll(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'une nouvelle race et traite sa soumission.
     *
     * Note : contrairement aux autres méthodes new*, la vue reçoit ici
     * "$form->createView()" au lieu de "$form" directement. Les deux approches
     * fonctionnent en Twig, mais il est recommandé d'uniformiser en passant
     * simplement "$form" (Symfony gère createView() automatiquement depuis la v5).
     *
     * Route : GET|POST /admin/races/ajout
     *
     * @param Request                $request       La requête HTTP.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire d'ajout ou redirection après succès.
     */
    #[Route('/races/ajout', name: 'adminAjoutRace', methods: ['GET', 'POST'])]
    public function newRace(Request $request, EntityManagerInterface $entityManager): Response
    {
        $race = new Race();
        $form = $this->createForm(RaceType::class, $race);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($race);
            $entityManager->flush();

            return $this->redirectToRoute('adminListeRaces', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('race/new.html.twig', [
            'race' => $race,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche le détail d'une race identifiée par son ID.
     *
     * Route : GET /admin/races/{id}
     *
     * @param Race $race L'entité Race résolue automatiquement depuis l'URL.
     * @return Response La page HTML affichant les informations de la race.
     */
    #[Route('/races/{id}', name: 'adminAfficheUneRace', methods: ['GET'])]
    public function showRace(Race $race): Response
    {
        return $this->render('race/show.html.twig', [
            'race' => $race,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'une race et traite sa soumission.
     *
     * Route : GET|POST /admin/races/modification/{id}
     *
     * @param Request                $request       La requête HTTP.
     * @param Race                   $race          L'entité Race à modifier.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire pré-rempli ou redirection après succès.
     */
    #[Route('/races/modification/{id}', name: 'adminModifierUneRace', methods: ['GET', 'POST'])]
    public function editRace(Request $request, Race $race, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RaceType::class, $race);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('adminListeRaces', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('race/edit.html.twig', [
            'race' => $race,
            'form' => $form,
        ]);
    }

    /**
     * Supprime une race après vérification du token CSRF.
     *
     * Route : POST /admin/races/suppression/{id}
     *
     * @param Request                $request       La requête HTTP POST.
     * @param Race                   $race          L'entité Race à supprimer.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Redirection vers la liste des races.
     */
    #[Route('/races/suppression/{id}', name: 'adminSupprimerUneRace', methods: ['POST'])]
    public function deleteRace(Request $request, Race $race, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $race->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($race);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adminListeRaces', [], Response::HTTP_SEE_OTHER);
    }

    // ==========================================================================
    //  SECTION PROPRIÉTAIRES
    // ==========================================================================

    /**
     * Liste tous les propriétaires de chiens enregistrés en base de données.
     *
     * Route : GET /admin/proprietaires
     *
     * @param ProprietaireRepository $proprietaireRepository Repository injecté pour accéder aux Propriétaires.
     * @return Response La page HTML listant tous les propriétaires.
     */
    #[Route('/proprietaires', name: 'adminListeProprietaires', methods: ['GET'])]
    public function indexProprietaire(ProprietaireRepository $proprietaireRepository): Response
    {
        return $this->render('proprietaire/index.html.twig', [
            'proprietaires' => $proprietaireRepository->findAll(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'un nouveau propriétaire et traite sa soumission.
     *
     * Route : GET|POST /admin/proprietaires/ajout
     *
     * @param Request                $request       La requête HTTP.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire d'ajout ou redirection après succès.
     */
    #[Route('/proprietaires/ajout', name: 'adminAjoutProprietaire', methods: ['GET', 'POST'])]
    public function newProprietaire(Request $request, EntityManagerInterface $entityManager): Response
    {
        $proprietaire = new Proprietaire();
        $form = $this->createForm(ProprietaireType::class, $proprietaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($proprietaire);
            $entityManager->flush();

            return $this->redirectToRoute('adminListeProprietaires', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('proprietaire/new.html.twig', [
            'proprietaire' => $proprietaire,
            'form'         => $form,
        ]);
    }

    /**
     * Affiche le détail d'un propriétaire identifié par son ID.
     *
     * Route : GET /admin/proprietaires/{id}
     *
     * @param Proprietaire $proprietaire L'entité Propriétaire résolue automatiquement depuis l'URL.
     * @return Response La page HTML affichant les informations du propriétaire.
     */
    #[Route('/proprietaires/{id}', name: 'adminAfficheUnProprietaire', methods: ['GET'])]
    public function showProprietaire(Proprietaire $proprietaire): Response
    {
        return $this->render('proprietaire/show.html.twig', [
            'proprietaire' => $proprietaire,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'un propriétaire et traite sa soumission.
     *
     * Route : GET|POST /admin/proprietaires/modification/{id}
     *
     * @param Request                $request       La requête HTTP.
     * @param Proprietaire           $proprietaire  L'entité Propriétaire à modifier.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire pré-rempli ou redirection après succès.
     */
    #[Route('/proprietaires/modification/{id}', name: 'adminModifierUnProprietaire', methods: ['GET', 'POST'])]
    public function editProprietaire(Request $request, Proprietaire $proprietaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProprietaireType::class, $proprietaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('adminListeProprietaires', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('proprietaire/edit.html.twig', [
            'proprietaire' => $proprietaire,
            'form'         => $form,
        ]);
    }

    /**
     * Supprime un propriétaire après vérification du token CSRF.
     *
     * Route : POST /admin/proprietaires/suppression/{id}
     *
     * @param Request                $request       La requête HTTP POST.
     * @param Proprietaire           $proprietaire  L'entité Propriétaire à supprimer.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Redirection vers la liste des propriétaires.
     */
    #[Route('/proprietaires/suppression/{id}', name: 'adminSupprimerUnProprietaire', methods: ['POST'])]
    public function deleteProprietaire(Request $request, Proprietaire $proprietaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $proprietaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($proprietaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adminListeProprietaires', [], Response::HTTP_SEE_OTHER);
    }

    // ==========================================================================
    //  SECTION NIVEAUX D'APPRENTISSAGE
    // ==========================================================================

    /**
     * Liste tous les niveaux d'apprentissage disponibles en base de données.
     *
     * Les niveaux d'apprentissage représentent les différents degrés de progression
     * proposés par l'école (débutant, intermédiaire, avancé, etc.).
     *
     * Route : GET /admin/niveauApprentissage
     *
     * @param NiveauApprentissageRepository $niveauApprentissageRepository Repository injecté.
     * @return Response La page HTML listant tous les niveaux d'apprentissage.
     */
    #[Route('/niveauApprentissage', name: 'adminListeNiveauApprentissage', methods: ['GET'])]
    public function indexNiveauApprentissage(NiveauApprentissageRepository $niveauApprentissageRepository): Response
    {
        return $this->render('niveau_apprentissage/index.html.twig', [
            'niveau_apprentissages' => $niveauApprentissageRepository->findAll(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'un nouveau niveau d'apprentissage et traite sa soumission.
     *
     * Route : GET|POST /admin/niveauApprentissage/ajout
     *
     * @param Request                $request       La requête HTTP.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire d'ajout ou redirection après succès.
     */
    #[Route('/niveauApprentissage/ajout', name: 'adminAjoutNiveauApprentissage', methods: ['GET', 'POST'])]
    public function newNiveauApprentissage(Request $request, EntityManagerInterface $entityManager): Response
    {
        $niveauApprentissage = new NiveauApprentissage();
        $form = $this->createForm(NiveauApprentissageType::class, $niveauApprentissage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($niveauApprentissage);
            $entityManager->flush();

            return $this->redirectToRoute('adminListeNiveauApprentissage', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('niveau_apprentissage/new.html.twig', [
            'niveau_apprentissage' => $niveauApprentissage,
            'form'                 => $form,
        ]);
    }

    /**
     * Affiche le détail d'un niveau d'apprentissage identifié par son ID.
     *
     * Route : GET /admin/niveauApprentissage/{id}
     *
     * @param NiveauApprentissage $niveauApprentissage L'entité résolue automatiquement depuis l'URL.
     * @return Response La page HTML affichant les informations du niveau.
     */
    #[Route('/niveauApprentissage/{id}', name: 'adminAfficheUnNiveauApprentissage', methods: ['GET'])]
    public function showNiveauApprentissage(NiveauApprentissage $niveauApprentissage): Response
    {
        return $this->render('niveau_apprentissage/show.html.twig', [
            'niveau_apprentissage' => $niveauApprentissage,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'un niveau d'apprentissage et traite sa soumission.
     *
     * Route : GET|POST /admin/niveauApprentissage/modification/{id}
     *
     * @param Request                $request             La requête HTTP.
     * @param NiveauApprentissage    $niveauApprentissage L'entité à modifier.
     * @param EntityManagerInterface $entityManager       Le gestionnaire Doctrine.
     * @return Response Formulaire pré-rempli ou redirection après succès.
     */
    #[Route('/niveauApprentissage/modification/{id}', name: 'adminModifierUnNiveauApprentissage', methods: ['GET', 'POST'])]
    public function editNiveauApprentissage(Request $request, NiveauApprentissage $niveauApprentissage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NiveauApprentissageType::class, $niveauApprentissage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('adminListeNiveauApprentissage', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('niveau_apprentissage/edit.html.twig', [
            'niveau_apprentissage' => $niveauApprentissage,
            'form'                 => $form,
        ]);
    }

    /**
     * Supprime un niveau d'apprentissage après vérification du token CSRF.
     *
     * Route : POST /admin/niveauApprentissage/suppression/{id}
     *
     * @param Request                $request             La requête HTTP POST.
     * @param NiveauApprentissage    $niveauApprentissage L'entité à supprimer.
     * @param EntityManagerInterface $entityManager       Le gestionnaire Doctrine.
     * @return Response Redirection vers la liste des niveaux d'apprentissage.
     */
    #[Route('/niveauApprentissage/suppression/{id}', name: 'adminSupprimerUnNiveauApprentissage', methods: ['POST'])]
    public function deleteNiveauApprentissage(Request $request, NiveauApprentissage $niveauApprentissage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $niveauApprentissage->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($niveauApprentissage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adminListeNiveauApprentissage', [], Response::HTTP_SEE_OTHER);
    }

    // ==========================================================================
    //  SECTION INSCRIPTIONS
    // ==========================================================================

    /**
     * Liste toutes les inscriptions enregistrées en base de données.
     *
     * Une inscription représente le lien entre un chien (et donc son propriétaire)
     * et un cours donné par l'école.
     *
     * Route : GET /admin/inscriptions
     *
     * @param InscriptionRepository $inscriptionRepository Repository injecté pour accéder aux Inscriptions.
     * @return Response La page HTML listant toutes les inscriptions.
     */
    #[Route('/inscriptions', name: 'adminListeInscription', methods: ['GET'])]
    public function indexInscription(InscriptionRepository $inscriptionRepository): Response
    {
        return $this->render('inscription/index.html.twig', [
            'inscriptions' => $inscriptionRepository->findAll(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'une nouvelle inscription et traite sa soumission.
     *
     * Route : GET|POST /admin/inscriptions/ajout
     *
     * @param Request                $request       La requête HTTP.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire d'ajout ou redirection après succès.
     */
    #[Route('/inscriptions/ajout', name: 'adminAjoutInscription', methods: ['GET', 'POST'])]
    public function newInscription(Request $request, EntityManagerInterface $entityManager): Response
    {
        $inscription = new Inscription();
        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($inscription);
            $entityManager->flush();

            return $this->redirectToRoute('adminListeInscription', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('inscription/new.html.twig', [
            'inscription' => $inscription,
            'form'        => $form,
        ]);
    }

    /**
     * Affiche le détail d'une inscription identifiée par son ID.
     *
     * Route : GET /admin/inscriptions/{id}
     *
     * @param Inscription $inscription L'entité Inscription résolue automatiquement depuis l'URL.
     * @return Response La page HTML affichant les informations de l'inscription.
     */
    #[Route('/inscriptions/{id}', name: 'adminAfficheUneInscription', methods: ['GET'])]
    public function showInscription(Inscription $inscription): Response
    {
        return $this->render('inscription/show.html.twig', [
            'inscription' => $inscription,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'une inscription et traite sa soumission.
     *
     * Route : GET|POST /admin/inscriptions/modification/{id}
     *
     * @param Request                $request       La requête HTTP.
     * @param Inscription            $inscription   L'entité Inscription à modifier.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire pré-rempli ou redirection après succès.
     */
    #[Route('/inscriptions/modification/{id}', name: 'adminModifierUneInscription', methods: ['GET', 'POST'])]
    public function editInscription(Request $request, Inscription $inscription, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('adminListeInscription', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('inscription/edit.html.twig', [
            'inscription' => $inscription,
            'form'        => $form,
        ]);
    }

    /**
     * Supprime une inscription après vérification du token CSRF.
     *
     * Route : POST /admin/inscriptions/suppression/{id}
     *
     * @param Request                $request       La requête HTTP POST.
     * @param Inscription            $inscription   L'entité Inscription à supprimer.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Redirection vers la liste des inscriptions.
     */
    #[Route('/inscriptions/suppression/{id}', name: 'adminSupprimerUneInscription', methods: ['POST'])]
    public function deleteInscription(Request $request, Inscription $inscription, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $inscription->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($inscription);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adminListeInscription', [], Response::HTTP_SEE_OTHER);
    }

    // ==========================================================================
    //  SECTION COURS
    // ==========================================================================

    /**
     * Liste tous les cours proposés par l'école, enregistrés en base de données.
     *
     * Route : GET /admin/cours
     *
     * @param CoursRepository $coursRepository Repository injecté pour accéder aux Cours.
     * @return Response La page HTML listant tous les cours.
     */
    #[Route('/cours', name: 'adminListeCours', methods: ['GET'])]
    public function indexCours(CoursRepository $coursRepository): Response
    {
        return $this->render('cours/index.html.twig', [
            'cours' => $coursRepository->findAll(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'un nouveau cours et traite sa soumission.
     *
     * Route : GET|POST /admin/cours/ajout
     *
     * @param Request                $request       La requête HTTP.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire d'ajout ou redirection après succès.
     */
    #[Route('/cours/ajout', name: 'adminAjoutCours', methods: ['GET', 'POST'])]
    public function newCours(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cour);
            $entityManager->flush();

            return $this->redirectToRoute('adminListeCours', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/new.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    /**
     * Affiche le détail d'un cours identifié par son ID.
     *
     * Route : GET /admin/cours/{id}
     *
     * @param Cours $cour L'entité Cours résolue automatiquement depuis l'URL.
     * @return Response La page HTML affichant les informations du cours.
     */
    #[Route('/cours/{id}', name: 'adminAfficheUnCours', methods: ['GET'])]
    public function showCours(Cours $cour): Response
    {
        return $this->render('cours/show.html.twig', [
            'cour' => $cour,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'un cours et traite sa soumission.
     *
     * Route : GET|POST /admin/cours/modification/{id}
     *
     * @param Request                $request       La requête HTTP.
     * @param Cours                  $cour          L'entité Cours à modifier.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire pré-rempli ou redirection après succès.
     */
    #[Route('/cours/modification/{id}', name: 'adminModifierUnCours', methods: ['GET', 'POST'])]
    public function editCours(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('adminListeCours', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/edit.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un cours après vérification du token CSRF.
     *
     * Route : POST /admin/cours/suppression/{id}
     *
     * @param Request                $request       La requête HTTP POST.
     * @param Cours                  $cour          L'entité Cours à supprimer.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Redirection vers la liste des cours.
     */
    #[Route('/cours/suppression/{id}', name: 'adminSupprimerUnCours', methods: ['POST'])]
    public function deleteCours(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $cour->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($cour);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adminListeCours', [], Response::HTTP_SEE_OTHER);
    }
}