<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\Finder\Finder;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    private const SOURCE_PROFILE_IMAGE_DIRECTORY = __DIR__.'/../../public/imageProfile';
    private const DESTINATION_PROFILE_IMAGE_DIRECTORY = __DIR__.'/../../public/uploads/profileImages';
    private UserPasswordHasherInterface $hasher;

    public function __construct(
        UserPasswordHasherInterface $hasher,
    ) {
        $this->hasher = $hasher;
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        $createdAt = self::faker()->dateTimeThisYear();

        return [
            'createdAt' => $createdAt,
            'updatedAt' => self::faker()->dateTimeBetween($createdAt, 'now'),
            'email' => self::faker()->unique()->email(),
            'username' => self::faker()->unique()->userName(),
            'password' => 'password123',
            'verified' => false,
            'profileImage' => self::getRandomProfileImageFilename(),
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            $user->setPassword(
                $this->hasher->hashPassword($user, $user->getPassword())
            );
            $this->copyProfileImage(self::DESTINATION_PROFILE_IMAGE_DIRECTORY, $user->getProfileImage());
        });
    }

    private function copyProfileImage(string $directory, string $filename): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $sourceImagePath = self::SOURCE_PROFILE_IMAGE_DIRECTORY.'/'.$filename;
        $destinationImagePath = $directory.'/'.$filename;

        if (file_exists($sourceImagePath)) {
            copy($sourceImagePath, $destinationImagePath);
        }
    }

    private static function getRandomProfileImageFilename(): string
    {
        $finder = new Finder();
        $finder->files()->in(self::SOURCE_PROFILE_IMAGE_DIRECTORY)->name('/\.(jpg|jpeg|png|gif)$/i');

        $imageFiles = iterator_to_array($finder);

        if (empty($imageFiles)) {
            return 'defaultProfile.jpg';
        }
        $randomImage = $imageFiles[array_rand($imageFiles)];

        return $randomImage->getFilename();
    }
}
