<?php

namespace App\Service;

class MailService
{
    public function sendMail($subject, $template, $email, $siteName)
    {
        $array['subject'] = $subject;
        $array['sender'] = array('name' => $siteName,
         'email' =>'201907222049.28647049849@smtp-relay.mailin.fr');
        $array['htmlContent'] = $template;
        $array['to'] = array(array('name' => strstr($email, '@', true), 'email' => $email));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendinblue.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array));
        curl_setopt($ch, CURLOPT_POST, 1);
        $headers = array();
        $headers[] = 'Api-Key: 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';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
    }
}