<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create webauthn_credentials table for passkey metadata';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if ($platform === 'postgresql') {
            $this->addSql('CREATE TABLE IF NOT EXISTS webauthn_credentials (id SERIAL NOT NULL, user_id UUID NOT NULL, credential_id VARCHAR(255) NOT NULL, transports JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_WEBAUTHN_CREDENTIAL_ID ON webauthn_credentials (credential_id)');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_WEBAUTHN_USER ON webauthn_credentials (user_id)');
            $this->addSql('ALTER TABLE webauthn_credentials ADD CONSTRAINT FK_WEBAUTHN_USER FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        } else {
            $this->addSql('CREATE TABLE IF NOT EXISTS webauthn_credentials (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL, credential_id VARCHAR(255) NOT NULL, transports JSON DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_WEBAUTHN_CREDENTIAL_ID (credential_id), INDEX IDX_WEBAUTHN_USER (user_id), PRIMARY KEY(id))');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS webauthn_credentials');
    }
}
