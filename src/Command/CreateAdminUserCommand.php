<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Creates a default admin user',
)]
class CreateAdminUser extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private UserPasswordHasherInterface $userPasswordHasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command creates a default admin user with email admin@example.com and password "admin"')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if the admin user already exists
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if ($existingAdmin) {
            $io->warning('Admin user already exists.');
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsVerified(true);
        $user->setNom('AdminNom');       // Set default name
        $user->setPrenom('AdminPrenom');     // Set default first name
        $user->setTelephone('123456789'); // Set default phone
        $user->setAdresse('Admin Address');    // Set default address


        // Hash the password
        $hashedPassword = $this->userPasswordHasher->hashPassword($user, 'admin123');  // 'admin' est le mot de passe
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Admin user created successfully.');

        return Command::SUCCESS;
    }
}