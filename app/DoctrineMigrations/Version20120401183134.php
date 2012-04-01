<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120401183134 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE club_booking_plan CHANGE end_date end_time TIME NOT NULL");
        $this->addSql("ALTER TABLE club_booking_plan CHANGE first_date first_time TIME NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE club_booking_plan CHANGE end_time end_date TIME NOT NULL");
        $this->addSql("ALTER TABLE club_booking_plan CHANGE first_time first_date TIME NOT NULL");

    }
}
