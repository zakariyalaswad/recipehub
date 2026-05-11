<?php

namespace App\DataFixtures;

use App\Entity\Recette;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use App\Entity\CategorieRecette;
use App\Entity\TagRecette;
use App\Entity\User;

class RecetteFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $categories = $manager->getRepository(\App\Entity\CategorieRecette::class)->findAll();

        $difficultes = ['facile', 'moyen', 'difficile'];

        for ($i = 0; $i < 20; $i++) {

            $recette = new Recette();

            $recette->setTitre($faker->sentence(3));
            $recette->setDescription($faker->text(100));
            $recette->setInstructions($faker->text(200));
            $recette->setTempsPreparation(rand(10, 60));
            $recette->setTempsCuisson(rand(10, 120));
            $recette->setDifficulte($difficultes[array_rand($difficultes)]);
            $recette->setNbPersonnes(rand(1, 6));
            $recette->setDateCreation(new \DateTime());
            $recette->setPubliee(true);

            $recette->setAuteur($this->getReference('user_chef', User::class));
            $recette->setCategorie($faker->randomElement($categories));

            // TAGS
            $tagNames = ['Végétarien','Vegan','Sans Gluten','Rapide','Familial','Bio'];
            $nbTags = rand(1, 3);

            for ($j = 0; $j < $nbTags; $j++) {
                $tagName = $faker->randomElement($tagNames);
                $recette->addTag($this->getReference('tag_'.$tagName,TagRecette::class));
            }

            $this->addReference('recette_'.$i, $recette);

            $manager->persist($recette);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategorieFixtures::class,
            TagRecetteFixtures::class,
        ];
    }
}
