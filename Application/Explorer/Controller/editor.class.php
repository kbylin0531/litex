<?php 
namespace Application\Explorer\Controller;
use Application\Explorer\Common\Library\FileCache;
use Application\Explorer\Common\Library\ExplorerController;

class editor extends ExplorerController{
	function __construct()    {
		parent::__construct();
		$this->tpl = TEMPLATE . 'editor/';
	}

	// 多文件编辑器
	public function index(){
		$this->display('editor.php');
	}
	// 单文件编辑
	public function edit(){
		$this->assign('editor_config',$this->getConfig());//获取编辑器配置信息
		$this->display('edit.php');
	}

	// 获取文件数据
	public function fileGet(){
		$filename=_DIR($this->in['filename']);
		if (!is_readable($filename)) show_json($this->L['no_permission_read'],false);
		if (filesize($filename) >= 1024*1024*20) show_json($this->L['edit_too_big'],false);

		$filecontents=file_get_contents($filename);//文件内容
		$charset=get_charset($filecontents);
		if ($charset!='' || $charset!='utf-8') {
			$filecontents=mb_convert_encoding($filecontents,'utf-8',$charset);
		}
		$data = array(
			'ext'		=> get_path_ext($filename),
			'name'      => iconv_app(get_path_this($filename)),
			'filename'	=> rawurldecode($this->in['filename']),
			'charset'	=> $charset,
			'content'	=> $filecontents			
		);
		show_json($data);
	}
	public function fileSave(){
		$filestr = rawurldecode($this->in['filestr']);
		$charset = $this->in['charset'];
		$path =_DIR($this->in['path']);
		if (!is_writable($path)) show_json($this->L['no_permission_write_file'],false);
		
		if ($charset !='' || $charset != 'utf-8') {
			$filestr=mb_convert_encoding($filestr,$this->in['charset'],'utf-8');
		}
		$fp=fopen($path,'wb');
		fwrite($fp,$filestr);
		fclose($fp);
		show_json($this->L['save_success']);
	}

	/*
	* 获取编辑器配置信息
	*/
	public function getConfig(){
		$default = array(
			'font_size'		=> '15px',
			'theme'			=> 'clouds',
			'auto_wrap'		=> 0,
			'display_char'	=> 0,
			'auto_complete'	=> 1,
			'function_list' => 1
		);
		$config_file = USER.'data/editor_config.php';		
		if (!file_exists($config_file)) {//不存在则创建
			$sql=new FileCache($config_file);
			$sql->reset($default);
		}else{
			$sql=new FileCache($config_file);
			$default = $sql->get();
		}
		if (!isset($default['function_list'])) {
			$default['function_list'] = 1;
		}
		return json_encode($default);
    }
	/*
	* 获取编辑器配置信息
	*/
	public function setConfig(){
		$file = USER.'data/editor_config.php';	
        if (!is_writeable($file)) {//配置不可写
            show_json($this->L['no_permission_write_file'],false);
        }
		$key= $this->in['k'];
		$value = $this->in['v'];
        if ($key !='' && $value != '') {
        	$sql=new FileCache($file);
        	if(!$sql->update($key,$value)){
        		$sql->add($key,$value);//没有则添加一条
        	}
            show_json($this->L["setting_success"]);
        }else{
            show_json($this->L['error'],false);
        }
    }

}