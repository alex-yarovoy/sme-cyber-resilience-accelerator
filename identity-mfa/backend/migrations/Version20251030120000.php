<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251030120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users, audit_logs, user_sessions tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS users (id UUID PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, mfa_enabled BOOLEAN DEFAULT FALSE, mfa_secret VARCHAR(255) DEFAULT NULL, backup_codes JSON DEFAULT NULL, created_at TIMESTAMP NOT NULL, updated_at TIMESTAMP NOT NULL, last_login_at TIMESTAMP DEFAULT NULL, is_active BOOLEAN DEFAULT TRUE, is_verified BOOLEAN DEFAULT FALSE, verification_token VARCHAR(255) DEFAULT NULL, verification_token_expires_at TIMESTAMP DEFAULT NULL, reset_password_token VARCHAR(255) DEFAULT NULL, reset_password_token_expires_at TIMESTAMP DEFAULT NULL);");
        $this->addSql("CREATE TABLE IF NOT EXISTS audit_logs (id UUID PRIMARY KEY, user_id UUID NULL, action VARCHAR(100) NOT NULL, ip_address INET NULL, user_agent TEXT NULL, metadata JSON NULL, created_at TIMESTAMP NOT NULL, level VARCHAR(50) NOT NULL, resource VARCHAR(255) NULL);");
        $this->addSql("CREATE TABLE IF NOT EXISTS user_sessions (id VARCHAR(128) PRIMARY KEY, user_id UUID NOT NULL, data BYTEA NULL, created_at TIMESTAMP NOT NULL, expires_at TIMESTAMP NOT NULL, ip_address VARCHAR(45) NULL, user_agent TEXT NULL, is_active BOOLEAN NOT NULL);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_sessions');
        $this->addSql('DROP TABLE IF EXISTS audit_logs');
        $this->addSql('DROP TABLE IF EXISTS users');
    }
}


