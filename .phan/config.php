<?php

return [
    "target_php_version" => '7.0,7.1,7.2,7.3,7.4,8.0',
    'directory_list' => [
        'src',
        'vendor'
    ],
    "exclude_analysis_directory_list" => [
        'vendor'
    ],
    'plugins' => [
        'AlwaysReturnPlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'DuplicateExpressionPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'SleepCheckerPlugin',
        'UnreachableCodePlugin',
        'UseReturnValuePlugin',
        'EmptyStatementListPlugin',
        'LoopVariableReusePlugin',
    ],
];
