<?php

namespace App\Entity;

use App\Repository\RaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Race - Entité représentant une race de chien dans l'application CanisPro.
 *
 * Une race est une donnée de référence (ex : Berger Allemand, Labrador, Husky)
 * utilisée pour catégoriser les chiens inscrits dans l'école de dressage.
 *
 * Relations :
 * - OneToMany vers Chien : une race peut être associée à plusieurs chiens.
 *                          C'est le côté inverse de la relation (mappedBy: 'race'),
 *                          la clé étrangère est portée par l'entité Chien.
 *                          L'option orphanRemoval: true supprime automatiquement
 *                          les chiens orphelins si retirés de la collection.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: RaceRepository::class)]
class Race
{
    /**
     * Identifiant unique auto-incrémenté de la race en base de données.
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
     * Nom de la race (ex : "Berger Allemand", "Labrador", "Husky").
     *
     * Limité à 30 caractères en base de données.
     * Vaut null tant qu'il n'a pas été défini.
     *
     * @var string|null
     */
    #[ORM\Column(length: 30)]
    private ?string $nom = null;

    /**
     * Collection des chiens appartenant à cette race.
     *
     * Relation OneToMany — une race peut regrouper plusieurs chiens.
     * C'est le côté inverse de la relation (mappedBy: 'race'),
     * la clé étrangère race_id est portée par l'entité Chien.
     *
     * L'option orphanRemoval: true garantit que si un chien est retiré
     * de cette collection, il sera automatiquement supprimé en base
     * lors du prochain flush(), sans appel explicite à remove().
     *
     * Initialisée dans le constructeur avec une ArrayCollection vide.
     *
     * @var Collection<int, Chien>
     */
    #[ORM\OneToMany(targetEntity: Chien::class, mappedBy: 'race', orphanRemoval: true)]
    private Collection $chien;

    /**
     * Constructeur — initialise la collection de chiens.
     *
     * Doctrine requiert que les collections soient initialisées avec une
     * ArrayCollection dans le constructeur pour éviter les erreurs lors
     * de l'accès à la collection avant tout chargement depuis la base.
     */
    public function __construct()
    {
        $this->chien = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant unique de la race.
     *
     * @return int|null L'ID auto-incrémenté, ou null si l'entité n'est pas encore persistée.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le nom de la race.
     *
     * @return string|null Le nom de la race, ou null s'il n'a pas encore été défini.
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Définit le nom de la race.
     *
     * @param string $nom Le nom de la race (ex : "Berger Allemand").
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Retourne la collection complète des chiens de cette race.
     *
     * @return Collection<int, Chien> La collection des chiens associés à cette race.
     */
    public function getChien(): Collection
    {
        return $this->chien;
    }

    /**
     * Ajoute un chien à cette race.
     *
     * Vérifie d'abord que le chien n'est pas déjà présent dans la collection
     * via contains() pour éviter les doublons. Si nouveau, l'ajoute à la
     * collection et synchronise le côté propriétaire de la relation en
     * appelant $chien->setRace($this).
     *
     * Cette synchronisation bidirectionnelle est indispensable pour que Doctrine
     * persiste correctement la clé étrangère (race_id) dans la table chien.
     *
     * @param Chien $chien Le chien à associer à cette race.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function addChien(Chien $chien): static
    {
        if (!$this->chien->contains($chien)) {
            $this->chien->add($chien);
            $chien->setRace($this);
        }

        return $this;
    }

    /**
     * Retire un chien de cette race.
     *
     * Utilise removeElement() pour retirer le chien de la collection.
     * Si l'élément était présent et a été retiré (retour true), vérifie
     * que la race liée côté Chien est bien cette race, puis appelle
     * setRace(null) pour dissocier le côté propriétaire.
     *
     * Grâce à orphanRemoval: true, Doctrine supprimera automatiquement
     * le chien en base lors du prochain flush() si il est retiré de
     * cette collection.
     *
     * @param Chien $chien Le chien à retirer de cette race.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function removeChien(Chien $chien): static
    {
        if ($this->chien->removeElement($chien)) {
            if ($chien->getRace() === $this) {
                $chien->setRace(null);
            }
        }

        return $this;
    }
}