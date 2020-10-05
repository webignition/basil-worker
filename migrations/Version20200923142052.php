<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200923142052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Test entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE test (
                id SERIAL NOT NULL,
                state VARCHAR(255) NOT NULL,
                source TEXT NOT NULL,
                position INT NOT NULL,
                manifest_path VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D87F7E0C462CE4F5 ON test (position)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE test');
    }
}
