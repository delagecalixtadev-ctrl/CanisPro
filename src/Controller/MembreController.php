<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProprietaireRepository;
use App\Repository\ChienRepository;
use App\Repository\SeanceRepository;
use App\Entity\Inscription;
use App\Entity\Chien;
use App\Form\ChienType;
use App\Entity\Proprietaire;
use App\Form\ProprietaireType;
use App\Entity\Seance;
use App\Entity\Cours;
use App\Form\InscriptionType;

/**
 * MembreController - Contrôleur dédié à l'espace membre de l'application CanisPro.
 *
 * Ce contrôleur gère toutes les fonctionnalités accessibles aux membres connectés
 * (propriétaires de chiens) de l'école de dressage canin. Il constitue l'interface
 * entre l'utilisateur authentifié et ses données personnelles.
 *
 * Fonctionnalités couvertes :
 * - Accueil membre et espace personnel (consultation et modification du profil propriétaire)
 * - Gestion des chiens (consultation, ajout, modification)
 * - Gestion des inscriptions aux séances (réservation, consultation, suppression)
 * - Consultation des prochaines séances disponibles
 *
 * Toutes les routes sont préfixées par "/membre" grâce à l'attribut #[Route('/membre')]
 * posé au niveau de la classe.
 *
 * Ce contrôleur suppose que l'utilisateur est authentifié. La méthode getUser()
 * est utilisée systématiquement pour récupérer l'utilisateur connecté, puis
 * son entité Proprietaire associée via getProprietaire().
 *
 * @package App\Controller
 */
#[Route('/membre')]
final class MembreController extends AbstractController
{
    // ==========================================================================
    //  SECTION ACCUEIL
    // ==========================================================================

    /**
     * Page d'accueil de l'espace membre.
     *
     * Affiche la page d'accueil générale de l'application.
     * Cette route présente une anomalie : le préfixe de classe est déjà "/membre"
     * et la route définie est "/membre/membre", ce qui génère un doublon dans l'URL.
     * Il faudrait corriger en "/accueil" ou "/" pour éviter "/membre/membre".
     *
     * Route : GET /membre/membre
     *
     * @return Response La page d'accueil "index.html.twig".
     */
    #[Route('/membre', name: 'accueil')]
    public function index(): Response
    {
        return $this->render('index.html.twig', [
            'controller_name' => 'MembreController',
        ]);
    }

    // ==========================================================================
    //  SECTION ESPACE PERSONNEL (PROPRIÉTAIRE)
    // ==========================================================================

    /**
     * Affiche l'espace personnel du membre connecté.
     *
     * Récupère l'utilisateur actuellement authentifié via getUser(), puis accède
     * à son entité Proprietaire associée. Cette entité contient toutes les informations
     * personnelles du membre (nom, prénom, coordonnées, etc.) ainsi que ses chiens
     * et inscriptions.
     *
     * Cette page sert de tableau de bord principal pour le membre connecté.
     *
     * Route : GET /membre/espace-personnel
     *
     * @return Response La vue "membre/espace_personal.html.twig" avec les données du propriétaire.
     */
    #[Route('/espace-personnel', name: 'espace_personnel')]
    public function espaceProprietaire(): Response
    {
        $user = $this->getUser();
        $proprietaire = $user->getProprietaire();

        return $this->render('membre/espace_personnel.html.twig', [
            'proprietaire' => $proprietaire,
        ]);
    }

    /**
     * Affiche l'espace de gestion des chiens du membre connecté.
     *
     * Récupère le propriétaire lié à l'utilisateur connecté, puis charge
     * la collection de chiens qui lui appartiennent via la relation Doctrine
     * getChiens(). Ces chiens sont ensuite transmis à la vue pour affichage
     * sous forme de liste (nom, race, niveau, etc.).
     *
     * Route : GET /membre/espace-chien
     *
     * @return Response La vue "membre/espace_chien.html.twig" avec le propriétaire et ses chiens.
     */
    #[Route('/espace-chien', name: 'espace_chien')]
    public function espaceChien(): Response
    {
        $user = $this->getUser();
        $proprietaire = $user->getProprietaire();
        $chiens = $proprietaire->getChiens();

        return $this->render('membre/espace_chien.html.twig', [
            'proprietaire' => $proprietaire,
            'chiens'       => $chiens,
        ]);
    }

    // ==========================================================================
    //  SECTION INSCRIPTIONS AUX SÉANCES
    // ==========================================================================

    /**
     * Affiche le formulaire de réservation d'une séance et traite sa soumission.
     *
     * Cette méthode permet à un membre de s'inscrire à une séance spécifique
     * en choisissant l'un de ses chiens.
     *
     * Fonctionnement détaillé :
     * - La séance cible est résolue automatiquement depuis l'URL via le ParamConverter ({seance}).
     * - Les chiens disponibles sont filtrés par propriétaire connecté (findBy).
     * - En GET  : affiche le formulaire InscriptionType pré-rempli avec la séance.
     * - En POST : récupère l'ID du chien sélectionné depuis la requête brute (request->request->get),
     *             associe le chien et fixe le nombre de chiens inscrits à 1,
     *             puis persiste l'inscription en base et redirige vers l'espace personnel.
     *
     * Note : la récupération du chien via $request->request->get('chien') contourne
     * le système de formulaire Symfony. Il serait plus robuste d'intégrer le champ
     * directement dans InscriptionType avec une EntityType filtrée par propriétaire.
     *
     * Route : GET|POST /membre/inscriptions/ajout/{seance}
     *
     * @param Request                $request           La requête HTTP.
     * @param ChienRepository        $chienRepository   Repository pour récupérer les chiens du propriétaire.
     * @param EntityManagerInterface $entityManager     Le gestionnaire Doctrine pour persister l'inscription.
     * @param Seance                 $seance            La séance cible, résolue automatiquement depuis l'URL.
     * @return Response Formulaire de réservation ou redirection vers l'espace personnel après succès.
     */
    #[Route('/inscriptions/ajout/{seance}', name: 'app_reservation', methods: ['GET', 'POST'])]
    public function newInscription(
        Request $request,
        ChienRepository $chienRepository,
        EntityManagerInterface $entityManager,
        Seance $seance
    ): Response {
        $user        = $this->getUser();
        $proprietaire = $user->getProprietaire();
        $chiens      = $chienRepository->findBy(['proprietaire' => $proprietaire]);

        $inscription = new Inscription();
        $inscription->setSeance($seance);

        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $idChien = $request->request->get('chien');
            $chien   = $chienRepository->find($idChien);
            $inscription->setChien($chien);
            $inscription->setNbChienInscrit(1);

            $entityManager->persist($inscription);
            $entityManager->flush();

            return $this->redirectToRoute('espace_personnel', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('inscription/new.html.twig', [
            'inscription' => $inscription,
            'form'        => $form,
            'chiens'      => $chiens,
        ]);
    }

    // ==========================================================================
    //  SECTION GESTION DES CHIENS
    // ==========================================================================

    /**
     * Affiche le formulaire d'ajout d'un nouveau chien pour le membre connecté.
     *
     * Crée un nouvel objet Chien et l'associe automatiquement au propriétaire
     * de l'utilisateur connecté. Le propriétaire est assigné deux fois :
     * une première fois avant la gestion du formulaire (pour l'affichage),
     * et une seconde fois après validation (pour garantir la liaison même si
     * le formulaire écrase la valeur). Cette redondance est intentionnelle
     * mais pourrait être simplifiée.
     *
     * Note : le paramètre {id} est présent dans la route mais n'est pas
     * utilisé dans la signature de la méthode — il s'agit probablement
     * d'un résidu de refactoring à nettoyer.
     *
     * Route : GET|POST /membre/espace-chien/ajoutChien/{id}
     *
     * @param Request                $request       La requête HTTP.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire d'ajout de chien ou redirection vers l'espace chien.
     */
    #[Route('/espace-chien/ajoutChien/{id}', name: 'membreAjoutChien')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user         = $this->getUser();
        $proprietaire = $user->getProprietaire();
        $chien        = new Chien();
        $form         = $this->createForm(ChienType::class, $chien);
        $chien->setProprietaire($proprietaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chien->setProprietaire($proprietaire);
            $entityManager->persist($chien);
            $entityManager->flush();

            return $this->redirectToRoute('espace_chien', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chien/new.html.twig', [
            'chien'        => $chien,
            'form'         => $form,
            'proprietaire' => $proprietaire,
        ]);
    }

    // ==========================================================================
    //  SECTION SÉANCES
    // ==========================================================================

    /**
     * Affiche les prochaines séances disponibles pour le membre connecté.
     *
     * Récupère toutes les séances disponibles en base de données.
     * Actuellement, la vue reçoit uniquement le cours de la première séance
     * ($seances[0]->getCours()) à titre d'exemple. Cette implémentation est
     * incomplète et devrait être remplacée par un filtre sur les séances à venir
     * (date >= aujourd'hui) triées chronologiquement, idéalement via une méthode
     * dédiée dans SeanceRepository (ex: findUpcoming()).
     *
     * Route : GET /membre/mes-prochaines-seances
     *
     * @param SeanceRepository $seanceRepository Repository injecté pour récupérer les séances.
     * @return Response La vue "membre/mes_seances.html.twig" avec le propriétaire et les séances.
     */
    #[Route('/mes-prochaines-seances', name: 'membre_mes_prochaines_seances')]
    public function mesSeances(SeanceRepository $seanceRepository): Response
    {
        $user         = $this->getUser();
        $proprietaire = $user->getProprietaire();
        $seances      = $seanceRepository->findAll();

        return $this->render('membre/mes_seances.html.twig', [
            'proprietaire' => $proprietaire,
            'seances'      => $seances[0]->getCours(),
        ]);
    }

    // ==========================================================================
    //  SECTION MODIFICATION PROFIL
    // ==========================================================================

    /**
     * Affiche le formulaire de modification du profil propriétaire et traite sa soumission.
     *
     * Récupère le propriétaire depuis l'utilisateur connecté (et non depuis
     * le paramètre {id} de l'URL ni l'injection automatique Doctrine), ce qui
     * signifie que le paramètre d'URL est ignoré : un membre ne peut modifier
     * que son propre profil, quelle que soit l'URL saisie. C'est un choix
     * sécurisé mais qui rend le paramètre {id} et l'injection de Chien inutiles
     * dans la signature — ils sont à nettoyer.
     *
     * En POST : persiste les modifications (persist() est ici superflu car
     * l'entité est déjà managée par Doctrine — seul flush() est nécessaire).
     *
     * Route : GET|POST /membre/membre/espace-personnel/modification/{id}
     *
     * @param Request                $request       La requête HTTP.
     * @param Proprietaire           $proprietaire  Injecté par Doctrine mais immédiatement
     *                                              remplacé par le propriétaire de l'utilisateur connecté.
     * @param Chien                  $chien         Injecté mais jamais utilisé — à supprimer.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Formulaire de modification ou redirection vers l'espace personnel.
     */
    #[Route('/membre/espace-personnel/modification/{id}', name: 'membre_proprietaire_modification', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierUnProprietaires(
        Request $request,
        Proprietaire $proprietaire,
        Chien $chien,
        EntityManagerInterface $entityManager
    ): Response {
        $user         = $this->getUser();
        $proprietaire = $user->getProprietaire();

        $form = $this->createForm(ProprietaireType::class, $proprietaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($proprietaire);
            $entityManager->flush();

            return $this->redirectToRoute('espace_personnel', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('membre/modification_proprietaire.html.twig', [
            'proprietaire' => $proprietaire,
            'form'         => $form,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'un chien et traite sa soumission.
     *
     * Contrairement aux autres méthodes de ce contrôleur, celle-ci n'utilise pas
     * le ParamConverter automatique de Symfony mais récupère le chien manuellement
     * via le repository. Cela permet de gérer explicitement le cas "non trouvé"
     * avec une exception 404 personnalisée.
     *
     * Un flag $isModification est transmis à la vue pour adapter l'affichage
     * (titre, bouton de soumission, etc.) selon le contexte création/modification.
     *
     * En cas de succès, un message flash "success" est ajouté avant la redirection
     * pour informer l'utilisateur que la modification a bien été prise en compte.
     *
     * Note : le formulaire est passé à Twig via $form->createView() au lieu de
     * $form directement. Les deux fonctionnent, mais passer $form est recommandé
     * depuis Symfony 5 (createView() est appelé automatiquement par Twig).
     *
     * Route : GET|POST /membre/membre/espace-chien/modification/{id}
     *
     * @param int                    $id         L'identifiant du chien à modifier, extrait de l'URL.
     * @param ChienRepository        $repository Repository pour récupérer le chien par son ID.
     * @param EntityManagerInterface $entity     Le gestionnaire Doctrine (flush uniquement, pas de persist).
     * @param Request                $request    La requête HTTP.
     * @return Response Formulaire pré-rempli ou redirection vers l'espace chien après succès.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException Si le chien n'existe pas en base.
     */
    #[Route('/membre/espace-chien/modification/{id}', name: 'membre_chien_modification', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierUnChien(
        int $id,
        ChienRepository $repository,
        EntityManagerInterface $entity,
        Request $request
    ): Response {
        $chien = $repository->find($id);

        if (!$chien) {
            throw $this->createNotFoundException('Chien non trouvé');
        }

        $isModification = true;

        $form = $this->createForm(ChienType::class, $chien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity->flush();
            $this->addFlash('success', 'Chien modifié avec succès');

            return $this->redirectToRoute('espace_chien', [
                'id' => $chien->getProprietaire()->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('membre/modification_chien.html.twig', [
            'form'           => $form->createView(),
            'chien'          => $chien,
            'isModification' => $isModification,
        ]);
    }

    // ==========================================================================
    //  SECTION SÉANCES D'UN CHIEN
    // ==========================================================================

    /**
     * Affiche toutes les inscriptions aux séances d'un chien spécifique.
     *
     * Récupère le chien par son ID et charge sa collection d'inscriptions
     * via la relation Doctrine getInscriptions(). Chaque inscription contient
     * la référence à la séance et au cours associé, permettant d'afficher
     * l'historique et les prochaines séances pour ce chien.
     *
     * Si le chien n'est pas trouvé en base (ID inexistant), une exception 404
     * est levée avec un message explicite.
     *
     * Route : GET /membre/espace-chien/seances/{id}
     *
     * @param ChienRepository $chienRepo Repository pour récupérer le chien par son ID.
     * @param int             $id        L'identifiant du chien, extrait de l'URL.
     * @return Response La vue "membre/chien_seances.html.twig" avec le chien et ses inscriptions.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException Si le chien est introuvable.
     */
    #[Route('/espace-chien/seances/{id}', name: 'chien_inscrit_seance')]
    public function voirSeancesChien(ChienRepository $chienRepo, int $id): Response
    {
        $chien = $chienRepo->find($id);

        if (!$chien) {
            throw $this->createNotFoundException('Chien introuvable');
        }

        return $this->render('membre/chien_seances.html.twig', [
            'chien'        => $chien,
            'inscriptions' => $chien->getInscriptions(),
        ]);
    }

    // ==========================================================================
    //  SECTION SUPPRESSION D'INSCRIPTION
    // ==========================================================================

    /**
     * Supprime une inscription d'un chien à une séance après vérification du token CSRF.
     *
     * Avant de supprimer, sauvegarde l'ID du chien associé à l'inscription
     * afin de pouvoir rediriger vers la page des séances de ce chien après suppression.
     * Cette précaution est nécessaire car une fois l'entité supprimée et le flush effectué,
     * l'accès à $inscription->getChien() pourrait poser problème selon la configuration
     * de Doctrine (lazy loading).
     *
     * La suppression n'est effectuée que si le token CSRF est valide (nommé "delete{id}").
     * Si le token est invalide, la redirection a lieu sans suppression — comportement
     * silencieux qui pourrait être amélioré avec un message flash d'erreur.
     *
     * Note : la route contient une faute d'orthographe ("supression" au lieu de
     * "suppression") qui devrait être corrigée pour la cohérence avec les autres routes.
     *
     * Route : POST /membre/espace-chien/inscription/supression/{id}
     *
     * @param Request                $request       La requête HTTP POST contenant le token CSRF.
     * @param Inscription            $inscription   L'entité Inscription à supprimer, résolue depuis l'URL.
     * @param EntityManagerInterface $entityManager Le gestionnaire Doctrine.
     * @return Response Redirection vers la page des séances du chien concerné.
     */
    #[Route('/espace-chien/inscription/supression/{id}', name: 'membreSuppressionInscription')]
    public function delete(
        Request $request,
        Inscription $inscription,
        EntityManagerInterface $entityManager
    ): Response {
        $chienId = $inscription->getChien()->getId();

        if ($this->isCsrfTokenValid('delete' . $inscription->getId(), $request->request->get('_token'))) {
            $entityManager->remove($inscription);
            $entityManager->flush();
        }

        return $this->redirectToRoute('chien_inscrit_seance', ['id' => $chienId], Response::HTTP_SEE_OTHER);
    }
}