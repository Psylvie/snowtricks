<?php

namespace App\Factory;

use App\Entity\Comment;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Comment>
 */
final class CommentFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return Comment::class;
    }

    protected function defaults(): array|callable
    {
        $createdAt = self::faker()->dateTimeThisYear();

        return [
            'createdAt' => $createdAt,
            'updatedAt' => self::faker()->dateTimeBetween($createdAt, 'now'),
            'Content' => self::faker()->text(),
            'trick' => self::faker()->randomElement(TrickFactory::repository()->findAll()),
            'user' => self::faker()->randomElement(UserFactory::repository()->findAll()),
        ];
    }


    protected function initialize(): static
    {
        return $this;
    }
}
