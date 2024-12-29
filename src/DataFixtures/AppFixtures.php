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
        CategoryFactory::createMany(10);
        TrickFactory::createMany(10);
        PictureFactory::createMany(30);
        VideoFactory::createMany(30);
        CommentFactory::createMany(30);

        $manager->flush();
    }
}
