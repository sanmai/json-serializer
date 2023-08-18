<?php

declare(strict_types=1);

$header = trim('This code is licensed under the MIT License.'.substr(file_get_contents('LICENSE'), strlen('The MIT License')));

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        'header_comment' => ['comment_type' => 'PHPDoc', 'header' => $header, 'separate' => 'bottom', 'location' => 'after_open'],

        '@Symfony:risky' => true,
        '@PHP71Migration:risky' => true,
        '@PHPUnit75Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        'ordered_class_elements' => false,
        'phpdoc_to_comment' => false,
        'strict_comparison' => true,
        'comment_to_phpdoc' => true,
        'native_function_invocation' => ['include' => ['@internal'], 'scope' => 'namespaced'],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'php_unit_test_case_static_method_calls' => false,
        'yoda_style' => false,
        'random_api_migration' => false,
        'blank_line_between_import_groups' => false,
        'blank_line_before_statement' => ['statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try']],
        'no_unset_on_property' => false,
        'multiline_whitespace_before_semicolons' => false,
        'php_unit_method_casing' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
    )
;

return $config;
