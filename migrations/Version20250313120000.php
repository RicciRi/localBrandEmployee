<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250313120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создание таблицы сотрудников';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE employees (
            id INT AUTO_INCREMENT NOT NULL,
            emp_id VARCHAR(20) NOT NULL,
            name_prefix VARCHAR(10) DEFAULT NULL,
            first_name VARCHAR(50) NOT NULL,
            middle_initial VARCHAR(1) DEFAULT NULL,
            last_name VARCHAR(50) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            email VARCHAR(100) NOT NULL,
            date_of_birth DATE NOT NULL,
            time_of_birth TIME NOT NULL,
            age_in_years DOUBLE PRECISION NOT NULL,
            date_of_joining DATE NOT NULL,
            age_in_company DOUBLE PRECISION NOT NULL,
            phone_no VARCHAR(20) NOT NULL,
            place_name VARCHAR(100) DEFAULT NULL,
            county VARCHAR(100) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            zip VARCHAR(20) DEFAULT NULL,
            region VARCHAR(100) DEFAULT NULL,
            user_name VARCHAR(100) NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_EMP_ID (emp_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE employees');
    }
}