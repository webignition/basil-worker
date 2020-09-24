<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200924105420 extends AbstractMigration
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
                state_id INT NOT NULL, 
                label VARCHAR(32) NOT NULL, 
                callback_url VARCHAR(255) NOT NULL, 
                sources TEXT NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBD8E0F8EA750E8 ON job (label)');
        $this->addSql('CREATE INDEX IDX_FBD8E0F85D83CC1 ON job (state_id)');
        $this->addSql('COMMENT ON COLUMN job.sources IS \'(DC2Type:simple_array)\'');
        $this->addSql('
            ALTER TABLE job 
            ADD CONSTRAINT FK_FBD8E0F85D83CC1 
                FOREIGN KEY (state_id) 
                REFERENCES job_state (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE job');
    }
}
