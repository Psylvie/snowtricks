<?php

namespace App\DataFixtures;

use App\Factory\CategoryFactory;
use App\Factory\CommentFactory;
use App\Factory\PictureFactory;
use App\Factory\TrickFactory;
use App\Factory\UserFactory;
use App\Factory\VideoFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createMany(25);
        CategoryFactory::createMany(5);
        TrickFactory::createMany(10);
        PictureFactory::createMany(25);
        VideoFactory::createMany(25);
        CommentFactory::createMany(25);

        $manager->flush();
    }
}
