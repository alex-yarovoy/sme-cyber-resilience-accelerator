<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setEmail('admin@example.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true)
            ->setMfaEnabled(false);
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin#123456'));
        $manager->persist($admin);

        $user = (new User())
            ->setEmail('user@example.com')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);
        $user->setPassword($this->hasher->hashPassword($user, 'User#12345678'));
        $manager->persist($user);

        $manager->flush();
    }
}


