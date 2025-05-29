<?php

declare(strict_types=1);

$header = trim('This code is licensed under the MIT License.'.substr(file_get_contents('LICENSE'), strlen('The MIT License')));

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        'header_comment' => ['comment_type' => 'PHPDoc', 'header' => $header, 'separate' => 'bottom', 'location' => 'after_open'],

        '@Symfony' => true,
        'phpdoc_to_comment' => false,
        'native_function_invocation' => ['include' => ['@internal'], 'scope' => 'namespaced'],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'blank_line_between_import_groups' => false,
        'strict_comparison' => true,
        'php_unit_method_casing' => false,
        'blank_line_after_opening_tag' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
    )
;

return $config;
