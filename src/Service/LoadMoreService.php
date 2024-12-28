<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class LoadMoreService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Environment $twig)
    {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function loadItems(
        string $entityClass,
        int $offset,
        int $limit,
        string $template,
        string $datakey,
        array $criteria = []
    ): array {
        $repository = $this->em->getRepository($entityClass);

        $items = $repository->findBy($criteria, ['createdAt' => 'DESC', 'id' => 'ASC'], $limit, $offset);

        return [
            'html' => $this->twig->render($template, [$datakey => $items]),
            'hasMore' => count($items) === $limit,
        ];
    }
}
