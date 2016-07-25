<?php

return [
    'DRIVER_CONFIG_LIST' => [
        [
            'READ_LIMIT_ON'     => true,
            'WRITE_LIMIT_ON'    => true,
            'READABLE_SCOPE'    => PATH_BASE,
            'WRITABLE_SCOPE'    => [PATH_RUNTIME,PATH_PUBLIC],

            'READOUT_MAX_SIZE'          => 2097152,//2M限制,对于文本文件已经足够
            'OS_ECNODE'         => 'GB2312', // 文件系统编码格式,如果是英文环境下可能是UTF-8,GBK,GB2312以外的编码格式
            'READOUT_ENCODE'    => 'UTF-8', // 读出时转化的成的编码格式
            'WRITEIN_ENCODE'    => 'UTF-8', // 写入时转化的编码格式
        ]
    ],
];