<?php
class JalaliDate implements IDate{
    private $patterns = array(
	'jdate' => '([0-9]{4})\/(0?[1-9]|1[012])\/(0?[1-9]|[12][0-9]|3[01])',
	'jtime' => '(0?[0-9]|1[0-9]|2[0123]):(0?[0-9]|[1-5][0-9]):(0?[0-9]|[1-5][0-9]) ?(AM|PM|pm|am)?'
    );
    
    private $secIn = array(
	'year' => 31536000,
	'month' => 2592000,
	'week' => 604800,
	'day' => 86400,
	'hour' => 3600,
	'minute' => 60,
	'second' => 1
    );
    
    public function GetTime(){ return time(); }
    
    public function toLocale($g_y, $g_m, $g_d, $seperator = null){
	$g_y = ($g_y); $g_m = ($g_m); $g_d = ($g_d);/* <= :اين سطر ، جزء تابع اصلي نيست */
	$g_a = array(0,0,31,59,90,120,151,181,212,243,273,304,334);
	$doy_g = $g_a[(int)$g_m]+$g_d;
	if(($g_y%4) == 0 and $g_m > 2)$doy_g++;
	$jy = ($doy_g<80)?$g_y-622:$g_y-621;
	$doy_j = ($doy_g>79)?$doy_g-80:$doy_g+(($jy%4==3)?286:285);
	if($doy_j<186){ $a=0; $b=31; $c=1;}
	else{ $a=186; $b=30; $c=7;}
	
	$jm = (int)(($doy_j-$a)/$b);
	$jd = $doy_j-$a-($jm*$b)+1;
	$jm += $c;
	return empty($seperator)? array($jy,$jm,$jd): $jy.$seperator.$jm.$seperator.$jd;
    }

    public function toGregorian($l_y, $l_m, $l_d, $seperator = null){
	$l_y = ($l_y); $l_m = ($l_m); $l_d = ($l_d);/* <= :اين سطر ، جزء تابع اصلي نيست */
	$doy_j=($l_m<7)? ((($l_m-1)*31)+$l_d): ((($l_m-7)*30)+$l_d)+186;
	$d28x=($l_y%4==3)? 287: 286;
	if($doy_j > $d28x){
	    $gy = $l_y+622;
	    $gd = $doy_j-$d28x;
	} else {
	    $gy = $l_y+621;
	    $gd = $doy_j+79;
	}
	$g_a = array(0, 31, (($gy%4==0) ? 29 :28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	foreach($g_a as $gm => $v){
	    if($gd <= $v)break;
		$gd -= $v;
	}
	return empty($seperator)? array($gy,$gm,$gd): $gy.$seperator.$gm.$seperator.$gd;
    }
    
    public function isLeap($year){
	return ($year%33%4-1 == (int)($year%33*.05))? 1: 0;
    }
    
    private function extractJDate($strdate){
	$ptrn = '/^('.$this->patterns['jdate'].')? ?('.$this->patterns['jtime'].')?$/';
	preg_match_all($ptrn, $strdate, $matches); // extract all matches
	
	unset($matches[0], $matches[1], $matches[5]); // unsetting all unneccessary parts
	foreach($matches as &$match) $match = $match[0]; // delete sub arrays
	
	$matches = array_merge(array(), $matches); // resetting indexes
	
	if(empty($matches[6])) {
	    if(empty($matches[3]))
		$matches[6] = $this->toString('a');
	    else {
		if((int)$matches[3] > 12) $matches[6] = '';
		else $matches[6] = 'am';
	    }
	}
	
	if(empty($matches[0])) $matches[0] = $this->toString('Y'); // if year not specified
	if(empty($matches[1])) $matches[1] = $this->toString('m'); // if month not specified
	if(empty($matches[2])) $matches[2] = $this->toString('d'); // if day not specified
	if(empty($matches[3])) $matches[3] = $this->toString('H'); // if hour not specified
	if(empty($matches[4])) $matches[4] = $this->toString('i'); // if minute not specified
	if(empty($matches[5])) $matches[5] = $this->toString('s'); // if second not specified
	if((int)$matches[3] == 0 || (int)$matches[3]>12) $matches[6] = '';
	
	return $matches;
    }
    
    public function fromString($strtime, $timestamp){
        if($timestamp == -1) $timestamp = time();
        
	if(preg_match_all('/(([first|last]+) day of)? ?([first|last|next]+)? ?month/', $strtime, $matches)){
	    unset($matches[0], $matches[1]); // unsetting all unneccessary parts
	    foreach($matches as &$match) $match = $match[0]; // delete sub arrays
	    $matches = array_merge(array(), $matches); // resetting indexes

	    $y = (int)($this->toString('Y', $timestamp));
	    $m = (int)($this->toString('m', $timestamp));
	    $h = (int)($this->toString('H', $timestamp));
	    $i = (int)($this->toString('i', $timestamp));
	    $s = (int)($this->toString('s', $timestamp));
	    $t = "$h:$i:$s";
	    
	    switch($matches[1]){
		// next month
		case 'next': if($m == 12){$m = 1; $y++;} else $m++; break;
		// first month
		case 'first': $m = 12; break;
		// last month
		case 'last': $m = 12; break;
		// current month
		default: $m = (int)($this->toString('m', $timestamp)); break;
	    }
	    
	    switch($matches[0]){
		// first day of month
		case 'first': $d = 1; break;
		// last day of month
		case 'last':
		    $t = ($h+$i+$s) == 0? '23:59:59': "$h:$i:$s";
		    if($m<6) $d = 31;
		    else if($m<12) $d = 30;
		    else $d = $this->isLeap($y)? 30: 29;
		    break;
		// current day of month
		default: $d = (int)($this->toString('d', $timestamp)); break;
	    }
	    return $this->fromString("$y/$m/$d $t", $timestamp);
	} else {
	    $ptrn = '/^('.$this->patterns['jdate'].')? ?('.$this->patterns['jtime'].')?$/';
	    if(preg_match($ptrn, $strtime)){
		list($j_y, $j_m, $j_d, $h, $i, $s, $a) = $this->extractJDate($strtime);
                switch($a){
                    case 'ق.ظ': $a = 'am'; break;
                    case 'ب.ظ': $a = 'pm'; break;
                    case 'قبل از ظهر': $a = 'AM'; break;
                    case 'بعد از ظهر': $a = 'PM'; break;
                }
		list($g_y, $g_m, $g_d) = $this->toGregorian($j_y, $j_m, $j_d);
		return strtotime("$g_y/$g_m/$g_d $h:$i:$s $a");
	    } else
		return strtotime($strtime, $timestamp);
	}
    }
    
  
    public function toString($format, $timestamp = -1){
	
	$none = '';
	$time_zone = 'Asia/Tehran';
	$tr_num = 'fa';
	$T_sec = '0';// <= رفع خطاي زمان سرور ، با اعداد '+' و '-' بر حسب ثانيه *
	
	$ts = ($timestamp == -1)? time()+$T_sec: ($timestamp)+$T_sec;
	$date = explode('_', date('a_d_m_N_w_Y', $ts));
	
	list($j_y, $j_m, $j_d) = $this->toLocale($date[5], $date[2], $date[1]);
	
	$doy = ($j_m<7)? ((($j_m-1)*31)+$j_d-1): ((($j_m-7)*30)+$j_d+185);
	$kab = $this->isLeap($j_y);
	$out = '';

	for($i=0; $i<strlen($format); $i++){
	    $sub = substr($format, $i, 1);
	    if($sub == '\\'){
		$out .= substr($format,($i+1),1);
		$i++;
	    }
	    switch($sub){
		case'C':case'E':case'R':case'x':case'X':$out.='<a href="http://jdf.scr.ir/">دریافت نسخه ی جدید http://jdf.scr.ir</a>'; break;
		case'\\': $out.='';  break;
		case'B':case'e':case'g':case'G':case'h':case'H':case'i': case'I':case'O':case'P':case's':case'T':case'u':case'Z':
		    $out .= date($sub, $ts);
		    break;
		case'a': $out.=($date[0]=='pm')?'ب.ظ':'ق.ظ'; break;
		case'A':$out.=($date[0]=='pm')?'بعد از ظهر':'قبل از ظهر';break;
		case'b': $out.=ceil($j_m/3);break;
		case'c':$out.=$this->toString('Y/n/j ,H:i:s P',$ts,'',$time_zone,$tr_num);break;
		case'd':$out.=($j_d<10)?'0'.$j_d:$j_d;break;
		case'D': $key=array('ی','د','س','چ','پ','ج','ش'); $out.=$key[$date[4]]; break;
		case'f':
		    $key=array('بهار','تابستان','پاییز','زمستان');
		    $out.=$key[ceil($j_m/3)-1];
		    break;
		case'F':
		    $key=array(
		    'فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند');
		    $out.=$key[$j_m-1];
		    break;
		case'j': $out.=$j_d; break;
		case'J':
		    $key=array('یک','دو','سه','چهار','پنج','شش','هفت','هشت','نه','ده','یازده','دوازده','سیزده',
		    'چهارده','پانزده','شانزده','هفده','هجده','نوزده','بیست','بیست و یک','بیست و دو','بیست و سه',
		    'بیست و چهار','بیست و پنج','بیست و شش','بیست و هفت','بیست و هشت','بیست و نه','سی','سی و یک');
		    $out.=$key[$j_d-1];
		    break;
		case'k';$out.=100-round(($doy/($kab+365)*100),1); break;
		case'K':$out.=round(($doy/($kab+365)*100),1); break;
		case'l':
		    $key=array('یکشنبه','دوشنبه','سه شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه');
		    $out.=$key[$date[4]];
		    break;
		case'L':$out.=$kab;break;
		case'm': $out.=($j_m<10)?'0'.$j_m:$j_m; break;
		case'M':
		    $key=array('فر','ار','خر','تی‍','مر','شه‍','مه‍','آب‍','آذ','دی','به‍','اس‍');
		    $out.=$key[$j_m-1];
		    break;
		case'n': $out.=$j_m; break;
		case'N': $out.=($date[3]!=7)?$date[3]+1:1; break;
		case'o':
		    $jdw=($date[4]!=6)?$date[4]+1:0;
		    $dny=364+$kab-$doy;
		    $out.=($doy<3 and $jdw>($doy+3))?$j_y-1:(($dny<3 and (3-$dny)>$jdw)?$j_y+1:$j_y);
		    break;
		case'p':
		    $key=array('حمل','ثور','جوزا','سرطان','اسد','سنبله','میزان','عقرب','قوس','جدی','دلو','حوت');
		    $out.=$key[$j_m-1];
		    break;
		case'q':
		    $key=array('مار','اسب','گوسفند','میمون','مرغ','سگ','خوک','موش','گاو','پلنگ','خرگوش','نهنگ');
		    $out.=$key[$j_y%12];
		    break;
		case'Q':$out.=$kab+364-$doy;break;
		case'r':$out.=$this->toString('H:i:s O l, j F Y',$ts,'',$time_zone,$tr_num); break;
		case'S':$out.='ام';break;
		case't':$out.=($j_m!=12)?(31-(int)($j_m/6.5)):($kab+29);break;
		case'U':$out.=$ts;break;
		case'v':
		    $xy3=substr($j_y,2,1);
		    $h3=$h34=$h4='';
		    if($xy3==1){
		    $p34='';
		    $k34=array('ده','یازده','دوازده','سیزده','چهارده','پانزده','شانزده','هفده','هجده','نوزده');
		    $h34=$k34[substr($j_y,2,2)-10];
		    }else{
		    $xy4=substr($j_y,3,1);
		    $p34=($xy3==0 or $xy4==0)?'':' و ';
		    $k3=array('','','بیست','سی','چهل','پنجاه','شصت','هفتاد','هشتاد','نود');
		    $h3=$k3[$xy3];
		    $k4=array('','یک','دو','سه','چهار','پنج','شش','هفت','هشت','نه');
		    $h4=$k4[$xy4];
		    }
		    $out.=$h3.$p34.$h34.$h4;
		    break;
		case'V':
		    $out.=
		    str_ireplace(array('00','13','14'),array('','هزار و سیصد','هزار و چهارصد'),substr($j_y,0,2))
		    .((substr($j_y,2,2)=='00')?'':' و ')
		    .$this->toString('v',$ts,'',$time_zone);
		    break;
		case'w': $out.=($date[4]!=6)?$date[4]+1:0; break;
		case'W':
		    $avs=$this->toString('w',$ts-($doy*86400),'',$time_zone,'en');
		    $num=(int)(($doy+$avs)/7);
		    if($avs<4){
			$num++;
		    }elseif($num<1){
			$num=($avs==4 or $avs==(($j_y%33%4-2==(int)($j_y%33*.05))?5:4))?53:52;
		    }
		    $aks=$avs+$kab;
		    if($aks==7)$aks=0;
		    $out.=($aks<3 and ($kab+363-$doy)<$aks)?'01':(($num<10)?'0'.$num:$num);
		    break;
		case'y': $out.=substr($j_y,2,2); break;
		case'Y': $out.=$j_y; break;
		case'z': $out.=$doy; break;
		default: $out.=$sub; break;
	    }
	}
	return($tr_num=='fa' or $tr_num=='')?($out):$out;
    }
}

$this->date = new JalaliDate();
?>