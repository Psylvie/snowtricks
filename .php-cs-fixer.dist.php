<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'vendor']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
    ])
    ->setFinder($finder);
