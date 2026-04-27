<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Seance - Entité représentant une séance planifiée dans l'école CanisPro.
 *
 * Une séance est une occurrence concrète et datée d'un Cours. Par exemple,
 * le cours "Dressage débutant" peut avoir plusieurs séances à des dates
 * et heures différentes.
 *
 * Relations :
 * - ManyToOne vers Cours    : plusieurs séances peuvent appartenir au même cours.
 *                             La clé étrangère est portée par cette entité (JoinColumn).
 *                             La colonne cours_id ne peut pas être null (nullable: false).
 * - OneToMany vers Inscription : une séance peut avoir plusieurs inscriptions de chiens.
 *                             L'option orphanRemoval: true supprime automatiquement
 *                             les inscriptions orphelines si elles sont retirées
 *                             de la collection.
 *
 * Note : le champ "date" est stocké en VARCHAR(20) au lieu d'un type DateTimeInterface.
 * Il serait recommandé de le migrer vers #[ORM\Column(type: 'date')] pour bénéficier
 * du typage natif Doctrine et faciliter les comparaisons et tris chronologiques.
 *
 * Note : le champ "heure" est stocké en INT. Il serait plus lisible de le stocker
 * en type 'time' ou de le fusionner avec "date" dans un seul champ DateTimeInterface.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: SeanceRepository::class)]
class Seance
{
    /**
     * Identifiant unique auto-incrémenté de la séance en base de données.
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
     * Date de la séance stockée sous forme de chaîne de caractères.
     *
     * Limité à 20 caractères (ex : "2024-06-15" ou "15/06/2024").
     * Ce champ devrait idéalement être migré vers un type DateTimeInterface
     * pour bénéficier du typage natif Doctrine et permettre des requêtes
     * de filtrage et de tri chronologique efficaces.
     *
     * @var string|null
     */
    #[ORM\Column(length: 20)]
    private ?string $date = null;

    /**
     * Heure de la séance stockée sous forme d'entier.
     *
     * Représente l'heure de début de la séance (ex : 9 pour 9h00, 14 pour 14h00).
     * Ce champ ne permet pas de stocker les minutes. Il serait recommandé
     * d'utiliser un type 'time' Doctrine ou de fusionner date et heure
     * dans un seul champ DateTimeInterface pour plus de précision.
     *
     * @var int|null
     */
    #[ORM\Column]
    private ?int $heure = null;

    /**
     * Cours auquel cette séance est rattachée.
     *
     * Relation ManyToOne — plusieurs séances peuvent appartenir au même cours.
     * Cette entité porte la clé étrangère (cours_id) en base de données.
     * La colonne est NOT NULL (nullable: false), ce qui signifie qu'une séance
     * doit obligatoirement être associée à un cours.
     *
     * @var Cours|null
     */
    #[ORM\ManyToOne(inversedBy: 'seances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cours $cours = null;

    /**
     * Collection des inscriptions liées à cette séance.
     *
     * Relation OneToMany — une séance peut accueillir plusieurs inscriptions de chiens.
     * C'est le côté inverse de la relation (mappedBy: 'seance'), la clé étrangère
     * est donc portée par l'entité Inscription.
     *
     * L'option orphanRemoval: true garantit que si une inscription est retirée
     * de cette collection (removeInscription), elle est automatiquement supprimée
     * en base de données, même sans appel explicite à $entityManager->remove().
     *
     * Initialisée dans le constructeur avec une ArrayCollection vide.
     *
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'seance', orphanRemoval: true)]
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
     * Retourne l'identifiant unique de la séance.
     *
     * @return int|null L'ID auto-incrémenté, ou null si l'entité n'est pas encore persistée.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne la date de la séance sous forme de chaîne.
     *
     * @return string|null La date (ex : "2024-06-15"), ou null si non définie.
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    /**
     * Définit la date de la séance.
     *
     * @param string $date La date sous forme de chaîne (ex : "2024-06-15").
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setDate(string $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Retourne l'heure de début de la séance.
     *
     * @return int|null L'heure sous forme d'entier (ex : 9 pour 9h00), ou null si non définie.
     */
    public function getHeure(): ?int
    {
        return $this->heure;
    }

    /**
     * Définit l'heure de début de la séance.
     *
     * @param int $heure L'heure de début (ex : 14 pour 14h00).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setHeure(int $heure): static
    {
        $this->heure = $heure;

        return $this;
    }

    /**
     * Retourne le cours auquel cette séance est rattachée.
     *
     * @return Cours|null Le cours associé, ou null si non défini.
     */
    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    /**
     * Définit le cours auquel cette séance est rattachée.
     *
     * @param Cours|null $cours Le cours à associer (null pour dissocier,
     *                          mais attention à la contrainte nullable: false en base).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;

        return $this;
    }

    /**
     * Retourne la collection complète des inscriptions de cette séance.
     *
     * @return Collection<int, Inscription> La collection des inscriptions associées.
     */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    /**
     * Ajoute une inscription à cette séance.
     *
     * Vérifie d'abord que l'inscription n'est pas déjà présente dans la collection
     * via contains() pour éviter les doublons. Si elle est nouvelle, l'ajoute
     * à la collection et synchronise le côté propriétaire de la relation en
     * appelant $inscription->setSeance($this).
     *
     * Cette synchronisation bidirectionnelle est indispensable pour que Doctrine
     * persiste correctement la clé étrangère (seance_id) dans la table inscription.
     *
     * @param Inscription $inscription L'inscription à ajouter à la séance.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function addInscription(Inscription $inscription): static
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setSeance($this);
        }

        return $this;
    }

    /**
     * Retire une inscription de cette séance.
     *
     * Utilise removeElement() pour retirer l'inscription de la collection.
     * Si l'élément était bien présent et a été retiré (retour true), vérifie
     * que la séance liée côté Inscription est bien cette séance, puis
     * appelle setSeance(null) pour dissocier le côté propriétaire.
     *
     * Grâce à orphanRemoval: true sur la relation, Doctrine supprimera
     * automatiquement l'inscription en base lors du prochain flush(),
     * sans nécessiter d'appel explicite à $entityManager->remove().
     *
     * @param Inscription $inscription L'inscription à retirer de la séance.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function removeInscription(Inscription $inscription): static
    {
        if ($this->inscriptions->removeElement($inscription)) {
            if ($inscription->getSeance() === $this) {
                $inscription->setSeance(null);
            }
        }

        return $this;
    }
}