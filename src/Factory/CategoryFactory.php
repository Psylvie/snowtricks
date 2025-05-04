<?php

namespace App\Factory;

use App\Entity\Category;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Category>
 */
final class CategoryFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return Category::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->unique()->randomElement(self::getCategoryNames()),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public static function getCategoryNames(): array
    {
        return [
            'Grabs',
            'Spins',
            'Flips',
            'Slides',
            'Rotations',
            'Inverts',
            'Butters',
            'Jumps',
            'Tweaks',
            'Rails',
        ];
    }
}
