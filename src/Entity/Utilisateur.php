<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Utilisateur - Entité représentant un compte utilisateur de l'application CanisPro.
 *
 * Cette entité est le pilier du système d'authentification Symfony. Elle implémente
 * deux interfaces du composant Security :
 * - UserInterface           : fournit les méthodes requises par le pare-feu Symfony
 *                             (getUserIdentifier, getRoles, eraseCredentials).
 * - PasswordAuthenticatedUserInterface : indique que cet utilisateur s'authentifie
 *                             par mot de passe (getPassword).
 *
 * Chaque Utilisateur est lié à exactement un Proprietaire via une relation
 * OneToOne bidirectionnelle. L'Utilisateur est le côté "inverse" de cette relation
 * (mappedBy), ce qui signifie que c'est le Proprietaire qui détient la clé étrangère.
 *
 * Le cascade ['persist', 'remove'] garantit que toute opération de persistance
 * ou suppression sur l'Utilisateur est automatiquement propagée au Proprietaire lié.
 *
 * Contrainte d'unicité : le champ "login" est unique en base de données
 * grâce à l'attribut #[ORM\UniqueConstraint].
 *
 * Table Doctrine : générée automatiquement depuis le nom de la classe.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_LOGIN', fields: ['login'])]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Identifiant unique auto-incrémenté de l'utilisateur en base de données.
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
     * Login (identifiant de connexion) de l'utilisateur.
     *
     * Utilisé comme identifiant unique de connexion (getUserIdentifier).
     * Limité à 180 caractères et soumis à une contrainte d'unicité en base
     * via #[ORM\UniqueConstraint]. Ne peut pas être null en base de données.
     *
     * @var string|null
     */
    #[ORM\Column(length: 180)]
    private ?string $login = null;

    /**
     * Liste des rôles attribués à l'utilisateur.
     *
     * Stocké en JSON en base de données. Chaque rôle est une chaîne préfixée
     * par "ROLE_" (ex : "ROLE_USER", "ROLE_ADMIN").
     * Le rôle "ROLE_USER" est toujours ajouté automatiquement par getRoles()
     * même s'il n'est pas explicitement présent dans ce tableau, garantissant
     * ainsi que tout utilisateur possède au minimum ce rôle de base.
     *
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Mot de passe haché de l'utilisateur.
     *
     * Ne stocke jamais le mot de passe en clair — uniquement le hash généré
     * par UserPasswordHasherInterface (algorithme configuré dans security.yaml,
     * généralement bcrypt ou sodium).
     * La méthode __serialize() remplace ce hash par un CRC32C avant sérialisation
     * en session pour éviter d'exposer le vrai hash dans les données de session.
     *
     * @var string|null
     * @see PasswordAuthenticatedUserInterface
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Propriétaire associé à cet utilisateur.
     *
     * Relation OneToOne bidirectionnelle — côté inverse (mappedBy).
     * La clé étrangère est détenue par l'entité Proprietaire (côté propriétaire
     * de la relation). Le cascade ['persist', 'remove'] propage automatiquement
     * les opérations Doctrine vers le Proprietaire lié.
     *
     * Vaut null si aucun Proprietaire n'est encore associé à cet utilisateur.
     *
     * @var Proprietaire|null
     */
    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Proprietaire $proprietaire = null;

    /**
     * Retourne l'identifiant technique unique de l'utilisateur en base.
     *
     * @return int|null L'ID auto-incrémenté, ou null si l'entité n'est pas encore persistée.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le login de l'utilisateur.
     *
     * @return string|null Le login, ou null s'il n'a pas encore été défini.
     */
    public function getLogin(): ?string
    {
        return $this->login;
    }

    /**
     * Définit le login de l'utilisateur.
     *
     * @param string $login Le login à attribuer (doit être unique en base).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Retourne l'identifiant visuel représentant cet utilisateur pour Symfony Security.
     *
     * Implémente UserInterface::getUserIdentifier(). C'est cette valeur que Symfony
     * utilise comme identifiant de session et dans les logs de sécurité.
     * Ici, c'est le login qui joue ce rôle d'identifiant unique.
     *
     * @see UserInterface
     * @return string Le login converti en chaîne (cast sécurisé depuis ?string).
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

    /**
     * Retourne la liste complète des rôles de l'utilisateur.
     *
     * Implémente UserInterface::getRoles(). Ajoute systématiquement "ROLE_USER"
     * à la liste des rôles stockés en base, puis déduplique via array_unique()
     * pour éviter les doublons si "ROLE_USER" était déjà présent explicitement.
     *
     * @see UserInterface
     * @return list<string> Tableau de chaînes représentant les rôles (ex : ['ROLE_USER', 'ROLE_ADMIN']).
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Définit les rôles de l'utilisateur.
     *
     * Remplace entièrement la liste des rôles existants. "ROLE_USER" n'a pas
     * besoin d'être inclus car il est ajouté automatiquement par getRoles().
     *
     * @param list<string> $roles Tableau de rôles à attribuer (ex : ['ROLE_ADMIN']).
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Retourne le mot de passe haché de l'utilisateur.
     *
     * Implémente PasswordAuthenticatedUserInterface::getPassword().
     * Utilisé par Symfony pour vérifier le mot de passe lors de l'authentification.
     * Ne retourne jamais le mot de passe en clair.
     *
     * @see PasswordAuthenticatedUserInterface
     * @return string|null Le hash du mot de passe, ou null s'il n'est pas encore défini.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Définit le mot de passe haché de l'utilisateur.
     *
     * Cette méthode attend un mot de passe déjà haché par UserPasswordHasherInterface.
     * Ne jamais passer un mot de passe en clair directement.
     *
     * Exemple d'utilisation correcte :
     * $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
     *
     * @param string $password Le mot de passe haché à stocker.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Sérialise l'entité pour stockage en session en remplaçant le hash du mot de passe.
     *
     * Surcharge la sérialisation PHP native pour des raisons de sécurité.
     * Au lieu de stocker le vrai hash bcrypt/sodium en session (ce qui exposerait
     * une donnée sensible), ce hash est remplacé par son empreinte CRC32C,
     * un algorithme de checksum rapide et non réversible supporté depuis Symfony 7.3.
     *
     * Cela permet à Symfony de détecter un changement de mot de passe entre deux
     * requêtes (invalidation de session) sans stocker le vrai hash en session.
     *
     * @return array<string, mixed> Les données sérialisées avec le hash remplacé par son CRC32C.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    /**
     * Retourne le Proprietaire associé à cet utilisateur.
     *
     * @return Proprietaire|null Le propriétaire lié, ou null si aucun n'est associé.
     */
    public function getProprietaire(): ?Proprietaire
    {
        return $this->proprietaire;
    }

    /**
     * Définit ou retire le Proprietaire associé à cet utilisateur.
     *
     * Gère la cohérence des deux côtés de la relation OneToOne bidirectionnelle :
     *
     * - Si $proprietaire est null et qu'un propriétaire était déjà lié :
     *   appelle setUser(null) sur l'ancien propriétaire pour désynchroniser
     *   le côté propriétaire (owning side) de la relation.
     *
     * - Si $proprietaire est non null et que son utilisateur lié n'est pas encore
     *   cet utilisateur : appelle setUser($this) pour synchroniser le côté
     *   propriétaire (owning side) et garantir la cohérence en base.
     *
     * Cette double vérification est le pattern standard Symfony pour maintenir
     * la synchronisation des relations bidirectionnelles sans boucle infinie.
     *
     * @param Proprietaire|null $proprietaire Le propriétaire à associer, ou null pour dissocier.
     * @return static L'instance courante pour permettre le chaînage de méthodes.
     */
    public function setProprietaire(?Proprietaire $proprietaire): static
    {
        if ($proprietaire === null && $this->proprietaire !== null) {
            $this->proprietaire->setUser(null);
        }

        if ($proprietaire !== null && $proprietaire->getUser() !== $this) {
            $proprietaire->setUser($this);
        }

        $this->proprietaire = $proprietaire;

        return $this;
    }
}