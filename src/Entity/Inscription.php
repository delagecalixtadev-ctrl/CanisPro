<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Inscription - Entité représentant l'inscription d'un chien à une séance dans CanisPro.
 *
 * Une inscription est la table de liaison entre un Chien et une Séance.
 * Elle matérialise le fait qu'un chien (et donc son propriétaire) est inscrit
 * à une séance planifiée d'un cours de dressage.
 *
 * C'est une entité de relation enrichie (elle ne se contente pas de lier deux
 * entités mais porte également une donnée métier : nb_Chien_Inscrit).
 *
 * Relations :
 * - ManyToOne vers Chien  : une inscription concerne exactement un chien.
 *                           Cette entité porte la clé étrangère chien_id (JoinColumn).
 *                           La colonne est NOT NULL (nullable: false).
 *
 * - ManyToOne vers Seance : une inscription est rattachée à exactement une séance.
 *                           Cette entité porte la clé étrangère seance_id (JoinColumn).
 *                           La colonne est NOT NULL (nullable: false).
 *
 * Contrainte métier : un même chien ne devrait pas pouvoir s'inscrire deux fois
 * à la même séance. Cette contrainte d'unicité composite (chien + séance) n'est
 * pas encore définie au niveau Doctrine (#[ORM\UniqueConstraint]) et devrait
 * être ajoutée pour garantir l'intégrité des données.
 *
 * Note sur le nommage : $nb_Chien_Inscrit utilise un underscore et une majuscule,
 * ce qui ne respecte pas les conventions PSR. Il devrait être renommé en
 * $nbChienInscrit en camelCase.
 *
 * Note métier : le champ nb_Chien_Inscrit est actuellement toujours fixé à 1
 * dans MembreController::newInscription(). Si une inscription ne concerne
 * toujours qu'un seul chien (ce que confirme la relation ManyToOne vers Chien),
 * ce champ est redondant et pourrait être supprimé.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
class Inscription
{
    /**
     * Identifiant unique auto-incrémenté de l'inscription en base de données.
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
     * Nombre de chiens inscrits pour cette inscription.
     *
     * Champ nullable (nullable: true) — peut valoir null si non renseigné.
     * Dans l'implémentation actuelle de MembreController::newInscription(),
     * ce champ est systématiquement fixé à 1 car une inscription correspond
     * toujours à un seul chien (confirmé par la relation ManyToOne vers Chien).
     * Ce champ est donc potentiellement redondant et pourrait être supprimé
     * ou remplacé par une valeur par défaut fixe.
     *
     * Note : le nommage $nb_Chien_Inscrit avec underscore et majuscule ne
     * respecte pas les conventions PSR — devrait être $nbChienInscrit.
     *
     * @var int|null
     */
    #[ORM\Column(nullable: true)]
    private ?int $nb_Chien_Inscrit = null;

    /**
     * Chien inscrit à la séance.
     *
     * Relation ManyToOne — une inscription concerne exactement un chien,
     * mais un chien peut avoir plusieurs inscriptions (à différentes séances).
     * Cette entité porte la clé étrangère chien_id en base de données.
     * La colonne est NOT NULL (nullable: false) — une inscription doit
     * obligatoirement être associée à un chien.
     *
     * @var Chien|null
     */
    #[ORM\ManyToOne(targetEntity: Chien::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chien $chien = null;

    /**
     * Séance à laquelle le chien est inscrit.
     *
     * Relation ManyToOne — une inscription est rattachée à exactement une séance,
     * mais une séance peut accueillir plusieurs inscriptions (de chiens différents).
     * Cette entité porte la clé étrangère seance_id en base de données.
     * La colonne est NOT NULL (nullable: false) — une inscription doit
     * obligatoirement être associée à une séance.
     *
     * @var Seance|null
     */
    #[ORM\ManyToOne(targetEntity: Seance::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Seance $seance = null;

    /**
     * Retourne l'identifiant unique de l'inscription.
     *
     * @return int|null L'ID auto-incrémenté, ou null si l'entité n'est pas encore persistée.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le nombre de chiens inscrits pour cette inscription.
     *
     * Dans l'implémentation actuelle, cette valeur est toujours 1.
     *
     * @return int|null Le nombre de chiens inscrits, ou null si non renseigné.
     */
    public function getNbChienInscrit(): ?int
    {
        return $this->nb_Chien_Inscrit;
    }

    /**
     * Définit le nombre de chiens inscrits pour cette inscription.
     *
     * @param int|null $nb_Chien_Inscrit Le nombre de chiens (généralement 1), ou null.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setNbChienInscrit(?int $nb_Chien_Inscrit): static
    {
        $this->nb_Chien_Inscrit = $nb_Chien_Inscrit;

        return $this;
    }

    /**
     * Retourne le chien associé à cette inscription.
     *
     * @return Chien|null Le chien inscrit, ou null s'il n'a pas encore été défini.
     */
    public function getChien(): ?Chien
    {
        return $this->chien;
    }

    /**
     * Définit le chien associé à cette inscription.
     *
     * Attention : passer null est techniquement possible via ce setter mais
     * viole la contrainte NOT NULL définie sur la colonne chien_id en base.
     * Un flush() avec $chien = null lèverait une exception Doctrine.
     *
     * @param Chien|null $chien Le chien à inscrire (ne devrait pas être null en pratique).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setChien(?Chien $chien): static
    {
        $this->chien = $chien;

        return $this;
    }

    /**
     * Retourne la séance associée à cette inscription.
     *
     * @return Seance|null La séance ciblée, ou null si elle n'a pas encore été définie.
     */
    public function getSeance(): ?Seance
    {
        return $this->seance;
    }

    /**
     * Définit la séance associée à cette inscription.
     *
     * Attention : passer null est techniquement possible via ce setter mais
     * viole la contrainte NOT NULL définie sur la colonne seance_id en base.
     * Un flush() avec $seance = null lèverait une exception Doctrine.
     *
     * @param Seance|null $seance La séance cible (ne devrait pas être null en pratique).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setSeance(?Seance $seance): static
    {
        $this->seance = $seance;

        return $this;
    }
}