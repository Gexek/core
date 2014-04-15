<?php
namespace Utils;

abstract class Image extends Utility{
    static public function resize($image, $width, $height, $target_image = null, $comprssion = 99){
        if(file_exists($image)){
            if(is_null($target_image)) $target_image = $image;
            else if(is_dir($target_image))
                $target_image = File::realpath($target_image).'/'.basename($image);
            
            list($_width, $_height, $_type) = getimagesize($image);
            $_source = self::createFromFile($image, $_type);
            if ($_source === false) return false;
            
            $target = imagecreatetruecolor($width, $height);
            imagecopyresampled($target, $_source, 0, 0, 0, 0, $width, $height, $_width, $_height);
			imagedestroy($_source);
			
            self::writeToFile($target, $_type, $target_image);
            imagedestroy($target);
            
            return true;
        } else 
        	return false;
    }
    
    static public function getSize($image){
    	$size = array();
    	if(is_string($image)){
    		list($w, $h) = getimagesize($image);
    		$size = array($w, $h, 'width' => $w, 'height' => $h);
    	} else {
    		$w = imagesx($image);
    		$h = imagesy($image);
    		$size = array($w, $h, 'width' => $w, 'height' => $h);
    	}
    	return $size;
    }
    
    static public function scale($image, $width, $height, $target_image = null, $comprssion = 99){
        if(file_exists($image)){
        	$ratio = $width / $height;
        	
            list($_width, $_height) = getimagesize($image);
            $_ratio = $_width / $_height;
            
            if ($_ratio > $ratio) $height = (int)($width / $_ratio);
            else $width = (int)($height * $_ratio);
            
            Image::resize($image, $width, $height, $target_image, $comprssion);
        }
    }
    
    static private function createFromFile($filename, $type){
    	try{
    		switch ($type){
    			case IMAGETYPE_GIF: $image = imagecreatefromgif($filename); break;
    			case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($filename); break;
    			case IMAGETYPE_PNG: $image = imagecreatefrompng($filename); break;
    		}
    	} catch (Exception $e){
    		return false;
    	}
    	return $image;
    }
    
    static private function writeToFile($image, $type, $filename){
    	switch ($type){
    		case IMAGETYPE_GIF: imagegif($image, $filename); break;
    		case IMAGETYPE_JPEG: imagejpeg($image, $filename, 99); break;
    		case IMAGETYPE_PNG: imagepng($image, $filename); break;
    	}
    }
    
    static public function createThumb($source, $target, $query = ''){
    	// get image type (gif, jpg, png)
    	$_type = exif_imagetype($source);
    
    	// create a virtual image
    	$_source = self::createFromFile($source, $_type);
    
    	// fetch width and height of source image
    	$_width = imagesx($_source);
    	$_height = imagesy($_source);
    
    	// source file ratio
    	$_ratio = round($_width / $_height, 3);
    
    	// force the target path to be a directory
    	$target = is_dir($target)? $target: dirname($target);
    
    	// extract parameters from query
    	$params = array();
    	foreach(preg_split('/[\;&]/', $query, 0, PREG_SPLIT_NO_EMPTY) as $q){
    		@list($name, $value) = preg_split('/[=:]/', $q, 0, PREG_SPLIT_NO_EMPTY);
    		$params[$name] = $value;
    	}
    
    	// initial the default thumb name
    	$thumbname = File::removeExt(basename($source)).'_thumb';
    
    	// extend params with default values
    	extend($params, array(
	    	'cropX' => 'center',
	    	'cropY' => 'center',
	    	// user defined filenames should not have extension
	    	'thumbname' => $thumbname
    	));
    	if(!isset($params['width'])){
    		if(!isset($params['height'])) $params['height'] = 100;
    		$params['width'] = floor($params['height'] * $_ratio);
    	}
    	if(!isset($params['height'])){
    		if(!isset($params['width'])) $params['width'] = 100;
    		$params['height'] = floor($params['width'] / $_ratio);
    	}
    
    	$params['height'] = (int)$params['height'];
    	$params['width'] = (int)$params['width'];
    
    	// thumb file ratio
    	$params['ratio'] = round($params['width'] / $params['height'], 3);

    	// final thumb name
    	$thumbname = "$target/$params[thumbname].".File::ext($source);
    
    	$crop_x = $crop_y = 0;
    	$crop_w = $params['width'];
    	$crop_h = $params['height'];
    	if($_ratio != $params['ratio']){
    		if($_ratio > $params['ratio']){
    			// If image is wider than thumbnail (in aspect ratio sense)
    			$crop_h = $params['height'];
    			$crop_w = floor($_width / ($_height / $params['height']));
    		} else {
    			// If the thumbnail is wider than the image
    			$crop_h = floor($_height / ($_width / $params['width']));
    		}
    
    		switch ($params['cropY']){
    			case 'center': $crop_y -= ($crop_h - $params['height']) / 2; break;
    			case 'bottom': $crop_y -= ($crop_h - $params['height']); break;
    			default: $crop_y = 0; break;
    		}
    
    		switch ($params['cropX']){
    			case 'center': $crop_x -= ($crop_w - $params['width']) / 2; break;
    			case 'right': $crop_x -= ($crop_w - $params['width']); break;
    			default: $crop_x = 0; break;
    		}
    	}
    
    	// create a virtual image of thumbnail
    	$target = imagecreatetruecolor($params['width'], $params['height']);
    
    	// resample thumbnail
    	imagecopyresampled($target, $_source, $crop_x, $crop_y, 0, 0, $crop_w, $crop_h, $_width, $_height);
    
    	// write image to file
    	self::writeToFile($target, $_type, $thumbname);
    
    	imagedestroy($_source);
    	imagedestroy($target);
    }
    
    static public function createThumbs($source, $target, $sizes = array(), $name = 'x'){
        foreach($sizes as $size){
            $thumb_size = (array)$size;
            if(!isset($thumb_size[1])) $thumb_size[1] = $thumb_size[0];
            
            self::createThumb($source, $target, 'width='.$thumb_size[0].';height:'.$thumb_size[1].';thumbname='.$name.$thumb_size[0]);
        }
    }
}
?>