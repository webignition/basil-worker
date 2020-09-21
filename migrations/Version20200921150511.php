<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200921150511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE test_configuration (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                browser VARCHAR(255) NOT NULL,
                url VARCHAR(255) NOT NULL
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX browser_url_idx ON test_configuration (browser, url)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE test_configuration');
    }
}
