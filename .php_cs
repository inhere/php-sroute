<?php

$header = <<<'EOF'

@license  https://github.com/inhere/php-srouter/blob/master/LICENSE
EOF;

return PhpCsFixer\Config::create()->setRiskyAllowed(true)->setRules([
    '@PSR2' => true,
    'array_syntax' => [
        'syntax' => 'short'
    ],
    'class_attributes_separation' => true,
    'declare_strict_types' => true,
    'encoding' => true, // MUST use only UTF-8 without BOM
    'global_namespace_import' => true,
//     'header_comment' => [
//       'comment_type' => 'PHPDoc',
//       'header'       => $header,
//       'separate'     => 'bottom'
//     ],
    'no_unused_imports' => true,
    'single_quote' => true,
    'standardize_not_equals' => true,
  ])->setFinder(PhpCsFixer\Finder::create()
    // ->exclude('test')
                                 ->exclude('docs')->exclude('vendor')->in(__DIR__))->setUsingCache(false);
