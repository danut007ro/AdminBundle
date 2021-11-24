<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('src/Maker/Resources')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'php_unit_strict' => false,
        'phpdoc_to_comment' => false,
        'declare_strict_types' => true,
        'ordered_interfaces' => true,
    ])
    ->setFinder($finder)
;
