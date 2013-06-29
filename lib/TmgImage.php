<?php

defined('ROOTPATH') or define('ROOTPATH', getcwd());

define('ORIENTATION1', 270);
define('ORIENTATION2', 180);
define('ORIENTATION3', 90);
define('ORIENTATION4', 0);

class TmgImage {
  
  private $dir_path = NULL;
  private $base_url = NULL;
  private $type = NULL;
  private $img_name = NULL;
  private $img_ext = NULL;
  private $img_dir_path = NULL;
  private $img_base_url = NULL;
  private $image = NULL;
  private $format = NULL;
  
  function __construct($img_url,$img_path = null, $format = null) {
    $this->base_url = $img_url;
    if(isset($format)){
      $this->format = $format;
    }
    if(isset($img_path)){
      $this->dir_path = $img_path;
    }else{
      $this->dir_path = ROOTPATH . DIRECTORY_SEPARATOR . $img_url;
    }
  }
  
  public function getImage($path, $width = NULL, $height = NULL,$orientation = NULL){
    
    $file_info = pathinfo($path);
    
    if(isset($file_info['dirname']) && isset($file_info['filename']) && isset($file_info['extension'])){
      
      if(strpos ( $file_info['dirname'] , $this->dir_path ) == FALSE){
        $this->img_dir_path = $file_info['dirname'];
      }else{
        $this->img_base_url = $this->dir_path . $file_info['dirname'];
      }
      
      $this->img_base_url = $this->base_url;
      $this->img_name = $file_info['filename'];
      $this->img_ext = strtolower($file_info['extension']);
    }else{
      return NULL;
    }
    
    $path_img_base = $this->img_dir_path . DIRECTORY_SEPARATOR . $this->img_name . '.' . $this->img_ext;
    if(file_exists( $path_img_base )){
      $imgInfo = getimagesize($path_img_base);
      $target_name = $this->targetInfo($imgInfo,$width,$height,$orientation);
      $path_img_target = $this->img_dir_path . DIRECTORY_SEPARATOR . $target_name . '.' . $this->img_ext;
      if(file_exists( $path_img_target )){
        return $this->img_base_url .'/'. $target_name .'.'. $this->img_ext;
      }
      $this->loadImg();
      $this->transform($imgInfo,$width, $height,$orientation);
      $this->saveImg();
      return $this->img_base_url .'/'. $this->img_name .'.'. $this->img_ext;
    }
    
    return NULL;
  }
  
  private function targetInfo($info,&$width,&$height,&$orientation){
    
    $target_name = $this->img_name;
    if(isset($orientation) && $orientation == ORIENTATION4){
      $orientation = NULL;
    }elseif(isset($orientation)){
      $target_name .= '-rot'.$orientation;
    }
    
    if(!isset($width)){
      $width = $info[0];
    }
     
    if(!isset($height)){
      if($info[0] == 0 && $width == 0){
        $height = 0;
      }elseif($info[0] == 0){
        $height = $info[1];
      }else{
        $height = floor($width * $info[1] / $info[0]);        
      }
    }
    
    if($width != $info[0] || $height != $info[1]){
      $target_name .= '-'.$width.'x'.$height;
    }
    return $target_name;
  }
  
  private function transform($info,$width,$height,$orientation){
    if(!isset($info)){
      return NULL;
    }

    if(isset($orientation)){
      $this->rotate($orientation);
    }
    $info[0] = imagesx($this->image);
    $info[1] = imagesy($this->image);
    
    if($width != $info[0] || $height != $info[1]){
      $this->resize($info[0],$info[1],$width,$height);
    }
    $info[0] = imagesx($this->image);
    $info[1] = imagesy($this->image);
  }
  
  private function loadImg(){
    if(!isset($this->img_ext) || !isset($this->img_dir_path) || !isset($this->img_name)){
      return FALSE;
    }
    $path_img_base = $this->img_dir_path . DIRECTORY_SEPARATOR . $this->img_name .'.'. $this->img_ext;
    if(isset($this->format)){
      $path_img_base = $this->img_dir_path . DIRECTORY_SEPARATOR . $this->img_name .'-'. $this->format .'.'. $this->img_ext;
      if(!file_exists( $path_img_base )){
        $path_img_base = $this->img_dir_path . DIRECTORY_SEPARATOR . $this->img_name .'.'. $this->img_ext;
      }
    }
    switch ($this->img_ext) {
      case 'jpg' :
      case 'jpeg' :
        $this->image = @imagecreatefromjpeg($path_img_base);
        break;
      case 'gif' :
        $this->image = @imagecreatefromgif($path_img_base);
        break;
      case 'png' :
        $this->image = @imagecreatefrompng($path_img_base);
        break;
      default :
        $this->image = null;
    }
  }
  
  private function saveImg(){
    if(!isset($this->img_ext) || !isset($this->img_dir_path) || !isset($this->img_name)){
      return FALSE;
    }
    
    $save_path = $this->img_dir_path . DIRECTORY_SEPARATOR . $this->img_name . '.' . $this->img_ext;
    
    switch ($this->img_ext) {
      case 'jpg' :
      case 'jpeg' :
        @imagejpeg($this->image, $save_path, 100);
        break;
      case 'gif' :
        @imagegif($this->image, $save_path);
        break;
      case 'png' :
        @imagepng($this->image, $save_path,9);
        break;
      default :
        $this->image = null;
    }
    
  }
  
  
  private function resize($width,$height,$new_width,$new_height){
    
    $new_img = @imagecreatetruecolor($new_width, $new_height);
    switch ($this->img_ext) {
      case 'jpg' :
      case 'jpeg' :
        break;
      case 'gif' :
        @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
        break;
      case 'png' :
        @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
        @imagealphablending($new_img, false);
        @imagesavealpha($new_img, true);
        break;
      default :
        $src_img = null;
    }
    @imagecopyresampled($new_img,$this->image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    $this->img_name .= '-'.$new_width.'x'.$new_height;
    $this->image = $new_img;
    return true;
  }
  
  private function crop($start_width,$start_height,$new_width,$new_height){
    $new_img = @imagecreatetruecolor($new_width, $new_height);
    switch ($this->img_ext) {
      case 'jpg' :
      case 'jpeg' :
        break;
      case 'gif' :
        @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
        break;
      case 'png' :
        @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
        @imagealphablending($new_img, false);
        @imagesavealpha($new_img, true);
        break;
      default :
        $src_img = null;
    }
    @imagecopyresampled($new_img, $this->image, 0, 0, $start_width, $start_height, $new_width, $new_height, $start_width + $new_width , $start_height + $new_height);
    $this->image = $new_img;
  }
  
  private function rotate($orientation){
    $this->image = @imagerotate($this->image, $orientation, 0);
    $this->img_name .= '-rot'.$orientation;
    return true;
  }
  
}


?>