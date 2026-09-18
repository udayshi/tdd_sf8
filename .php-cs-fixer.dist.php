<?php

$finder = (new PhpCsFixer\Finder())
    ->in('src')
    ->in('tests')
    ->in('config')
    ->exclude(['migrations', 'var'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,  // ← Add this line
        'ordered_class_elements' => true,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache')
    ;
