<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cours - Entité représentant un cours de dressage proposé par l'école CanisPro.
 *
 * Un cours est un programme d'entraînement défini par son type, sa description,
 * son prix, sa durée et ses caractéristiques (collectif ou individuel, nombre
 * maximum de chiens). Il est associé à un niveau d'apprentissage et peut
 * être décliné en plusieurs séances planifiées à des dates différentes.
 *
 * Relations :
 * - ManyToOne vers NiveauApprentissage : un cours est rattaché à un niveau
 *                                        de difficulté (ex : "Débutant").
 *                                        La colonne est nullable — un cours peut
 *                                        exister sans niveau assigné.
 *                                        C'est le côté propriétaire (owning side),
 *                                        il porte la clé étrangère.
 *
 * - OneToMany vers Seance             : un cours peut avoir plusieurs séances
 *                                        planifiées à des dates/heures différentes.
 *                                        C'est le côté inverse (mappedBy: 'cours'),
 *                                        la clé étrangère est portée par Seance.
 *                                        Pas d'orphanRemoval — supprimer un cours
 *                                        ne supprime pas automatiquement ses séances.
 *
 * Note sur le nommage : $esCollectif devrait idéalement être $estCollectif
 * (faute de frappe sur "est"). La méthode isEsCollectif() devrait être
 * renommée en isCollectif() pour suivre la convention Symfony/PSR sur les booléens.
 *
 * Note sur $duree : stocké en INT sans unité précisée. Il est recommandé
 * d'ajouter un commentaire ou une convention dans le projet pour indiquer
 * si la durée est en minutes, en heures, etc.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: CoursRepository::class)]
class Cours
{
    /**
     * Identifiant unique auto-incrémenté du cours en base de données.
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
     * Type d'entraînement du cours.
     *
     * Chaîne limitée à 25 caractères décrivant la catégorie du cours
     * (ex : "Obéissance", "Agility", "Pistage", "Rapport").
     * Ne peut pas être null en base de données.
     *
     * @var string|null
     */
    #[ORM\Column(length: 25)]
    private ?string $typeEntrainement = null;

    /**
     * Description détaillée du cours.
     *
     * Texte limité à 100 caractères présentant le contenu et les objectifs
     * pédagogiques du cours. Ne peut pas être null en base de données.
     * Note : 100 caractères est une limite assez courte pour une description
     * — envisager d'augmenter à 255 ou d'utiliser un type TEXT.
     *
     * @var string|null
     */
    #[ORM\Column(length: 100)]
    private ?string $description = null;

    /**
     * Prix du cours en euros.
     *
     * Stocké en FLOAT (DOUBLE PRECISION en SQL). Ne peut pas être null.
     * Note : pour des calculs financiers précis, il est recommandé d'utiliser
     * le type DECIMAL (Types::DECIMAL) afin d'éviter les imprécisions
     * inhérentes aux nombres flottants (ex : 19.99 stocké comme 19.989999...).
     *
     * @var float|null
     */
    #[ORM\Column]
    private ?float $prix = null;

    /**
     * Indique si le cours est collectif ou individuel.
     *
     * true  = cours collectif (plusieurs chiens/propriétaires ensemble).
     * false = cours individuel (un seul chien/propriétaire).
     *
     * Note : le nom $esCollectif contient une faute de frappe ("es" au lieu
     * de "est"). Il devrait être renommé en $estCollectif, et la méthode
     * isEsCollectif() en isCollectif() pour respecter les conventions PSR
     * sur les accesseurs booléens.
     *
     * @var bool|null
     */
    #[ORM\Column]
    private ?bool $esCollectif = null;

    /**
     * Nombre maximum de chiens pouvant participer au cours.
     *
     * Pertinent principalement pour les cours collectifs. Pour un cours
     * individuel ($esCollectif = false), cette valeur devrait être 1.
     * Ne peut pas être null en base de données.
     *
     * @var int|null
     */
    #[ORM\Column]
    private ?int $nbChienMax = null;

    /**
     * Durée du cours.
     *
     * Stockée en INT. Ne peut pas être null en base de données.
     * L'unité n'est pas précisée dans le code — il est recommandé d'établir
     * une convention dans le projet (minutes ou heures) et de la documenter
     * ici (ex : durée en minutes : 60 = 1h, 90 = 1h30).
     *
     * @var int|null
     */
    #[ORM\Column]
    private ?int $duree = null;

    /**
     * Niveau d'apprentissage requis pour ce cours.
     *
     * Relation ManyToOne — plusieurs cours peuvent partager le même niveau.
     * C'est le côté propriétaire (owning side) de la relation, cette entité
     * porte la clé étrangère niveaux_apprentissage_id.
     * La colonne est nullable (JoinColumn nullable: true) — un cours peut
     * exister sans niveau d'apprentissage assigné.
     *
     * @var NiveauApprentissage|null
     */
    #[ORM\ManyToOne(inversedBy: 'cours')]
    #[ORM\JoinColumn(nullable: true)]
    private ?NiveauApprentissage $niveauxApprentissage = null;

    /**
     * Collection des séances planifiées pour ce cours.
     *
     * Relation OneToMany — un cours peut être décliné en plusieurs séances
     * à des dates et heures différentes.
     * C'est le côté inverse de la relation (mappedBy: 'cours'),
     * la clé étrangère cours_id est portée par l'entité Seance.
     *
     * Pas d'orphanRemoval — supprimer un cours ne supprime pas automatiquement
     * ses séances en base. Ce comportement devrait être évalué selon les règles
     * métier : logiquement, supprimer un cours devrait supprimer ses séances.
     *
     * Initialisée dans le constructeur avec une ArrayCollection vide.
     *
     * @var Collection<int, Seance>
     */
    #[ORM\OneToMany(targetEntity: Seance::class, mappedBy: 'cours')]
    private Collection $seances;

    /**
     * Constructeur — initialise la collection de séances.
     *
     * Doctrine requiert que les collections soient initialisées avec une
     * ArrayCollection dans le constructeur pour éviter les erreurs lors
     * de l'accès à la collection avant tout chargement depuis la base.
     */
    public function __construct()
    {
        $this->seances = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant unique du cours.
     *
     * @return int|null L'ID auto-incrémenté, ou null si l'entité n'est pas encore persistée.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le type d'entraînement du cours.
     *
     * @return string|null Le type (ex : "Obéissance"), ou null s'il n'a pas encore été défini.
     */
    public function getTypeEntrainement(): ?string
    {
        return $this->typeEntrainement;
    }

    /**
     * Définit le type d'entraînement du cours.
     *
     * @param string $typeEntrainement Le type d'entraînement (max 25 caractères).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setTypeEntrainement(string $typeEntrainement): static
    {
        $this->typeEntrainement = $typeEntrainement;

        return $this;
    }

    /**
     * Retourne la description du cours.
     *
     * @return string|null La description, ou null si non définie.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Définit la description du cours.
     *
     * @param string $description La description (max 100 caractères).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Retourne le prix du cours en euros.
     *
     * @return float|null Le prix, ou null s'il n'a pas encore été défini.
     */
    public function getPrix(): ?float
    {
        return $this->prix;
    }

    /**
     * Définit le prix du cours en euros.
     *
     * Privilégier des valeurs avec au maximum 2 décimales (ex : 49.90).
     * Attention aux imprécisions des flottants pour les calculs financiers.
     *
     * @param float $prix Le prix du cours (ex : 49.90).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    /**
     * Indique si le cours est collectif.
     *
     * Retourne true si le cours accueille plusieurs chiens simultanément,
     * false s'il s'agit d'un cours individuel.
     *
     * Note : la méthode est nommée isEsCollectif() au lieu de isCollectif()
     * à cause de la faute de frappe sur le nom de la propriété ($esCollectif).
     *
     * @return bool|null true = collectif, false = individuel, null si non défini.
     */
    public function isEsCollectif(): ?bool
    {
        return $this->esCollectif;
    }

    /**
     * Définit si le cours est collectif ou individuel.
     *
     * @param bool $esCollectif true pour un cours collectif, false pour individuel.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setEsCollectif(bool $esCollectif): static
    {
        $this->esCollectif = $esCollectif;

        return $this;
    }

    /**
     * Retourne le nombre maximum de chiens autorisés dans ce cours.
     *
     * @return int|null Le nombre maximum de chiens, ou null s'il n'a pas encore été défini.
     */
    public function getNbChienMax(): ?int
    {
        return $this->nbChienMax;
    }

    /**
     * Définit le nombre maximum de chiens autorisés dans ce cours.
     *
     * Pour un cours individuel ($esCollectif = false), cette valeur devrait être 1.
     *
     * @param int $nbChienMax Le nombre maximum de chiens (ex : 6 pour un cours collectif).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setNbChienMax(int $nbChienMax): static
    {
        $this->nbChienMax = $nbChienMax;

        return $this;
    }

    /**
     * Retourne la durée du cours.
     *
     * L'unité n'est pas explicitement définie dans le code — se référer
     * à la convention du projet (probablement en minutes).
     *
     * @return int|null La durée, ou null si non définie.
     */
    public function getDuree(): ?int
    {
        return $this->duree;
    }

    /**
     * Définit la durée du cours.
     *
     * @param int $duree La durée du cours (unité à préciser : minutes recommandées).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    /**
     * Retourne le niveau d'apprentissage associé à ce cours.
     *
     * @return NiveauApprentissage|null Le niveau requis, ou null si aucun niveau n'est assigné.
     */
    public function getNiveauxApprentissage(): ?NiveauApprentissage
    {
        return $this->niveauxApprentissage;
    }

    /**
     * Définit le niveau d'apprentissage requis pour ce cours.
     *
     * Accepte null car la colonne est nullable en base (JoinColumn nullable: true).
     * Un cours sans niveau assigné est accessible à tous les niveaux.
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
     * Retourne la collection des séances planifiées pour ce cours.
     *
     * @return Collection<int, Seance> La collection des séances associées.
     */
    public function getSeances(): Collection
    {
        return $this->seances;
    }

    /**
     * Ajoute une séance à ce cours.
     *
     * Vérifie d'abord que la séance n'est pas déjà présente dans la collection
     * via contains() pour éviter les doublons. Si nouvelle, l'ajoute à la
     * collection et synchronise le côté propriétaire de la relation en
     * appelant $seance->setCours($this).
     *
     * Cette synchronisation bidirectionnelle est indispensable pour que Doctrine
     * persiste correctement la clé étrangère (cours_id) dans la table seance.
     *
     * @param Seance $seance La séance à associer à ce cours.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function addSeance(Seance $seance): static
    {
        if (!$this->seances->contains($seance)) {
            $this->seances->add($seance);
            $seance->setCours($this);
        }

        return $this;
    }

    /**
     * Retire une séance de ce cours.
     *
     * Utilise removeElement() pour retirer la séance de la collection.
     * Si l'élément était présent et a été retiré (retour true), vérifie
     * que le cours lié côté Seance est bien ce cours, puis appelle
     * setCours(null) pour dissocier le côté propriétaire.
     *
     * Attention : setCours(null) viole la contrainte NOT NULL sur la colonne
     * cours_id dans Seance (JoinColumn nullable: false). Un flush() après
     * cette opération lèverait une exception Doctrine. Il faudrait soit
     * supprimer la séance, soit lui assigner un autre cours.
     *
     * Pas d'orphanRemoval — la séance n'est pas supprimée automatiquement
     * en base lors du retrait de la collection.
     *
     * @param Seance $seance La séance à retirer de ce cours.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function removeSeance(Seance $seance): static
    {
        if ($this->seances->removeElement($seance)) {
            if ($seance->getCours() === $this) {
                $seance->setCours(null);
            }
        }

        return $this;
    }
}