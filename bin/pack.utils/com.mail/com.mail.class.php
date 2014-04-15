<?php
namespace Utils;

require_once('class.phpmailer.php');

abstract class Mail extends Utility{
    private static $options;
    
    public static function init($options = array()){
        extend($options, array(
            'from' => array(URL::domain(), 'robot@gexek.com'),
            'to' => array(URL::domain(), 'nobody@nowhere.com'),
            'body' => '',
        ));
        Mail::$options = $options;
    }
    
    public static function create(){
        $Mailer = new PHPMailer(); // defaults to using php "mail()"
        return $Mailer;
    }

    public static function send($subject, $content, $options = array()){
        foreach($options as $n => $v)
            Mail::$options[$n] = $v;
        
        // To send HTML mail, the Content-type header must be set
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        
        // Additional Headers
        if(count(Mail::$options['receivers']) > 0){
            $to = '';
            foreach(Mail::$options['receivers'] as $title => $email)
                $to .= $title.' <'.$email.'>,';
        } else
            $to = Mail::$options['receiverAlias'].' <'.Mail::$options['receiver'].'>';
        $to = trim($to, ',');
        
        $headers .= "To: ".$to."\r\n";
        $headers .= "From: ".Mail::$options['senderAlias']." <".Mail::$options['sender'].">\r\n";
        
        return @mail($to, $subject, $content, $headers);
    }
}

Mail::init();
?>