<?php

declare(strict_types=1);

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface; // Pour accéder au conteneur
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // Pour hasher le mot de passe


/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231027100000 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return 'Add default admin user';
    }

    public function up(Schema $schema): void
    {
        // this up() migrates from schema to version 20231027100000
        // You can only use raw SQL in this method
        $this->addSql('INSERT INTO user (email, roles, password, is_verified, nom, prenom, telephone, adresse) VALUES (\'admin@example.com\', \'["ROLE_ADMIN"]\', \'$2y$10$admin123\', 1, \'AdminNom\', \'AdminPrenom\', \'1234567890\', \'AdminAdresse\')'); // Insère l'admin avec un mot de passe HASHÉ
    }

    public function down(Schema $schema): void
    {
        // this down() reverts migration to original state
        $this->addSql('DELETE FROM user WHERE email = \'admin@example.com\'');
    }

    public function postUp(Schema $schema): void
    {
        parent::postUp($schema);
        // Get the UserPasswordHasherInterface from the container
        $passwordHasher = $this->container->get('security.user_password_hasher');
        // Fetch the EntityManager
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        // Find the Admin User
        $adminUser = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if ($adminUser) {
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($adminUser, 'admin123'); // 'admin' est le mot de passe par défaut
            $adminUser->setPassword($hashedPassword);
            $entityManager->persist($adminUser);
            $entityManager->flush();
        }

    }
}

