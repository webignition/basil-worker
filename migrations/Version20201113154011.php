<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201113154011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE callback_entity (
                id SERIAL NOT NULL, 
                state VARCHAR(255) NOT NULL,
                retry_count SMALLINT NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                payload JSON NOT NULL, 
                PRIMARY KEY(id)
            )
       ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE callback');
    }
}
