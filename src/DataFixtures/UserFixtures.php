<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Admin
        $admin = new User();
        $admin->setEmail('admin@recipehub.com');
        $admin->setPseudo('AdminMaster');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Chef
        $chef = new User();
        $chef->setEmail('chef@recipehub.com');
        $chef->setPseudo('ChefMaster');
        $chef->setRoles(['ROLE_CUISINIER']);
        $chef->setPassword($this->hasher->hashPassword($chef, 'chef123'));
        $manager->persist($chef);
        $this->addReference('user_chef', $chef);

        // Users fake
        for ($i = 0; $i < 5; $i++) {
            $u = new User();
            $u->setEmail($faker->email());
            $u->setPseudo($faker->userName());
            $u->setRoles(['ROLE_USER']);
            $u->setPassword($this->hasher->hashPassword($u, 'test123'));
            $manager->persist($u);
        }

        $manager->flush();
    }
}
