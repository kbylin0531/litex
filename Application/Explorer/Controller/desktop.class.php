<?php 
namespace Application\Explorer\Controller;

use Application\Explorer\Common\Library\ExplorerController;
class desktop extends ExplorerController{
    function __construct() {
        parent::__construct();
        $this->tpl = TEMPLATE.'desktop/';	
    }
    public function index() {
        $wall = $this->config['user']['wall'];
        if(strlen($wall)>3){
            $this->assign('wall',$wall);
        }else{
            $this->assign('wall',STATIC_PATH.'images/wall_page/'.$wall.'.jpg');
        }
        if (!is_dir(MYHOME.'desktop/') && is_writable(MYHOME)) {
            mkdir(MYHOME.'desktop/');
        }
        $this->display('index.php');
    }
}
