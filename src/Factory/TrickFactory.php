<?php

namespace App\Factory;

use App\Entity\Trick;
use Symfony\Component\String\Slugger\SluggerInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Trick>
 */
final class TrickFactory extends PersistentProxyObjectFactory
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Trick::class;
    }

    protected function defaults(): array|callable
    {
        $createdAt = self::faker()->dateTimeThisYear();
        $name = self::faker()->unique()->randomElement(self::getTrickNames());

        return [
            'createdAt' => $createdAt,
            'updatedAt' => self::faker()->dateTimeBetween($createdAt, 'now'),
            'category' => self::faker()->randomElement(CategoryFactory::repository()->findAll()),
            'description' => self::faker()->text(200),
            'name' => $name,
            'slug' => $this->generateSlug($name),
            'user' => self::faker()->randomElement(UserFactory::repository()->findAll()),
        ];
    }

    protected function initialize(): static
    {
        return $this
        ;
    }

    public static function getTrickNames(): array
    {
        return [
            'Ollie',
            'Nollie',
            'Frontside 180',
            'Backside 180',
            'Frontside 360',
            'Backside 360',
            'Mute Grab',
            'Indy Grab',
            'Stalefish Grab',
            'Tail Grab',
            'Nose Grab',
            'Method Air',
            'Bloody Dracula',
            'Tamedog',
            'Wildcat',
            '50-50 Grind',
            'Boardslide',
            'Lipslide',
            'Bluntslide',
            'Noseslide',
            'Tail Press',
            'Nose Press',
            'Butter 180',
            'Switch Backside 360',
            'Cab 540',
        ];
    }

    private function generateSlug(string $name): string
    {
        return $this->slugger ? $this->slugger->slug($name)->lower()->toString() : u($name)->slug()->lower()->toString();
    }
}
