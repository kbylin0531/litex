<?php
return [
    PRIOR_INDEX => 0,
    DRIVER_CLASS_LIST => [
        'PLite\\Core\\Cache\\File',
        'PLite\\Core\\Cache\\Memcache',
    ],
    DRIVER_CONFIG_LIST => [
        [
            //from thinkphp ,match case
            'expire'        => 0,
            'cache_subdir'  => false,
            'path_level'    => 1,
            'prefix'        => '',
            'length'        => 0,
            'path'          => PATH_RUNTIME.'/file_cache/',
            'data_compress' => false,
        ],
        [
            'host'      => 'localhost',
            'port'      => 11211,
            'expire'    => 0,
            'prefix'    => '',
            'timeout'   => 1000, // 超时时间（单位：毫秒）
            'persistent'=> true,
            'length'    => 0,
        ],
    ],
];
