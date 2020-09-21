<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200921160156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE test (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                test_configuration_id INTEGER NOT NULL,
                source CLOB NOT NULL,
                target CLOB NOT NULL,
                step_count INTEGER NOT NULL
            )
       ');
        $this->addSql('CREATE INDEX IDX_D87F7E0C753B56F7 ON test (test_configuration_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE test');
    }
}
