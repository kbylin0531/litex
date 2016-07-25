<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/24/16
 * Time: 10:03 PM
 */

namespace Application\Admin\Controller;


use Application\System\Model\ArticleModel;
use PLite\Core\Dao;
use PLite\Library\Response;

class Article extends Admin {

    public function index(){
        $model = new ArticleModel();
        $list = $model->lists('id,title,summary,author,ctime,etime,category');
        \PLite\dumpout($list);
        $this->assign('datalist',json_encode($list));
        $this->show();
    }

    public function add($title='',$summary='',$content='',$author=''){
        $error = '';
        $info = '';
        if(IS_METHOD_POST){
            $model = new ArticleModel();
            $result = $model->add([
                'title' => $title,
                'summary'   => $summary,
                'content'   => $content,
                'author'    => $author,
            ]);
            \PLite\dumpout($result,$model->error());
            if(false === $result){
                $error = $model->error();
            }elseif(!$result){
                $error = 'No data insert!';
            }else{
                $info = 'Success to add!';
            }
        }
        $this->assign('error',$error);
        $this->assign('info',$info);
        $this->show();
    }

}