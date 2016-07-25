<?php
/**
 * Created by linzhv@outlook.com.
 * User: linzh
 * Date: 2016/6/22
 * Time: 17:19
 */

namespace PLite\Library;

class Gateway {
    /**
     * 包含模式和例外模式
     */
    const MODE_INCLUDE = 0;
    const MODE_EXCEPTION = 1;

    /**
     * @var Gateway
     */
    private static $_instance = null;
    /**
     * @var null
     */
    private $_mode = null;

    public static function getInstance(){
        if(null === self::$_instance){
            self::$_instance = new Gateway();
        }
        return self::$_instance;
    }

    private function __construct(){}

    /**
     * 检测IP是否存在于队列中
     * @return bool
     */
    public function check(){}

    /**
     * 添加一个IP到队列
     * @return void
     */
    public function add(){}

    /**
     * 从IP队列中删除一个名单
     * @return void
     */
    public function remove(){}

    /**
     * 获取IP列表
     * @return array
     */
    public function getlist(){}

    /**
     * 设定模式
     * @param $mode
     */
    public function setMode($mode){
        $this->_mode = $mode;
    }

}