<?php

function loadLines($file){
    return array_filter(array_map('trim', file($file)));
}

function loadSMTPList(){
    $rows = loadLines('config/smtp.txt');
    $list = [];
    foreach($rows as $r){
        list($host,$port,$user,$pass,$secure)=explode("|",$r);
        $list[] = compact("host","port","user","pass","secure");
    }
    return $list;
}

function loadContacts(){
    $rows = loadLines('config/contacts.txt');
    $list=[];
    foreach($rows as $r){
        $parts=explode("|",$r);
        $email=$parts[0];
        $name=$parts[1]??"Customer";
        $list[]=compact("email","name");
    }
    return $list;
}

function randomSubject(){
    $s=loadLines('config/subjects.txt');
    return $s[array_rand($s)];
}

function loadBody(){
    return file_get_contents('config/body.html');
}

function smtp_cmd($socket,$cmd){
    fwrite($socket,$cmd."\r\n");
    return fgets($socket,512);
}

function sendSMTPMail($smtp,$to,$name,$subject,$body){

    $host=$smtp['host'];
    $port=$smtp['port'];
    $user=$smtp['user'];
    $pass=$smtp['pass'];
    $secure=$smtp['secure'];

    if($secure=="ssl"){
        $host="ssl://".$host;
    }

    $socket=stream_socket_client($host.":".$port,$errno,$errstr,30);
    if(!$socket){
        return "Connection failed: $errstr";
    }

    fgets($socket,512);
    smtp_cmd($socket,"EHLO localhost");

    if($secure=="tls"){
        smtp_cmd($socket,"STARTTLS");
        stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT);
        smtp_cmd($socket,"EHLO localhost");
    }

    smtp_cmd($socket,"AUTH LOGIN");
    smtp_cmd($socket,base64_encode($user));
    smtp_cmd($socket,base64_encode($pass));

    smtp_cmd($socket,"MAIL FROM:<$user>");
    smtp_cmd($socket,"RCPT TO:<$to>");
    smtp_cmd($socket,"DATA");

    $message = "From: $user\r\n";
    $message .= "To: $to\r\n";
    $message .= "Subject: $subject\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message .= str_replace("{name}",$name,$body)."\r\n.";

    smtp_cmd($socket,$message);
    smtp_cmd($socket,"QUIT");

    fclose($socket);
    return true;
}
?>
