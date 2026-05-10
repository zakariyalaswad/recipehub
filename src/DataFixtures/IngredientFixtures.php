<?php

namespace App\DataFixtures;

use App\Entity\Ingredient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use App\Entity\Recette;

class IngredientFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < 20; $i++) {

            $recette = $this->getReference('recette_'.$i, Recette::class);

            for ($j = 0; $j < rand(3, 7); $j++) {

                $ingredient = new Ingredient();
                $ingredient->setNom($faker->word());
                $ingredient->setQuantite(rand(50, 500).'g');

                $ingredient->setRecette($recette);

                $manager->persist($ingredient);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RecetteFixtures::class,
        ];
    }
}   
