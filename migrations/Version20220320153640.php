<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220320153640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_35A67D4597E6F2F6');
        $this->addSql('CREATE TEMPORARY TABLE __temp__assigned_bonus_goals AS SELECT id, bonus_goal_id, username, completion_percentage, comment, period, progress, acceptance_path FROM assigned_bonus_goals');
        $this->addSql('DROP TABLE assigned_bonus_goals');
        $this->addSql('CREATE TABLE assigned_bonus_goals (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, bonus_goal_id INTEGER NOT NULL, username VARCHAR(255) NOT NULL, completion_percentage CLOB DEFAULT NULL --(DC2Type:array)
        , comment CLOB DEFAULT NULL --(DC2Type:array)
        , period DATE NOT NULL, progress VARCHAR(255) NOT NULL, acceptance_path CLOB DEFAULT NULL --(DC2Type:array)
        , CONSTRAINT FK_35A67D4597E6F2F6 FOREIGN KEY (bonus_goal_id) REFERENCES bonus_goals (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO assigned_bonus_goals (id, bonus_goal_id, username, completion_percentage, comment, period, progress, acceptance_path) SELECT id, bonus_goal_id, username, completion_percentage, comment, period, progress, acceptance_path FROM __temp__assigned_bonus_goals');
        $this->addSql('DROP TABLE __temp__assigned_bonus_goals');
        $this->addSql('CREATE INDEX IDX_35A67D4597E6F2F6 ON assigned_bonus_goals (bonus_goal_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_35A67D4597E6F2F6');
        $this->addSql('CREATE TEMPORARY TABLE __temp__assigned_bonus_goals AS SELECT id, bonus_goal_id, username, completion_percentage, comment, period, progress, acceptance_path FROM assigned_bonus_goals');
        $this->addSql('DROP TABLE assigned_bonus_goals');
        $this->addSql('CREATE TABLE assigned_bonus_goals (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, bonus_goal_id INTEGER NOT NULL, username VARCHAR(255) NOT NULL, completion_percentage CLOB DEFAULT NULL --(DC2Type:array)
        , comment CLOB DEFAULT NULL --(DC2Type:array)
        , period DATE NOT NULL, progress VARCHAR(255) NOT NULL, acceptance_path CLOB DEFAULT NULL --(DC2Type:array)
        )');
        $this->addSql('INSERT INTO assigned_bonus_goals (id, bonus_goal_id, username, completion_percentage, comment, period, progress, acceptance_path) SELECT id, bonus_goal_id, username, completion_percentage, comment, period, progress, acceptance_path FROM __temp__assigned_bonus_goals');
        $this->addSql('DROP TABLE __temp__assigned_bonus_goals');
        $this->addSql('CREATE INDEX IDX_35A67D4597E6F2F6 ON assigned_bonus_goals (bonus_goal_id)');
    }
}
