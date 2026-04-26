<?php

return [
    'phpstan_type_match' => [
        'list<string>',
        'value' => 'non-empty-string',
        'callback' => 'pure-callable(int):literal-string',
        'class' => 'class-string<stdClass>',
        'items' => 'iterable<int,positive-int>',
        'map' => 'array{foo:int,bar?:string}',
        'payload' => 'object{foo:int}',
        'mask' => 'int-mask<1, 2, 4>',
        'stream' => 'resource (closed)',
    ],
];
