<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250318135341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stripe_payment (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, subscription_id_id INT NOT NULL, amont DOUBLE PRECISION NOT NULL, currency VARCHAR(10) NOT NULL, status VARCHAR(20) NOT NULL, stripe_payment_intent VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_42EFB5F79D86650F (user_id_id), INDEX IDX_42EFB5F7857C9F24 (subscription_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stripe_payment ADD CONSTRAINT FK_42EFB5F79D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE stripe_payment ADD CONSTRAINT FK_42EFB5F7857C9F24 FOREIGN KEY (subscription_id_id) REFERENCES stripe_subscription (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stripe_payment DROP FOREIGN KEY FK_42EFB5F79D86650F');
        $this->addSql('ALTER TABLE stripe_payment DROP FOREIGN KEY FK_42EFB5F7857C9F24');
        $this->addSql('DROP TABLE stripe_payment');
    }
}
