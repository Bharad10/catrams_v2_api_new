<?php

namespace Config;

require_once   ROOTPATH .'/Twilio/autoload.php';

use CodeIgniter\Config\BaseConfig;
use Twilio\Rest\Client;


class TwilioConfig extends BaseConfig
{
    public function sendVerificationCode($phone,$type){
        $twilio = new Client(getenv("TWILIO_ACCOUNT_SID"),getenv("TWILIO_AUTH_TOKEN"));
        
        try{
            $verification = $twilio->verify->v2->services(getenv("TWILIO_SERVICE_SID"))
            ->verifications
            ->create($phone, $type, ["locale" => "en"]);
            return $verification->status;
        }catch(\Twilio\Exceptions\RestException $e){
            return $e->getStatusCode();
        } 
    }

    public function verifyVerificationCode($phone,$otp){
        $twilio = new Client(getenv("TWILIO_ACCOUNT_SID"),getenv("TWILIO_AUTH_TOKEN"));
        $verification_check = $twilio->verify->v2->services(getenv("TWILIO_SERVICE_SID"))
        ->verificationChecks
        ->create($otp, // code
                ["to" => $phone]
        );
        return $verification_check->status;
    }
}