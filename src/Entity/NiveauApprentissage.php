<?php

namespace App\Entity;

use App\Repository\NiveauApprentissageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * NiveauApprentissage - Entité représentant un niveau de progression dans CanisPro.
 *
 * Un niveau d'apprentissage est une donnée de référence qui catégorise à la fois
 * les chiens (selon leur progression) et les cours (selon leur difficulté).
 * Exemples de valeurs : "Débutant", "Intermédiaire", "Avancé", "Expert".
 *
 * Relations :
 * - OneToMany vers Chien : un niveau peut être associé à plusieurs chiens.
 *                          C'est le côté inverse (mappedBy: 'niveauxApprentissage'),
 *                          la clé étrangère est portée par Chien.
 *                          Pas d'orphanRemoval — supprimer un niveau ne supprime
 *                          pas les chiens associés.
 *
 * - OneToMany vers Cours : un niveau peut correspondre à plusieurs cours.
 *                          C'est également le côté inverse (mappedBy: 'niveauxApprentissage'),
 *                          la clé étrangère est portée par Cours.
 *                          Pas d'orphanRemoval — supprimer un niveau ne supprime
 *                          pas les cours associés.
 *
 * Note sur le nommage : la propriété est nommée "niveauxApprentissage" (pluriel)
 * côté Chien et Cours alors qu'il s'agit d'une relation ManyToOne (un seul niveau
 * par chien/cours). Le singulier "niveauApprentissage" serait plus approprié
 * et plus lisible.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: NiveauApprentissageRepository::class)]
class NiveauApprentissage
{
    /**
     * Identifiant unique auto-incrémenté du niveau d'apprentissage.
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
     * Libellé du niveau d'apprentissage.
     *
     * Chaîne descriptive limitée à 20 caractères représentant le nom
     * du niveau (ex : "Débutant", "Avancé").
     * Ne peut pas être null en base de données.
     *
     * @var string|null
     */
    #[ORM\Column(length: 20)]
    private ?string $libelle = null;

    /**
     * Collection des chiens ayant ce niveau d'apprentissage.
     *
     * Relation OneToMany — un niveau peut être attribué à plusieurs chiens.
     * C'est le côté inverse de la relation (mappedBy: 'niveauxApprentissage'),
     * la clé étrangère niveaux_apprentissage_id est portée par l'entité Chien.
     *
     * Pas d'orphanRemoval — retirer un chien de cette collection ou supprimer
     * ce niveau ne supprime pas le chien en base.
     *
     * Initialisée dans le constructeur avec une ArrayCollection vide.
     *
     * @var Collection<int, Chien>
     */
    #[ORM\OneToMany(targetEntity: Chien::class, mappedBy: 'niveauxApprentissage')]
    private Collection $chiens;

    /**
     * Collection des cours correspondant à ce niveau d'apprentissage.
     *
     * Relation OneToMany — un niveau peut regrouper plusieurs cours de même difficulté.
     * C'est le côté inverse de la relation (mappedBy: 'niveauxApprentissage'),
     * la clé étrangère niveaux_apprentissage_id est portée par l'entité Cours.
     *
     * Pas d'orphanRemoval — retirer un cours de cette collection ou supprimer
     * ce niveau ne supprime pas le cours en base.
     *
     * Initialisée dans le constructeur avec une ArrayCollection vide.
     *
     * @var Collection<int, Cours>
     */
    #[ORM\OneToMany(targetEntity: Cours::class, mappedBy: 'niveauxApprentissage')]
    private Collection $cours;

    /**
     * Constructeur — initialise les deux collections.
     *
     * Doctrine requiert que les collections soient initialisées avec une
     * ArrayCollection dans le constructeur pour éviter les erreurs lors
     * de l'accès aux collections avant tout chargement depuis la base.
     * Les deux collections ($chiens et $cours) sont initialisées ici.
     */
    public function __construct()
    {
        $this->chiens = new ArrayCollection();
        $this->cours  = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant unique du niveau d'apprentissage.
     *
     * @return int|null L'ID auto-incrémenté, ou null si l'entité n'est pas encore persistée.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le libellé du niveau d'apprentissage.
     *
     * @return string|null Le libellé (ex : "Débutant"), ou null s'il n'a pas encore été défini.
     */
    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    /**
     * Définit le libellé du niveau d'apprentissage.
     *
     * @param string $libelle Le libellé du niveau (max 20 caractères, ex : "Intermédiaire").
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * Retourne la collection des chiens ayant ce niveau d'apprentissage.
     *
     * @return Collection<int, Chien> La collection des chiens associés à ce niveau.
     */
    public function getChiens(): Collection
    {
        return $this->chiens;
    }

    /**
     * Associe un chien à ce niveau d'apprentissage.
     *
     * Vérifie d'abord que le chien n'est pas déjà présent dans la collection
     * via contains() pour éviter les doublons. Si nouveau, l'ajoute à la
     * collection et synchronise le côté propriétaire de la relation en
     * appelant $chien->setNiveauxApprentissage($this).
     *
     * Cette synchronisation bidirectionnelle est indispensable pour que Doctrine
     * persiste correctement la clé étrangère dans la table chien.
     *
     * @param Chien $chien Le chien à associer à ce niveau.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function addChien(Chien $chien): static
    {
        if (!$this->chiens->contains($chien)) {
            $this->chiens->add($chien);
            $chien->setNiveauxApprentissage($this);
        }

        return $this;
    }

    /**
     * Dissocie un chien de ce niveau d'apprentissage.
     *
     * Utilise removeElement() pour retirer le chien de la collection.
     * Si l'élément était présent et a été retiré (retour true), vérifie
     * que le niveau lié côté Chien est bien ce niveau, puis appelle
     * setNiveauxApprentissage(null) pour dissocier le côté propriétaire.
     *
     * Pas d'orphanRemoval — le chien n'est pas supprimé en base,
     * il se retrouve simplement sans niveau d'apprentissage assigné.
     *
     * @param Chien $chien Le chien à dissocier de ce niveau.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function removeChien(Chien $chien): static
    {
        if ($this->chiens->removeElement($chien)) {
            if ($chien->getNiveauxApprentissage() === $this) {
                $chien->setNiveauxApprentissage(null);
            }
        }

        return $this;
    }

    /**
     * Retourne la collection des cours correspondant à ce niveau d'apprentissage.
     *
     * @return Collection<int, Cours> La collection des cours associés à ce niveau.
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    /**
     * Associe un cours à ce niveau d'apprentissage.
     *
     * Vérifie d'abord que le cours n'est pas déjà présent dans la collection
     * via contains() pour éviter les doublons. Si nouveau, l'ajoute à la
     * collection et synchronise le côté propriétaire de la relation en
     * appelant $cour->setNiveauxApprentissage($this).
     *
     * Note : le paramètre est nommé $cour (singulier sans 's') par cohérence
     * avec la convention utilisée ailleurs dans le projet pour l'entité Cours.
     *
     * @param Cours $cour Le cours à associer à ce niveau.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
            $cour->setNiveauxApprentissage($this);
        }

        return $this;
    }

    /**
     * Dissocie un cours de ce niveau d'apprentissage.
     *
     * Utilise removeElement() pour retirer le cours de la collection.
     * Si l'élément était présent et a été retiré (retour true), vérifie
     * que le niveau lié côté Cours est bien ce niveau, puis appelle
     * setNiveauxApprentissage(null) pour dissocier le côté propriétaire.
     *
     * Pas d'orphanRemoval — le cours n'est pas supprimé en base,
     * il se retrouve simplement sans niveau d'apprentissage assigné.
     *
     * @param Cours $cour Le cours à dissocier de ce niveau.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function removeCour(Cours $cour): static
    {
        if ($this->cours->removeElement($cour)) {
            if ($cour->getNiveauxApprentissage() === $this) {
                $cour->setNiveauxApprentissage(null);
            }
        }

        return $this;
    }
}