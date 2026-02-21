<?php
require 'functions.php';

$smtps=loadSMTPList();
$contacts=loadContacts();
$body=loadBody();

$smtpIndex=0;
$total=0;

foreach($contacts as $c){

    $smtp=$smtps[$smtpIndex];
    $subject=randomSubject();

    $result=sendSMTPMail(
        $smtp,
        $c['email'],
        $c['name'],
        $subject,
        $body
    );

    echo $c['email']." -> ".($result===true?"Sent":"Failed: $result")."<br>";

    $smtpIndex++;
    if($smtpIndex>=count($smtps)) $smtpIndex=0;
    $total++;
}

echo "<hr>Total: $total";
?>
