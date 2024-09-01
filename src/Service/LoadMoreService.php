<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class LoadMoreService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Environment $twig)
    {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function loadItems(string $entityClass, int $offset, int $limit, string $template, string $datakey): array
    {
        $repository = $this->em->getRepository($entityClass);

        $items = $repository->findBy([], ['createdAt' => 'DESC', 'id' => 'ASC'], $limit, $offset);

        return [
            'html' => $this->twig->render($template, [$datakey => $items]),
            'hasMore' => count($items) === $limit,
        ];
    }
}
