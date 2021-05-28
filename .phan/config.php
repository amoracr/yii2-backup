<?php

return [
    "target_php_version" => '5.6,7.0,7.1,7.2,7.3,7.4,8.0',
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
