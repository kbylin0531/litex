<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/7/16
 * Time: 11:25 AM
 */
return [
    'PRIOR_INDEX' => 1,

    'DRIVER_CONFIG_LIST' => [
        1 => [
            'CACHE_ON'         => false,//缓存是否开启
            'CACHE_EXPIRE'     => 10,//缓存时间，0便是永久缓存,仅以设置为30
            'CACHE_UPDATE_CHECK'=> true,//是否检查模板文件是否发生了修改，如果发生修改将更新缓存文件（实现：检测模板文件的时间是否大于缓存文件的修改时间）
        ],
    ],
];