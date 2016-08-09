<?php
/**
 * Created by linzhv@outlook.com.
 * User: linzh
 * Date: 2016/6/27
 * Time: 17:22
 */
return [
    'AUTO_ESCAPE_ON'    => true,
    'PRIOR_INDEX' => 0,
    'DRIVER_CLASS_LIST' => [
        'PLite\\Core\\Dao\\MySQL',
    ],
    'DRIVER_CONFIG_LIST' => [
        [
            'dbname'    => 'litex',//选择的数据库
            'username'  => 'lin',
            'password'  => '123456',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'UTF8',
            'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
            'options'   => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
            ],
        ],
    ],
];