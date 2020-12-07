<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200925113340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Job entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE job (
                id INT NOT NULL, 
                label VARCHAR(32) NOT NULL, 
                callback_url VARCHAR(255) NOT NULL, 
                maximum_duration_in_seconds INT NOT NULL,
                start_date_time TIMESTAMP(0) WITHOUT TIME ZONE,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('COMMENT ON COLUMN job.start_date_time IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBD8E0F8EA750E8 ON job (label)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE job');
    }
}
