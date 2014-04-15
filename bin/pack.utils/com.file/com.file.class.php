<?php
namespace Utils;

abstract class File extends Utility{
    const SCAN_NONE = 0; // extract nothing
    const SCAN_DIRS = 1; // extract directories
    const SCAN_FILES = 2; // extract files (not directories)
    const SCAN_BOTH = 3; // extract both files and directories
    
    public static $base;
    public static $mime_types;
    
    public static function init(){
        File::$base = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['PHP_SELF'])), '/');
        $mimefile = File::realpath(dirname(__FILE__).'/mimetypes.txt');
        $mimefile_default = File::realpath(dirname(__FILE__).'/mimetypes_default.txt');
        
        /* Older than a month
        if(filesize($mimefile) < 5000 || time() - filemtime($mimefile) > (360 * 24 * 30)){
            $url = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';
            if(!($result = @file_get_contents($url)))
                $result = file_get_contents($mimefile_default);
            file_put_contents($mimefile, $result);
        }*/
        
        $mime_types = array();
        foreach(@explode("\n",@file_get_contents($mimefile))as $x)
            if(isset($x[0])&&$x[0]!=='#'&&preg_match_all('#([^\s]+)#',$x,$out)&&isset($out[1])&&($c=count($out[1]))>1)
                for($i=1;$i<$c;$i++) $mime_types[$out[1][$i]] = $out[1][0];
        
        //add mimetype for .htc files manually
        $mime_types['htc'] = 'text/x-component';
        
        File::$mime_types = $mime_types;
    }
    
    public static function trail($path){
        $path =  str_replace('\\', '/', $path);
        return rtrim($path, '/').'/';
    }
    
    final public static function rename($file, $newname, $ext = true){
        if(!$ext) $newname = $newname.'.'.File::ext($file);
        rename($file, $newname);
    }
    
    final public static function removeExt($file){
        $p = explode('.', $file);
        $p = array_splice($p, 0, -1);
        return implode('.', $p);
    }
    
    final public static function mkdir($file){
    	if(!file_exists($file)){
    		$folders = preg_split('/[\/\\\]/', $file);
    		
    		$path = '';
    		foreach ($folders as $folder){
    			$path .= $folder.'/';
    			if(!file_exists($path)) mkdir($path);
    		}
    	}
    	return false;
    }
    
    final public static function appendName($file, $append){
        return File::removeExt($file)."$append.".File::ext($file);
    }
    
    final public static function ext($file, $lowercase = true){
        $p = explode('.', basename($file));
        return $lowercase? strtolower($p[count($p)-1]): $p[count($p)-1];
    }
    
    final public static function read($file){
        return file_get_contents($file);
    }
    
    final public static function write($file, $content){
        return file_put_contents($file, $content);
    }
    
    final public static function mime($file){
        $ext = File::ext($file);
        if(isset(File::$mime_types[$ext]))
            return File::$mime_types[$ext];
        else
            return 'application/octet-stream';
    }
    
    final public static function isEmpty($dir){
        return File::scandir($file) <= 0;
    }
    
    final public static function delete($file, $subs = false){
        if(file_exists($file)){
            if(is_dir($file)){
                $files = File::scandir($file);
                if(count($files) > 0 && !$subs)
                    return false;
                
                foreach($files as $f)
                    File::delete(File::trail($file).$f, $subs);
                
                rmdir($file);
            } else
                unlink($file);
        }
    }
    
    public static function get_php_classes($filepath) {
        $php_code = File::read($filepath);
        $classes = File::parse_classes($php_code);
        return $classes;
    }
    
    private static function parse_classes($php_code) {
        $classes = array();
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if (   $tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING) {
            
                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }
        return $classes;
    }
    
    /* works as the same PHP internal realpath() function
     * externally converts an address to unix format
     * to be portable in both Windows and Unix base operation systems
    */
    static public function realpath($path){
        $path = str_replace('\\', '/', realpath($path));
        $path = rtrim($path, '/');
        return $path;
    }
    
    /*
     * the same PHP internal scandir() functionality
     * in addition $scan_type will filter the output
     * for more information about filters, see consts documentations
    */
    static public function scandir($directory, $scan_type = File::SCAN_BOTH, $sorting_order = 0, $filter = null){
        $files = array();
        
        $filter = str_replace('*', '([a-zA-Z_\-]+)', $filter);
        
        // converts address
        $directory = File::realpath($directory);
        if(file_exists($directory)){
            // extract all files
            $all = scandir($directory, $sorting_order);
            
            // if scaning failed, so return false
            // so the function can be used as the same internal scandir() function in conditions
            if($all === false) return false;

            foreach($all as $filename){ // loop through files
                // filter sibling operators
                if(!in_array($filename, array('.','..'))){
                	if(empty($filter) || !preg_match("/^$filter$/", $filename)){
	                    switch($scan_type){
	                        // add directories to output
	                        case File::SCAN_DIRS:
	                            if(is_dir("$directory/$filename"))
	                                $files[] = $filename;
	                            break;
	                        
	                        // add files to output
	                        case File::SCAN_FILES:
	                            if(!is_dir("$directory/$filename"))
	                                $files[] = $filename;
	                            break;
	                        
	                        // add files and directories to output
	                        case File::SCAN_BOTH:
	                            $files[] = $filename;
	                            break;
	                    }
                	}
                }
            }
        }
        return $files;
    }
    
    static public function isImage($file){
    	try{
        	return is_file($file) && getimagesize($file) !== false;
    	} catch(\Exception $e){
    		return false;
    	}
    }
}
?>