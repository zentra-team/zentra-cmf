<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notPath('vendor')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'                             => true,

        // Imports
        'no_unused_imports'                  => true,
        'ordered_imports'                    => ['sort_algorithm' => 'alpha'],
        'global_namespace_import'            => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],

        // Blank lines
        'no_extra_blank_lines'               => [
            'tokens' => [
                'attribute', 'case', 'continue', 'curly_brace_block', 'default',
                'extra', 'parenthesis_brace_block', 'square_brace_block',
                'switch', 'throw', 'use',
            ],
        ],
        'blank_line_after_namespace'         => true,
        'blank_line_after_opening_tag'       => true,
        'blank_line_before_statement'        => [
            'statements' => ['return', 'throw', 'try', 'yield'],
        ],
        'no_blank_lines_after_phpdoc'        => true,

        // Strings
        'single_quote'                       => ['strings_containing_single_quote_chars' => false],

        // Whitespace
        'no_trailing_whitespace'             => true,
        'no_trailing_whitespace_in_comment'  => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line'        => true,
        'whitespace_after_comma_in_array'    => ['ensure_single_space' => true],

        // Arrays
        'array_syntax'                       => ['syntax' => 'short'],
        'trailing_comma_in_multiline'        => ['elements' => ['arrays', 'parameters', 'arguments']],
        'normalize_index_brace'              => true,

        // Operators & casting
        'cast_spaces'                        => ['space' => 'single'],
        'concat_space'                       => ['spacing' => 'one'],
        'binary_operator_spaces'             => [
            'default'   => 'single_space',
            'operators' => ['=>' => 'align_single_space_minimal'],
        ],
        'unary_operator_spaces'              => true,

        // Functions & methods
        'function_typehint_space'            => true,
        'return_type_declaration'            => ['space_before' => 'none'],
        'nullable_type_declaration_for_default_null_value' => true,
        'no_spaces_after_function_name'      => true,
        'method_argument_space'              => [
            'on_multiline'                     => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],

        // Control structures
        'no_superfluous_elseif'              => true,
        'no_useless_else'                    => true,
        'simplified_if_return'               => true,

        // PHP tags & namespace
        'no_closing_tag'                     => true,
        'linebreak_after_opening_tag'        => true,
        'declare_strict_types'               => false,

        // Comments
        'single_line_comment_style'          => ['comment_types' => ['hash']],
        'multiline_comment_opening_closing'  => true,

        // Misc
        'no_empty_statement'                 => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'semicolon_after_instruction'        => true,
        'space_after_semicolon'              => true,
        'object_operator_without_whitespace' => true,
        'standardize_not_equals'             => true,
    ])
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setFinder($finder);
