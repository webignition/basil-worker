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
                test_configuration_id INT NOT NULL,
                state VARCHAR(255) NOT NULL,
                source TEXT NOT NULL,
                target TEXT NOT NULL,
                step_count INT NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_D87F7E0C753B56F7 ON test (test_configuration_id)');
        $this->addSql('
            ALTER TABLE test 
                ADD CONSTRAINT FK_D87F7E0C753B56F7
                FOREIGN KEY (test_configuration_id)
                REFERENCES test_configuration (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE test');
    }
}
