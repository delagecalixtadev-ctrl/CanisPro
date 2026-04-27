<?php

namespace App\Entity;

use App\Repository\ChienRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Chien - Entité représentant un chien inscrit dans l'école de dressage CanisPro.
 *
 * Un chien est l'entité centrale du domaine métier. Il appartient à un Proprietaire,
 * est catégorisé par une Race et un NiveauApprentissage, et peut être inscrit
 * à plusieurs séances de cours via ses Inscriptions.
 *
 * Relations :
 * - ManyToOne vers Race               : un chien appartient à une race.
 *                                       Nullable — la race peut ne pas être renseignée.
 *                                       Cette entité porte la clé étrangère race_id.
 *
 * - ManyToOne vers NiveauApprentissage : un chien possède un niveau de progression.
 *                                       Nullable — le niveau peut ne pas être assigné.
 *                                       Cette entité porte la clé étrangère niveaux_apprentissage_id.
 *
 * - ManyToOne vers Proprietaire       : un chien appartient à un propriétaire.
 *                                       Nullable — le propriétaire peut ne pas être renseigné.
 *                                       Cette entité porte la clé étrangère proprietaire_id.
 *                                       Note : JoinColumn nullable: true est incohérent avec
 *                                       la logique métier — un chien devrait toujours avoir
 *                                       un propriétaire.
 *
 * - OneToMany vers Inscription        : un chien peut être inscrit à plusieurs séances.
 *                                       C'est le côté inverse (mappedBy: 'chien'),
 *                                       la clé étrangère est portée par Inscription.
 *                                       orphanRemoval: true — supprimer un chien supprime
 *                                       automatiquement toutes ses inscriptions.
 *
 * Note sur $dateNaissance : stocké en VARCHAR(20) au lieu d'un type DateTimeInterface,
 * ce qui limite les possibilités de tri et de calcul d'âge.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: ChienRepository::class)]
class Chien
{
    /**
     * Identifiant unique auto-incrémenté du chien en base de données.
     *
     * Généré automatiquement par Doctrine lors du premier persist().
     * Vaut null tant que l'entité n'a pas été persistée.
     *
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du chien.
     *
     * Limité à 30 caractères. Ne peut pas être null en base de données.
     * Il s'agit du nom usuel du chien (ex : "Rex", "Luna", "Diesel").
     *
     * @var string|null
     */
    #[ORM\Column(length: 30)]
    private ?string $nom = null;

    /**
     * Date de naissance du chien stockée en chaîne de caractères.
     *
     * Limité à 20 caractères (ex : "2020-03-15" ou "15/03/2020").
     * Ne peut pas être null en base de données.
     * Note : ce champ devrait idéalement être migré vers un type
     * DateTimeInterface pour permettre le calcul de l'âge du chien
     * et les tris chronologiques via Doctrine.
     *
     * @var string|null
     */
    #[ORM\Column(length: 20)]
    private ?string $dateNaissance = null;

    /**
     * Race du chien.
     *
     * Relation ManyToOne — plusieurs chiens peuvent être de la même race.
     * Cette entité porte la clé étrangère race_id.
     * Nullable (JoinColumn nullable: true) — la race peut ne pas être renseignée
     * si elle est inconnue ou non référencée dans le système.
     *
     * @var Race|null
     */
    #[ORM\ManyToOne(inversedBy: 'chiens')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Race $race = null;

    /**
     * Niveau d'apprentissage actuel du chien.
     *
     * Relation ManyToOne — plusieurs chiens peuvent partager le même niveau.
     * Cette entité porte la clé étrangère niveaux_apprentissage_id.
     * Nullable — un chien peut ne pas encore avoir de niveau assigné
     * (par exemple lors de sa première inscription).
     *
     * Note : le nom "niveauxApprentissage" est au pluriel alors qu'un chien
     * ne possède qu'un seul niveau (ManyToOne). Le singulier "niveauApprentissage"
     * serait plus cohérent et lisible.
     *
     * @var NiveauApprentissage|null
     */
    #[ORM\ManyToOne(inversedBy: 'chiens')]
    #[ORM\JoinColumn(nullable: true)]
    private ?NiveauApprentissage $niveauxApprentissage = null;

    /**
     * Propriétaire du chien.
     *
     * Relation ManyToOne — un propriétaire peut posséder plusieurs chiens.
     * Cette entité porte la clé étrangère proprietaire_id.
     * Nullable (JoinColumn nullable: true) — techniquement possible mais
     * incohérent avec la logique métier : un chien devrait toujours avoir
     * un propriétaire. Il serait recommandé de passer nullable: false
     * pour garantir cette contrainte en base de données.
     *
     * @var Proprietaire|null
     */
    #[ORM\ManyToOne(inversedBy: 'chiens')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Proprietaire $proprietaire = null;

    /**
     * Collection des inscriptions de ce chien aux séances.
     *
     * Relation OneToMany — un chien peut être inscrit à plusieurs séances.
     * C'est le côté inverse de la relation (mappedBy: 'chien'),
     * la clé étrangère chien_id est portée par l'entité Inscription.
     *
     * orphanRemoval: true — si une inscription est retirée de cette collection
     * ou si le chien est supprimé, toutes ses inscriptions sont automatiquement
     * supprimées en base lors du prochain flush(), sans appel explicite à remove().
     *
     * Initialisée dans le constructeur avec une ArrayCollection vide.
     *
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'chien', orphanRemoval: true)]
    private Collection $inscriptions;

    /**
     * Constructeur — initialise la collection d'inscriptions.
     *
     * Doctrine requiert que les collections soient initialisées avec une
     * ArrayCollection dans le constructeur pour éviter les erreurs lors
     * de l'accès à la collection avant tout chargement depuis la base.
     */
    public function __construct()
    {
        $this->inscriptions = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant unique du chien.
     *
     * @return int|null L'ID auto-incrémenté, ou null si l'entité n'est pas encore persistée.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le nom du chien.
     *
     * @return string|null Le nom du chien (ex : "Rex"), ou null s'il n'a pas encore été défini.
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Définit le nom du chien.
     *
     * @param string $nom Le nom du chien (max 30 caractères).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Retourne la date de naissance du chien sous forme de chaîne.
     *
     * @return string|null La date de naissance (ex : "2020-03-15"), ou null si non définie.
     */
    public function getDateNaissance(): ?string
    {
        return $this->dateNaissance;
    }

    /**
     * Définit la date de naissance du chien.
     *
     * @param string $dateNaissance La date de naissance sous forme de chaîne (ex : "2020-03-15").
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setDateNaissance(string $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    /**
     * Retourne la race du chien.
     *
     * @return Race|null La race associée, ou null si non renseignée.
     */
    public function getRace(): ?Race
    {
        return $this->race;
    }

    /**
     * Définit la race du chien.
     *
     * @param Race|null $race La race à associer, ou null si inconnue.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setRace(?Race $race): static
    {
        $this->race = $race;

        return $this;
    }

    /**
     * Retourne le niveau d'apprentissage actuel du chien.
     *
     * @return NiveauApprentissage|null Le niveau associé, ou null si non assigné.
     */
    public function getNiveauxApprentissage(): ?NiveauApprentissage
    {
        return $this->niveauxApprentissage;
    }

    /**
     * Définit le niveau d'apprentissage du chien.
     *
     * @param NiveauApprentissage|null $niveauxApprentissage Le niveau à associer, ou null.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setNiveauxApprentissage(?NiveauApprentissage $niveauxApprentissage): static
    {
        $this->niveauxApprentissage = $niveauxApprentissage;

        return $this;
    }

    /**
     * Retourne le propriétaire du chien.
     *
     * @return Proprietaire|null Le propriétaire associé, ou null si non renseigné.
     */
    public function getProprietaire(): ?Proprietaire
    {
        return $this->proprietaire;
    }

    /**
     * Définit le propriétaire du chien.
     *
     * Note : bien que le setter accepte null, un chien devrait toujours
     * avoir un propriétaire selon la logique métier de l'application.
     *
     * @param Proprietaire|null $proprietaire Le propriétaire à associer, ou null.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setProprietaire(?Proprietaire $proprietaire): static
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    /**
     * Retourne la collection complète des inscriptions de ce chien.
     *
     * @return Collection<int, Inscription> La collection des inscriptions associées.
     */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    /**
     * Ajoute une inscription à ce chien.
     *
     * Vérifie d'abord que l'inscription n'est pas déjà présente dans la collection
     * via contains() pour éviter les doublons. Si nouvelle, l'ajoute à la
     * collection et synchronise le côté propriétaire de la relation en
     * appelant $inscription->setChien($this).
     *
     * Cette synchronisation bidirectionnelle est indispensable pour que Doctrine
     * persiste correctement la clé étrangère (chien_id) dans la table inscription.
     *
     * @param Inscription $inscription L'inscription à associer à ce chien.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function addInscription(Inscription $inscription): static
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setChien($this);
        }

        return $this;
    }

    /**
     * Retire une inscription de ce chien.
     *
     * Utilise removeElement() pour retirer l'inscription de la collection.
     * Si l'élément était présent et a été retiré (retour true), vérifie
     * que le chien lié côté Inscription est bien ce chien, puis appelle
     * setChien(null) pour dissocier le côté propriétaire.
     *
     * Grâce à orphanRemoval: true, Doctrine supprimera automatiquement
     * l'inscription en base lors du prochain flush(), sans appel explicite
     * à $entityManager->remove().
     *
     * Attention : setChien(null) est appelé pour dissocier la relation,
     * mais la colonne chien_id est NOT NULL dans Inscription (JoinColumn
     * nullable: false). L'orphanRemoval prend le relais en supprimant
     * l'inscription, évitant ainsi une violation de contrainte en base.
     *
     * @param Inscription $inscription L'inscription à retirer de ce chien.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function removeInscription(Inscription $inscription): static
    {
        if ($this->inscriptions->removeElement($inscription)) {
            if ($inscription->getChien() === $this) {
                $inscription->setChien(null);
            }
        }

        return $this;
    }
}