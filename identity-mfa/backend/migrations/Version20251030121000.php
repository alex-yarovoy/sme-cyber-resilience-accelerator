<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251030121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable pgcrypto extension for gen_random_uuid()';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pgcrypto');
    }

    public function down(Schema $schema): void
    {
        // no-op
    }
}



