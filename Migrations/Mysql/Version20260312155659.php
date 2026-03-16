<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312155659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1043Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1043Platform'."
        );

        // Check, if the tables already exist, to prevent errors if an older version without migrations was installed before.
        if ($schema->hasTable('neosrulez_directmail_domain_model_import')) {
            return;
        }

        $this->addSql('CREATE TABLE neosrulez_directmail_domain_model_import (persistence_object_identifier VARCHAR(40) NOT NULL, file VARCHAR(40) DEFAULT NULL, recipientlist VARCHAR(40) DEFAULT NULL, created DATETIME NOT NULL, UNIQUE INDEX UNIQ_BCC8EB388C9F3610 (file), INDEX IDX_BCC8EB383C405FF0 (recipientlist), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neosrulez_directmail_domain_model_job (persistence_object_identifier VARCHAR(40) NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neosrulez_directmail_domain_model_queue (persistence_object_identifier VARCHAR(40) NOT NULL, name VARCHAR(255) NOT NULL, nodeuri VARCHAR(255) NOT NULL, send DATETIME NOT NULL, done TINYINT(1) NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neosrulez_directmail_domain_model_queue_recipientlist_join (directmail_queue VARCHAR(40) NOT NULL, directmail_recipientlist VARCHAR(40) NOT NULL, INDEX IDX_3A956C4C94D9EC8F (directmail_queue), INDEX IDX_3A956C4CF526B291 (directmail_recipientlist), PRIMARY KEY(directmail_queue, directmail_recipientlist)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neosrulez_directmail_domain_model_queuerecipient (persistence_object_identifier VARCHAR(40) NOT NULL, recipient VARCHAR(40) DEFAULT NULL, queue VARCHAR(40) DEFAULT NULL, sent TINYINT(1) NOT NULL, created DATETIME NOT NULL, INDEX IDX_9F1CAA616804FB49 (recipient), INDEX IDX_9F1CAA617FFD7F63 (queue), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neosrulez_directmail_domain_model_recipient (persistence_object_identifier VARCHAR(40) NOT NULL, gender INT NOT NULL, importedviaapi TINYINT(1) DEFAULT 0, customsalutation VARCHAR(255) DEFAULT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, dimensions VARCHAR(255) DEFAULT NULL, customfields LONGTEXT NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neosrulez_directmail_domain_model_recipient_recipientlist_join (directmail_recipient VARCHAR(40) NOT NULL, directmail_recipientlist VARCHAR(40) NOT NULL, INDEX IDX_FA3DFD8ACDC8D936 (directmail_recipient), INDEX IDX_FA3DFD8AF526B291 (directmail_recipientlist), PRIMARY KEY(directmail_recipient, directmail_recipientlist)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neosrulez_directmail_domain_model_recipientlist (persistence_object_identifier VARCHAR(40) NOT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neosrulez_directmail_domain_model_tracking (persistence_object_identifier VARCHAR(40) NOT NULL, queue VARCHAR(40) DEFAULT NULL, recipient VARCHAR(40) DEFAULT NULL, action VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, created DATETIME NOT NULL, INDEX IDX_B8B473A7FFD7F63 (queue), INDEX IDX_B8B473A6804FB49 (recipient), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_import ADD CONSTRAINT FK_BCC8EB388C9F3610 FOREIGN KEY (file) REFERENCES neos_flow_resourcemanagement_persistentresource (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_import ADD CONSTRAINT FK_BCC8EB383C405FF0 FOREIGN KEY (recipientlist) REFERENCES neosrulez_directmail_domain_model_recipientlist (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_queue_recipientlist_join ADD CONSTRAINT FK_3A956C4C94D9EC8F FOREIGN KEY (directmail_queue) REFERENCES neosrulez_directmail_domain_model_queue (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_queue_recipientlist_join ADD CONSTRAINT FK_3A956C4CF526B291 FOREIGN KEY (directmail_recipientlist) REFERENCES neosrulez_directmail_domain_model_recipientlist (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_queuerecipient ADD CONSTRAINT FK_9F1CAA616804FB49 FOREIGN KEY (recipient) REFERENCES neosrulez_directmail_domain_model_recipient (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_queuerecipient ADD CONSTRAINT FK_9F1CAA617FFD7F63 FOREIGN KEY (queue) REFERENCES neosrulez_directmail_domain_model_queue (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_recipient_recipientlist_join ADD CONSTRAINT FK_FA3DFD8ACDC8D936 FOREIGN KEY (directmail_recipient) REFERENCES neosrulez_directmail_domain_model_recipient (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_recipient_recipientlist_join ADD CONSTRAINT FK_FA3DFD8AF526B291 FOREIGN KEY (directmail_recipientlist) REFERENCES neosrulez_directmail_domain_model_recipientlist (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_tracking ADD CONSTRAINT FK_B8B473A7FFD7F63 FOREIGN KEY (queue) REFERENCES neosrulez_directmail_domain_model_queue (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_tracking ADD CONSTRAINT FK_B8B473A6804FB49 FOREIGN KEY (recipient) REFERENCES neosrulez_directmail_domain_model_recipient (persistence_object_identifier)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1043Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1043Platform'."
        );

        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_import DROP FOREIGN KEY FK_BCC8EB388C9F3610');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_import DROP FOREIGN KEY FK_BCC8EB383C405FF0');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_queue_recipientlist_join DROP FOREIGN KEY FK_3A956C4C94D9EC8F');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_queue_recipientlist_join DROP FOREIGN KEY FK_3A956C4CF526B291');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_queuerecipient DROP FOREIGN KEY FK_9F1CAA616804FB49');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_queuerecipient DROP FOREIGN KEY FK_9F1CAA617FFD7F63');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_recipient_recipientlist_join DROP FOREIGN KEY FK_FA3DFD8ACDC8D936');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_recipient_recipientlist_join DROP FOREIGN KEY FK_FA3DFD8AF526B291');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_tracking DROP FOREIGN KEY FK_B8B473A7FFD7F63');
        $this->addSql('ALTER TABLE neosrulez_directmail_domain_model_tracking DROP FOREIGN KEY FK_B8B473A6804FB49');

        $this->addSql('DROP TABLE neosrulez_directmail_domain_model_import');
        $this->addSql('DROP TABLE neosrulez_directmail_domain_model_job');
        $this->addSql('DROP TABLE neosrulez_directmail_domain_model_queue');
        $this->addSql('DROP TABLE neosrulez_directmail_domain_model_queue_recipientlist_join');
        $this->addSql('DROP TABLE neosrulez_directmail_domain_model_queuerecipient');
        $this->addSql('DROP TABLE neosrulez_directmail_domain_model_recipient');
        $this->addSql('DROP TABLE neosrulez_directmail_domain_model_recipient_recipientlist_join');
        $this->addSql('DROP TABLE neosrulez_directmail_domain_model_recipientlist');
        $this->addSql('DROP TABLE neosrulez_directmail_domain_model_tracking');
    }
}
