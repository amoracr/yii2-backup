<?php

$supported_versions = [
  '5.1', '5.2', '5.3', '5.4', '5.5', '5.6',
  '7.0', '7.1', '7.2', '7.3', '7.4',
  '8.0',
];
$target_versions = implode(',', $supported_versions);
return [
    "target_php_version" => $target_versions,
    'directory_list' => [
        'src',
        'vendor'
    ],
    "exclude_analysis_directory_list" => [
        'vendor'
    ],
    'plugins' => [
        'AlwaysReturnPlugin',
        'DuplicateArrayKeyPlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'DuplicateConstantPlugin',
        'DuplicateExpressionPlugin',
        'EmptyStatementListPlugin',
        'HasPHPDocPlugin',
        'InvalidVariableIssetPlugin',
        'LoopVariableReusePlugin',
        'NonBoolBranchPlugin',
        'NonBoolInLogicalArithPlugin',
        'NumericalComparisonPlugin',
        'PHPDocInWrongCommentPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'SleepCheckerPlugin',
        'UnreachableCodePlugin',
        'UnsafeCodePlugin',
        'UseReturnValuePlugin',
    ],
];
