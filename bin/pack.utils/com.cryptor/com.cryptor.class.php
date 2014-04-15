<?php
namespace Utils;

abstract class Cryptor extends Utility{
	private static $ASCIIChars; // string of ASCII characters
	private static $adj; // 1st adjustment value (optional)
	private static $mod; // 2nd adjustment value (optional)
	public static $errors; // array of error messages
	static public function init(){
		// Each of these two strings must contain the same characters, but in a different order.
		// Use only printable characters from the ASCII table.
		// Do not use single quote, double quote or backslash as these have special meanings in PHP.
		// Each character can only appear once in each string.
		Cryptor::$ASCIIChars = array(
			'! #$%&()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_`abcdefghijklmnopqrstuvwxyz{|}~', 
			'f^jAE]okIOzU[2&q1{3`h5w_794p@6s8?BgP>dFV=m D<TcS%Ze|r:lGK/uCy.Jx)HiQ!#$~(;Lt-R}Ma,NvW+Ynb*0X'
		);
		
		if(strlen(Cryptor::$ASCIIChars[0])!=strlen(Cryptor::$ASCIIChars[1]))
			trigger_error('ASCIICHARS are not the same length', E_USER_ERROR);
		
		Cryptor::$adj = 1.75; // this value is added to the rolling fudgefactors
		Cryptor::$mod = 3; // if divisible by this the adjustment is made negative
	}

	static private function _convertKey($key){
		if(empty($key)){
			Cryptor::$errors[] = 'No value has been supplied for the encryption key';
			return;
		} // if
		
		$array[] = strlen($key);
		
		$tot = 0;
		for($i = 0; $i<strlen($key); $i++){
			
			$char = substr($key, $i, 1);
			$num = strpos(Cryptor::$ASCIIChars[0], $char);
			if($num===false){
				Cryptor::$errors[] = "Key contains an invalid character ($char)";
				return;
			} // if
			
			$array[] = $num;
			$tot = $tot+$num;
		} // for
		
		$array[] = $tot;
		return $array;
	} // _convertKey
	static private function _applyFudgeFactor(&$fudgefactor){
		$fudge = array_shift($fudgefactor);
		$fudge = $fudge+Cryptor::$adj;
		$fudgefactor[] = $fudge;
		
		if(!empty(Cryptor::$mod)){ // if modifier has been supplied
			if($fudge%Cryptor::$mod==0) // if it is divisible by modifier
				$fudge = $fudge*-1; // reverse then sign
		} // if
		return $fudge;
	} // _applyFudgeFactor
	static private function _checkRange($num){
		$num = round($num);
		$limit = strlen(Cryptor::$ASCIIChars[0]);
		while($num>=$limit)
			$num = $num-$limit;
		while($num<0)
			$num = $num+$limit;
		return $num;
	} // _checkRange
	static public function encrypt($key, $source, $sourcelen = 0){
		Cryptor::$errors = array();
		$fudgefactor = Cryptor::_convertKey($key);
		if(Cryptor::$errors)
			return;
		
		if(empty($source)){
			Cryptor::$errors[] = 'No value has been supplied for encryption';
			return;
		} // if
		
		while(strlen($source)<$sourcelen)
			$source .= ' ';
		
		$target = NULL;
		$factor2 = 0;
		for($i = 0; $i<strlen($source); $i++){
			$char1 = substr($source, $i, 1);
			$num1 = strpos(Cryptor::$ASCIIChars[0], $char1);
			if($num1===false){
				Cryptor::$errors[] = "Source string contains an invalid character ($char1)";
				return;
			} // if
			
			$adj = Cryptor::_applyFudgeFactor($fudgefactor);
			$factor1 = $factor2+$adj; // accumulate in $factor1
			
			$num2 = round($factor1)+$num1; // generate offset for $ASCIIChars[1]
			$num2 = Cryptor::_checkRange($num2); // check range
			$factor2 = $factor1+$num2; // accumulate in $factor
			$char2 = substr(Cryptor::$ASCIIChars[1], $num2, 1);
			$target .= $char2;
		} // for
		
		return $target;
	} // encrypt
	static public function decrypt($key, $source){
		Cryptor::$errors = array();
		$fudgefactor = Cryptor::_convertKey($key);
		if(Cryptor::$errors)
			return;
		
		if(empty($source)){
			Cryptor::$errors[] = 'No value has been supplied for decryption';
			return;
		} // if
		
		$target = NULL;
		$factor2 = 0;
		for($i = 0; $i<strlen($source); $i++){
			$char2 = substr($source, $i, 1);
			$num2 = strpos(Cryptor::$ASCIIChars[1], $char2);
			if($num2===false){
				Cryptor::$errors[] = "Source string contains an invalid character ($char2)";
				return;
			} // if
			
			$adj = Cryptor::_applyFudgeFactor($fudgefactor);
			$factor1 = $factor2+$adj;
			$num1 = $num2-round($factor1); // generate offset for $ASCIIChars[0]
			$num1 = Cryptor::_checkRange($num1); // check range
			$factor2 = $factor1+$num2; // accumulate in $factor2
			$char1 = substr(Cryptor::$ASCIIChars[0], $num1, 1);
			$target .= $char1;
		} // for
		
		return rtrim($target);
	} // decrypt
} // end encryption_class

Cryptor::init();
?>