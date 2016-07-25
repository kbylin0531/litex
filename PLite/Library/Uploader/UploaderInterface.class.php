<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/11/16
 * Time: 11:02 AM
 */

namespace PLite\Library\Uploader;


interface UploaderInterface {


    /**
     * 检测上传根目录
     * @param string $rootpath   根目录
     * @return boolean true-检测通过，false-检测失败
     */
    public function checkRootPath($rootpath);

    /**
     * 检测上传目录
     * @param  string $savepath 上传目录
     * @return boolean          检测结果，true-通过，false-失败
     */
    public function checkSavePath($savepath);

    /**
     * 保存指定文件
     * @param  array   $file    保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save($file, $replace=true);

    /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError();


}