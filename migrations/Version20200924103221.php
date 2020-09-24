<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200924103221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Test::state';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE test ADD state_id INT NOT NULL');
        $this->addSql('
            ALTER TABLE test ADD CONSTRAINT FK_D87F7E0C5D83CC1 
                FOREIGN KEY (state_id) 
                REFERENCES test_state (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('CREATE INDEX IDX_D87F7E0C5D83CC1 ON test (state_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE test DROP CONSTRAINT FK_D87F7E0C5D83CC1');
        $this->addSql('DROP INDEX IDX_D87F7E0C5D83CC1');
        $this->addSql('ALTER TABLE test DROP state_id');
    }
}
