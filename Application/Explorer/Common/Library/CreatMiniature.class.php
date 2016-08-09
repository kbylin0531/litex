<?php 
namespace Application\Explorer\Common\Library;
/**
 * 类名：CreatMiniature
 * 功能：生成多种类型的缩略图
 * 基本参数：$srcFile,$echoType
 * 方法用到的参数：
 * $toFile,生成的文件 * $toW,生成的宽  $toH,生成的高*
 * $bk1,背景颜色参数 以255为最高 * $bk2,背景颜色参数 * $bk3,背景颜色参数
 * 
 * 例子：
 * include('thumb.php');
 * $cm=new CreatMiniature();
 * $cm->SetVar('1.jpg','file');
 * $cm->Distortion('dis_bei.jpg',150,200);

 * $cm->Prorate('pro_bei.jpg',150,200);//附带切割
 * $cm->Cut('cut_bei.jpg',150,200);
 * $cm->BackFill('fill_bei.jpg',150,200);
 */
class CreatMiniature {
	// 公共变量
	public $srcFile = '';	//原图
    public $echoType = null;		//输出图片类型，link--不保存为文件；file--保存为文件
    /**
     * @var resource
     */
    public $im = null;		//临时变量
    public $srcW = '';		//原图宽
    public $srcH = '';		//原图高
	// 设置变量及初始化
	function SetVar($srcFile, $echoType){
		$this->srcFile = $srcFile;
		$this->echoType = $echoType;

		$info = '';
		$data = getimagesize($this->srcFile, $info);
		switch ($data[2]) {
			case 1:
				if (!function_exists('imagecreatefromgif')) {
					exit();
				} 
				$this->im = imagecreatefromgif($this->srcFile);
				break;
			case 2:
				if (!function_exists('imagecreatefromjpeg')) {
					exit();
				} 
				$this->im = imagecreatefromjpeg($this->srcFile);
				break;
			case 3:
				$this->im = imagecreatefrompng($this->srcFile);
				break;
		} 
		$this->srcW = imagesx($this->im);
		$this->srcH = imagesy($this->im);
	} 
	// 生成扭曲型缩图
	function Distortion($toFile, $toW, $toH){
		$cImg = $this->CreatImage($this->im, $toW, $toH, 0, 0, 0, 0, $this->srcW, $this->srcH);
		return $this->EchoImage($cImg, $toFile);
	}
	// 生成按比例缩放的缩图
	function Prorate($toFile, $toW, $toH){
		$toWH = $toW / $toH;
		$srcWH = $this->srcW / $this->srcH;
		if ($toWH<=$srcWH) {
			$ftoW = $toW;
			$ftoH = $ftoW * ($this->srcH / $this->srcW);
		} else {
			$ftoH = $toH;
			$ftoW = $ftoH * ($this->srcW / $this->srcH);
		} 
		if ($this->srcW > $toW || $this->srcH > $toH) {
			$cImg = $this->CreatImage($this->im, $ftoW, $ftoH, 0, 0, 0, 0, $this->srcW, $this->srcH);
			return $this->EchoImage($cImg, $toFile);
		} else {
			$cImg = $this->CreatImage($this->im, $this->srcW, $this->srcH, 0, 0, 0, 0, $this->srcW, $this->srcH);
			return $this->EchoImage($cImg, $toFile);
		}
	} 
	// 生成最小裁剪后的缩图
	function Cut($toFile, $toW, $toH){
		$toWH = $toW / $toH;
		$srcWH = $this->srcW / $this->srcH;
		if ($toWH<=$srcWH) {
			$ctoH = $toH;
			$ctoW = $ctoH * ($this->srcW / $this->srcH);
		} else {
			$ctoW = $toW;
			$ctoH = $ctoW * ($this->srcH / $this->srcW);
		} 
		$allImg = $this->CreatImage($this->im, $ctoW, $ctoH, 0, 0, 0, 0, $this->srcW, $this->srcH);
		$cImg = $this->CreatImage($allImg, $toW, $toH, 0, 0, ($ctoW - $toW) / 2, ($ctoH - $toH) / 2, $toW, $toH);
		return $this->EchoImage($cImg, $toFile);
	}
	// 生成背景填充的缩图,默认用白色填充剩余空间，传入$isAlpha为真时用透明色填充
	function BackFill($toFile, $toW, $toH,$isAlpha=false,$red=255, $green=255, $blue=255){
		$toWH = $toW / $toH;
		$srcWH = $this->srcW / $this->srcH;
		if ($toWH<=$srcWH) {
			$ftoW = $toW;
			$ftoH = $ftoW * ($this->srcH / $this->srcW);
		} else {
			$ftoH = $toH;
			$ftoW = $ftoH * ($this->srcW / $this->srcH);
		} 
		if (function_exists('imagecreatetruecolor')) {
			@$cImg = imagecreatetruecolor($toW, $toH);
			if (!$cImg) {
				$cImg = imagecreate($toW, $toH);
			} 
		} else {
			$cImg = imagecreate($toW, $toH);
		}
		

		$fromTop = ($toH - $ftoH)/2;//从正中间填充
		$backcolor = imagecolorallocate($cImg,$red,$green, $blue); //填充的背景颜色
		if ($isAlpha){//填充透明色
			$backcolor=imagecolortransparent($cImg,$backcolor);
			$fromTop = $toH - $ftoH;//从底部填充
		}		

		imagefilledrectangle($cImg, 0, 0, $toW, $toH, $backcolor);
		if ($this->srcW > $toW || $this->srcH > $toH) {
			$proImg = $this->CreatImage($this->im, $ftoW, $ftoH, 0, 0, 0, 0, $this->srcW, $this->srcH);
			if ($ftoW < $toW) {
				imagecopy($cImg, $proImg, ($toW - $ftoW) / 2, 0, 0, 0, $ftoW, $ftoH);
			} else if ($ftoH < $toH) {
				imagecopy($cImg, $proImg, 0, $fromTop, 0, 0, $ftoW, $ftoH);
			} else {
				imagecopy($cImg, $proImg, 0, 0, 0, 0, $ftoW, $ftoH);
			} 
		} else {
			imagecopymerge($cImg, $this->im, ($toW - $ftoW) / 2,$fromTop, 0, 0, $ftoW, $ftoH, 100);
		} 
		return $this->EchoImage($cImg, $toFile);
	}

	function CreatImage($img, $creatW, $creatH, $dstX, $dstY, $srcX, $srcY, $srcImgW, $srcImgH){
		if (function_exists('imagecreatetruecolor')) {
			@$creatImg = imagecreatetruecolor($creatW, $creatH);
			if ($creatImg)
				imagecopyresampled($creatImg, $img, $dstX, $dstY, $srcX, $srcY, $creatW, $creatH, $srcImgW, $srcImgH);
			else {
				$creatImg = imagecreate($creatW, $creatH);
				imagecopyresized($creatImg, $img, $dstX, $dstY, $srcX, $srcY, $creatW, $creatH, $srcImgW, $srcImgH);
			} 
		} else {
			$creatImg = imagecreate($creatW, $creatH);
			imagecopyresized($creatImg, $img, $dstX, $dstY, $srcX, $srcY, $creatW, $creatH, $srcImgW, $srcImgH);
		} 
		return $creatImg;
	} 
	// 输出图片，link---只输出，不保存文件。file--保存为文件
	function EchoImage($img, $to_File){
		switch ($this->echoType) {
			case 'link':return imagepng($img);break;
			case 'file':return imagepng($img, $to_File);break;
		}
		return null;
	} 
}
