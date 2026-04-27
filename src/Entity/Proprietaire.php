<?php

namespace App\Entity;

use App\Repository\ProprietaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Proprietaire - Entité représentant le profil personnel d'un membre de CanisPro.
 *
 * Un Proprietaire est la fiche personnelle d'un membre de l'école de dressage.
 * Il est systématiquement lié à un Utilisateur (compte de connexion) via une
 * relation OneToOne. Cette séparation permet de distinguer les données
 * d'authentification (login, password, roles) des données personnelles
 * (nom, prénom, adresse, etc.).
 *
 * Relations :
 * - OneToOne vers Utilisateur : chaque propriétaire possède exactement un compte
 *                               utilisateur. C'est le côté propriétaire 
 *                               de la relation — il détient la clé étrangère user_id.
 *                               Le cascade ['persist', 'remove'] propage les opérations
 *                               Doctrine vers l'Utilisateur lié.
 * - OneToMany vers Chien      : un propriétaire peut posséder plusieurs chiens.
 *                               C'est le côté inverse (mappedBy: 'proprietaire'),
 *                               la clé étrangère est portée par Chien.
 *
 * Note sur le nommage : plusieurs propriétés utilisent une majuscule initiale
 * ($Nom, $Prenom, $Adresse, $Ville, $Code_Postal) ce qui ne respecte pas
 * les conventions PSR (camelCase sans majuscule initiale). Il est recommandé
 * de les renommer en $nom, $prenom, $adresse, $ville, $codePostal pour la cohérence.
 *
 * Note sur les types : $date_Naissance est stocké en VARCHAR(20) au lieu d'un
 * type DateTimeInterface natif Doctrine, ce qui limite les possibilités de
 * filtrage et de tri par date.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: ProprietaireRepository::class)]
class Proprietaire
{
    /**
     * Identifiant unique auto-incrémenté du propriétaire en base de données.
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
     * Nom de famille du propriétaire.
     *
     * Limité à 25 caractères. Ne peut pas être null en base de données.
     * Note : la majuscule initiale ($Nom) ne respecte pas les conventions PSR.
     *
     * @var string|null
     */
    #[ORM\Column(length: 25)]
    private ?string $Nom = null;

    /**
     * Prénom du propriétaire.
     *
     * Limité à 25 caractères. Ne peut pas être null en base de données.
     * Note : la majuscule initiale ($Prenom) ne respecte pas les conventions PSR.
     *
     * @var string|null
     */
    #[ORM\Column(length: 25)]
    private ?string $Prenom = null;

    /**
     * Adresse email du propriétaire.
     *
     * Limité à 150 caractères. Champ optionnel (nullable: true) —
     * un propriétaire peut ne pas avoir d'email renseigné.
     *
     * @var string|null
     */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email = null;

    /**
     * Numéro de téléphone du propriétaire.
     *
     * Limité à 20 caractères. Champ optionnel (nullable: true) —
     * stocké en VARCHAR pour gérer les formats internationaux
     * et les espaces (ex : "06 12 34 56 78" ou "+33612345678").
     *
     * @var string|null
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tel = null;

    /**
     * Date de naissance du propriétaire stockée en chaîne de caractères.
     *
     * Limité à 20 caractères (ex : "1990-05-15" ou "15/05/1990").
     * Ne peut pas être null en base de données.
     * Note : ce champ devrait idéalement être migré vers un type
     * DateTimeInterface (Types::DATE_MUTABLE) pour bénéficier du
     * typage natif Doctrine et faciliter le calcul d'âge ou les filtres.
     *
     * @var string|null
     */
    #[ORM\Column(length: 20)]
    private ?string $date_Naissance = null;

    /**
     * Adresse postale complète du propriétaire.
     *
     * Limité à 150 caractères. Ne peut pas être null en base de données.
     * Contient le numéro et le nom de la rue (le code postal et la ville
     * sont stockés dans des champs séparés).
     * Note : la majuscule initiale ($Adresse) ne respecte pas les conventions PSR.
     *
     * @var string|null
     */
    #[ORM\Column(length: 150)]
    private ?string $Adresse = null;

    /**
     * Code postal du propriétaire.
     *
     * Stocké en INT. Ne peut pas être null en base de données.
     * Note : le nommage $Code_Postal avec underscore ne respecte pas
     * les conventions PSR (devrait être $codePostal en camelCase).
     * Attention : stocker le code postal en INT perd le zéro initial
     * pour les départements 01 à 09 (ex : 01000 devient 1000).
     * Il serait plus sûr de le stocker en VARCHAR.
     *
     * @var int|null
     */
    #[ORM\Column]
    private ?int $Code_Postal = null;

    /**
     * Ville de résidence du propriétaire.
     *
     * Limité à 25 caractères. Ne peut pas être null en base de données.
     * Note : la majuscule initiale ($Ville) ne respecte pas les conventions PSR.
     *
     * @var string|null
     */
    #[ORM\Column(length: 25)]
    private ?string $Ville = null;

    /**
     * Collection des chiens appartenant à ce propriétaire.
     *
     * Relation OneToMany — un propriétaire peut posséder plusieurs chiens.
     * C'est le côté inverse de la relation (mappedBy: 'proprietaire'),
     * la clé étrangère proprietaire_id est portée par l'entité Chien.
     *
     * Contrairement à la relation avec Seance/Inscription, il n'y a pas
     * d'orphanRemoval ici — supprimer un propriétaire ne supprime pas
     * automatiquement ses chiens. Ce comportement devrait être évalué
     * selon les règles métier de l'application.
     *
     * Initialisée dans le constructeur avec une ArrayCollection vide.
     *
     * @var Collection<int, Chien>
     */
    #[ORM\OneToMany(targetEntity: Chien::class, mappedBy: 'proprietaire')]
    private Collection $chiens;

    /**
     * Compte utilisateur lié à ce propriétaire.
     *
     * Relation OneToOne — c'est le côté propriétaire (owning side) de la relation,
     * ce qui signifie que cette entité détient la clé étrangère user_id en base.
     * C'est l'inverse de la relation définie dans Utilisateur (inversedBy: 'proprietaire').
     *
     * Le cascade ['persist', 'remove'] propage automatiquement les opérations
     * Doctrine vers l'Utilisateur lié : persister un Proprietaire persiste aussi
     * son Utilisateur, et le supprimer supprime aussi son compte.
     *
     * Vaut null si aucun compte utilisateur n'est encore associé.
     *
     * @var Utilisateur|null
     */
    #[ORM\OneToOne(inversedBy: 'proprietaire', cascade: ['persist', 'remove'])]
    private ?Utilisateur $user = null;

    /**
     * Constructeur — initialise la collection de chiens.
     *
     * Doctrine requiert que les collections soient initialisées avec une
     * ArrayCollection dans le constructeur pour éviter les erreurs lors
     * de l'accès à la collection avant tout chargement depuis la base.
     */
    public function __construct()
    {
        $this->chiens = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant unique du propriétaire.
     *
     * @return int|null L'ID auto-incrémenté, ou null si l'entité n'est pas encore persistée.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le nom de famille du propriétaire.
     *
     * @return string|null Le nom, ou null s'il n'a pas encore été défini.
     */
    public function getNom(): ?string
    {
        return $this->Nom;
    }

    /**
     * Définit le nom de famille du propriétaire.
     *
     * @param string $Nom Le nom de famille (max 25 caractères).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setNom(string $Nom): static
    {
        $this->Nom = $Nom;

        return $this;
    }

    /**
     * Retourne le prénom du propriétaire.
     *
     * @return string|null Le prénom, ou null s'il n'a pas encore été défini.
     */
    public function getPrenom(): ?string
    {
        return $this->Prenom;
    }

    /**
     * Définit le prénom du propriétaire.
     *
     * @param string $Prenom Le prénom (max 25 caractères).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setPrenom(string $Prenom): static
    {
        $this->Prenom = $Prenom;

        return $this;
    }

    /**
     * Retourne l'adresse email du propriétaire.
     *
     * @return string|null L'email, ou null s'il n'a pas été renseigné.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Définit l'adresse email du propriétaire.
     *
     * @param string|null $email L'email (max 150 caractères), ou null pour le vider.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Retourne le numéro de téléphone du propriétaire.
     *
     * @return string|null Le téléphone, ou null s'il n'a pas été renseigné.
     */
    public function getTel(): ?string
    {
        return $this->tel;
    }

    /**
     * Définit le numéro de téléphone du propriétaire.
     *
     * @param string|null $tel Le téléphone (max 20 caractères), ou null pour le vider.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setTel(?string $tel): static
    {
        $this->tel = $tel;

        return $this;
    }

    /**
     * Retourne la date de naissance du propriétaire sous forme de chaîne.
     *
     * @return string|null La date de naissance (ex : "1990-05-15"), ou null si non définie.
     */
    public function getDateNaissance(): ?string
    {
        return $this->date_Naissance;
    }

    /**
     * Définit la date de naissance du propriétaire.
     *
     * @param string $date_Naissance La date de naissance sous forme de chaîne.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setDateNaissance(string $date_Naissance): static
    {
        $this->date_Naissance = $date_Naissance;

        return $this;
    }

    /**
     * Retourne l'adresse postale du propriétaire.
     *
     * @return string|null L'adresse, ou null si non définie.
     */
    public function getAdresse(): ?string
    {
        return $this->Adresse;
    }

    /**
     * Définit l'adresse postale du propriétaire.
     *
     * @param string $Adresse L'adresse complète (numéro + rue, max 150 caractères).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setAdresse(string $Adresse): static
    {
        $this->Adresse = $Adresse;

        return $this;
    }

    /**
     * Retourne le code postal du propriétaire.
     *
     * @return int|null Le code postal, ou null s'il n'a pas encore été défini.
     */
    public function getCodePostal(): ?int
    {
        return $this->Code_Postal;
    }

    /**
     * Définit le code postal du propriétaire.
     *
     * Attention : stocké en INT, les zéros initiaux des départements 01 à 09
     * seront perdus (ex : 01000 → 1000). Envisager une migration vers VARCHAR.
     *
     * @param int $Code_Postal Le code postal sous forme d'entier.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setCodePostal(int $Code_Postal): static
    {
        $this->Code_Postal = $Code_Postal;

        return $this;
    }

    /**
     * Retourne la ville de résidence du propriétaire.
     *
     * @return string|null La ville, ou null si non définie.
     */
    public function getVille(): ?string
    {
        return $this->Ville;
    }

    /**
     * Définit la ville de résidence du propriétaire.
     *
     * @param string $Ville La ville (max 25 caractères).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setVille(string $Ville): static
    {
        $this->Ville = $Ville;

        return $this;
    }

    /**
     * Retourne la collection complète des chiens de ce propriétaire.
     *
     * @return Collection<int, Chien> La collection des chiens associés.
     */
    public function getChiens(): Collection
    {
        return $this->chiens;
    }

    /**
     * Ajoute un chien à ce propriétaire.
     *
     * Vérifie d'abord que le chien n'est pas déjà présent dans la collection
     * via contains() pour éviter les doublons. Si nouveau, l'ajoute à la
     * collection et synchronise le côté propriétaire de la relation en
     * appelant $chien->setProprietaire($this).
     *
     * Cette synchronisation bidirectionnelle est indispensable pour que Doctrine
     * persiste correctement la clé étrangère (proprietaire_id) dans la table chien.
     *
     * @param Chien $chien Le chien à associer à ce propriétaire.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function addChien(Chien $chien): static
    {
        if (!$this->chiens->contains($chien)) {
            $this->chiens->add($chien);
            $chien->setProprietaire($this);
        }

        return $this;
    }

    /**
     * Retire un chien de ce propriétaire.
     *
     * Utilise removeElement() pour retirer le chien de la collection.
     * Si l'élément était présent et a été retiré (retour true), vérifie
     * que le propriétaire lié côté Chien est bien ce propriétaire, puis
     * appelle setProprietaire(null) pour dissocier le côté propriétaire.
     *
     * Note : contrairement à d'autres relations du projet, il n'y a pas
     * d'orphanRemoval ici — retirer un chien de cette collection ne le
     * supprime pas automatiquement en base.
     *
     * @param Chien $chien Le chien à retirer de ce propriétaire.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function removeChien(Chien $chien): static
    {
        if ($this->chiens->removeElement($chien)) {
            if ($chien->getProprietaire() === $this) {
                $chien->setProprietaire(null);
            }
        }

        return $this;
    }

    /**
     * Retourne le compte utilisateur associé à ce propriétaire.
     *
     * @return Utilisateur|null L'utilisateur lié, ou null si aucun n'est associé.
     */
    public function getUser(): ?Utilisateur
    {
        return $this->user;
    }

    /**
     * Définit ou retire le compte utilisateur associé à ce propriétaire.
     *
     * Comme cette entité est le côté propriétaire (owning side) de la relation
     * OneToOne, il suffit de définir $this->user pour que Doctrine enregistre
     * la clé étrangère user_id en base lors du flush().
     *
     * La synchronisation du côté inverse (Utilisateur::setProprietaire) est
     * gérée dans Utilisateur::setProprietaire() — il n'est pas nécessaire
     * de la répliquer ici pour éviter une boucle infinie.
     *
     * @param Utilisateur|null $user L'utilisateur à associer, ou null pour dissocier.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setUser(?Utilisateur $user): static
    {
        $this->user = $user;

        return $this;
    }
}