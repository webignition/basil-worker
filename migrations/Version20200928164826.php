<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200928164826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Test::position';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE test ADD position INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D87F7E0C462CE4F5 ON test (position)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D87F7E0C462CE4F5');
        $this->addSql('ALTER TABLE test DROP position');
    }
}
