<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200924092522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create TestState entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE test_state (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A84D74F05E237E06 ON test_state (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE test_state');
    }
}
