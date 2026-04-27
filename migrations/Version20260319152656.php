<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319152656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chien (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(30) NOT NULL, date_naissance VARCHAR(20) NOT NULL, race_id INT DEFAULT NULL, niveaux_apprentissage_id INT DEFAULT NULL, proprietaire_id INT DEFAULT NULL, INDEX IDX_13A4067E6E59D40D (race_id), INDEX IDX_13A4067E32D67822 (niveaux_apprentissage_id), INDEX IDX_13A4067E76C50E4A (proprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE cours (id INT AUTO_INCREMENT NOT NULL, type_entrainement VARCHAR(25) NOT NULL, description VARCHAR(100) NOT NULL, prix DOUBLE PRECISION NOT NULL, es_collectif TINYINT NOT NULL, nb_chien_max INT NOT NULL, duree INT NOT NULL, niveaux_apprentissage_id INT DEFAULT NULL, INDEX IDX_FDCA8C9C32D67822 (niveaux_apprentissage_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE inscription (id INT AUTO_INCREMENT NOT NULL, nb_chien_inscrit INT DEFAULT NULL, chien_id INT NOT NULL, seance_id INT NOT NULL, INDEX IDX_5E90F6D6BFCF400E (chien_id), INDEX IDX_5E90F6D6E3797A94 (seance_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE niveau_apprentissage (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE proprietaire (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(25) NOT NULL, prenom VARCHAR(25) NOT NULL, email VARCHAR(150) DEFAULT NULL, tel VARCHAR(20) DEFAULT NULL, date_naissance VARCHAR(20) NOT NULL, adresse VARCHAR(150) NOT NULL, code_postal INT NOT NULL, ville VARCHAR(25) NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_69E399D6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE race (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(30) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE seance (id INT AUTO_INCREMENT NOT NULL, date VARCHAR(20) NOT NULL, heure INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_DF7DFD0E7ECF78B0 (cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, login VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_LOGIN (login), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('ALTER TABLE chien ADD CONSTRAINT FK_13A4067E6E59D40D FOREIGN KEY (race_id) REFERENCES race (id)');
        $this->addSql('ALTER TABLE chien ADD CONSTRAINT FK_13A4067E32D67822 FOREIGN KEY (niveaux_apprentissage_id) REFERENCES niveau_apprentissage (id)');
        $this->addSql('ALTER TABLE chien ADD CONSTRAINT FK_13A4067E76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES proprietaire (id)');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9C32D67822 FOREIGN KEY (niveaux_apprentissage_id) REFERENCES niveau_apprentissage (id)');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D6BFCF400E FOREIGN KEY (chien_id) REFERENCES chien (id)');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D6E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id)');
        $this->addSql('ALTER TABLE proprietaire ADD CONSTRAINT FK_69E399D6A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chien DROP FOREIGN KEY FK_13A4067E6E59D40D');
        $this->addSql('ALTER TABLE chien DROP FOREIGN KEY FK_13A4067E32D67822');
        $this->addSql('ALTER TABLE chien DROP FOREIGN KEY FK_13A4067E76C50E4A');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9C32D67822');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D6BFCF400E');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D6E3797A94');
        $this->addSql('ALTER TABLE proprietaire DROP FOREIGN KEY FK_69E399D6A76ED395');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E7ECF78B0');
        $this->addSql('DROP TABLE chien');
        $this->addSql('DROP TABLE cours');
        $this->addSql('DROP TABLE inscription');
        $this->addSql('DROP TABLE niveau_apprentissage');
        $this->addSql('DROP TABLE proprietaire');
        $this->addSql('DROP TABLE race');
        $this->addSql('DROP TABLE seance');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
