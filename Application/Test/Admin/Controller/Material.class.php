<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/22/16
 * Time: 2:12 PM
 */

namespace Application\Admin\Controller;
use Application\Admin\Model\MaterialImageModel;
use Application\System\Library\Service\MaterialService;
use Application\System\Library\Service\WechatService;
use PLite\Core\Storage;
use PLite\Library\Logger;
use PLite\Library\Model;
use PLite\Library\Response;
use PLite\Library\Uploader;
use PLite\Util\Helper\Network;

class Material extends Admin {

    public function textAccess(){
//        $service = new MaterialService(3);
//        $service->addImage(PATH_PUBLIC.'/img/add.png');
//
        $service = new WechatService(3);
        $token = $service->getAccessToken(true);
        \PLite\dumpout($token,date('Y-m-d H:i:s',$token[1]));
//        $service = new MaterialService(3);
//        $result = $service->getImageList();
//        \PLite\dumpout($result,$service->getError());

//        $result = Network::download('http://mmbiz.qpic.cn/mmbiz/GlYed54j9uxSMzK5uIL5LY4FroI13AZWDdWsCGTHlFSKOIHeUpia20QqySLeaJQaZp8TrzTUfwCzrROBVkIfMVA/0?wx_fmt=jpeg',
//            '/home/linzhv/webroot/plite/Public/upload//wechat/img/57937a2744050.jpg');
        $result = Network::download('http://mmbiz.qpic.cn/mmbiz/GlYed54j9uxSMzK5uIL5LY4FroI13AZWDdWsCGTHlFSKOIHeUpia20QqySLeaJQaZp8TrzTUfwCzrROBVkIfMVA/0?wx_fmt=jpeg',
            'a23.jpg');
        \PLite\dumpout($result);

    }

    public function index($aid){
        $this->assign('aid',$aid);
        $this->show();
    }

    public function imgList($aid,$like='%'){
        $model = MaterialImageModel::getInstance();
        $image_list = $model->get([
            'title'         => [$like,Model::OPERATOR_LIKE],
            'description'    => [$like,Model::OPERATOR_LIKE],
        ]);
        $this->assign('aid',$aid);
        $this->assign('image_list',$image_list);
        $this->show();
    }

    public function imgSync($aid){
        $service = new MaterialService($aid);
        $result = $service->getImageList();
        if(false === $result){
            Response::failed("failed to get image list from wechat server!");
        }else{
            $rst = [
                'f' => 0,//failure
                'sa' => 0,//success add
                'su' => 0,//success update
                'e' => '',//error
            ];
            \PLite\dumpout($result);
            if(!empty($result['item'] )){
                $model = new MaterialImageModel();
                foreach ($result['item'] as $item){
                    $media_id = $item['media_id'];
                    $filepath = $item['name'];
                    $url = $item['url'];

                    $count = $model->hasMedia($media_id);
                    Response::failed($model->error());
                    if($count){
                        //update
                        $path = Network::download($url,$filepath);
                        $result = $model->updateMedia($media_id,[
                            'path'=>$path,
                        ]);
                        if(false === $result){
                            $rst['f']++;
                            $rst['e'] .= $model->error().'\n';
                        }else{
                            $rst['su']++;
                        }
                    } else{
                        //create
                        $path = Network::download($url,$filepath);
                        $id = $model->add([
                            'path'  => $path,
                            'url'   => $url,
                            'media_id'  => $media_id,
                            'aid'   => $aid,
                        ]);
                        if(false === $id){
                            $rst['f']++;
                            $rst['e'] .= $model->error().'\n';
                        }else{
                            $rst['sa']++;
                        }
                    }
                }
            }
            $msg = "Success to add {$rst['sa']} and update {$rst['su']} media,{$rst['f']} failed!";
            $rst['e'] and Logger::write($rst['e']);
            Response::success($msg);
        }
    }


    public function imgUpload($aid,$title='',$description=''){
        if(IS_METHOD_POST){
            $uploader = new Uploader();
            $result = $uploader->upload([
                'savePath'  => '/wechat/img/',
                'mimes'     => ['image/jpeg','image/pjpeg','image/png'],
            ]);
            $pic = $result['picture'];
            $path = $pic['savepath'].$pic['savename'];

            $service = new MaterialService($aid);

            $result = $service->addImage($path);

            if(false === $result){
                Logger::write($result);
                $error = $service->getError();
            }else{
                \PLite\dumpout($result);
                $model = MaterialImageModel::getInstance();
                $pos = strpos($path,'Public/');
                $model->add([
                    'title'         => $title,
                    'description'   => $description,
                    'path'          => substr($path,$pos+6),
                    'url'           => $result['url'],
                    'media_id'      => $result['media_id'],
                    'aid'           => $aid,
                ]);
                $info = 'Success to add image!';
            }
        }
        $this->assign([
            'error' => empty($error)?'':$error,
            'info'  => empty($info)?'':$info,
            'aid'   =>    $aid
        ]);
        $this->show();
    }


}