<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->exclude('tests/Fixtures')
    ->exclude('src/Kernel.php')
    ->exclude('tests/bootstrap.php')
;


$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHPUnit75Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PSR12' => true,
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
        'header_comment' => ['header' => $header],
        'list_syntax' => ['syntax' => 'short'],
        'class_definition' => ['multi_line_extends_each_single_line' => true],
        'yoda_style' => false,
    ])
    ->setFinder($finder)
;

return $config;
