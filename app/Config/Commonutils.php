<?php
namespace Config;
use CodeIgniter\Config\BaseConfig;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

use App\Models\ServiceRequest\ServiceRequestHistoryModel;
use App\Models\ServiceRequest\ServiceRequestItemsModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\Customer\CustomerMasterModel;


class Commonutils extends BaseConfig
{
    public function generate_customer_jwt_token($customer_id)
    {

        $myconfig = new DSConfig;
        $key = getenv('JWT_SECRET');
        $iat = time(); // current timestamp value
        $payload = array(
            "iss" => $myconfig->name,
            "aud" => "customer",
            "sub" => "Customer verification",
            "iat" => $iat, //Time the JWT issued at
            "uid" => $customer_id,
        );
        $token = JWT::encode($payload, $key,'HS256');
        return $token;
    }

    public function decode_jwt_token($token)
    {
        
        $key = getenv('JWT_SECRET');
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $token_data = array(
            "uid" => $decoded->uid,
            "aud" => $decoded->aud
        );

        return $token_data;
    }

    public function generate_user_jwt_token($user_id,$type)
    {

        $myconfig = new DSConfig;
        $key = getenv('JWT_SECRET');
        $iat = time(); // current timestamp value
        $payload = array(
            "iss" => $myconfig->name,
            "aud" => $type,
            "sub" => "User verification",
            "iat" => $iat, //Time the JWT issued at
            "uid" => $user_id,
        );
        $token = JWT::encode($payload, $key,'HS256');
        return $token;
    }

    public function aes_encryption($key, $data)
    {
        $cipher = "AES-256-CBC";
        $encrypted_data = openssl_encrypt($data, $cipher, $key, 0, 'alm12digitalsoft');
        return $encrypted_data;
    }


    // public function image_upload($file, $filename, $folder, $filetype, $file_flag)
    // {
    //     $awss3 = new \Aws\S3\S3Client(
    //         [
    //             'version' => 'latest',
    //             'region'  => 'me-central-1',
    //             'credentials' => [
    //                 'key'    => getenv('S3_BUCKET_KEY'),
    //                 'secret' => getenv('S3_BUCKET_SECRET')
    //             ]
    //         ]
    //     );
    //     try {
    //         if ($file_flag) {
    //             $result = $awss3->putObject([
    //                 'Bucket' => 'autoversa-media',
    //                 'Key'    => $folder . $filename,
    //                 'SourceFile'   => $file,
    //                 'ACL'    => 'public-read',
    //                 'ContentType' => $filetype // make file 'public'
    //             ]);
    //         } else {
    //             $result = $awss3->putObject([
    //                 'Bucket' => 'autoversa-media',
    //                 'Key'    => $folder . $filename,
    //                 'Body'   => $file,
    //                 'ACL'    => 'public-read',
    //                 'ContentType' => $filetype // make file 'public'
    //             ]);
    //         }

    //         return $result;
    //     } catch (\Aws\S3\Exception\S3Exception $e) {
    //         //$msg = 'File has been uploaded';
    //         echo $e->getMessage();
    //         return $e->getMessage();
    //     }
    // }
    // function sendMessage($heading,$content,$player_ids)
    // {
    //     $fields = array(
    //         'app_id' => getenv('ONESIGNAL_APP_ID'),
    //         'include_player_ids' => $player_ids,
    //         'contents' => array("en" =>$content),
    //         'headings' => array("en" => $heading),
    //     );

    //     $fields = json_encode($fields);
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //         'Content-Type: application/json; charset=utf-8',
    //         'Authorization: Basic ' . getenv('ONESIGNAL_API_KEY')
    //     ));
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    //     curl_setopt($ch, CURLOPT_HEADER, FALSE);
    //     curl_setopt($ch, CURLOPT_POST, TRUE);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    //     $response = curl_exec($ch);
    //     curl_close($ch);

    //     return $response;
    // }

    // function getNMSpares($inv_no,$branchcode)
    // {
    //     $fields = array(
    //         'inv_no' => $inv_no,
    //         'branchcode' =>  $branchcode,
    //     );

    //     $fields = json_encode($fields);
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //         'Content-Type: application/json; charset=utf-8'
    //     ));
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    //     curl_setopt($ch, CURLOPT_HEADER, FALSE);
    //     curl_setopt($ch, CURLOPT_POST, TRUE);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    //     $response = curl_exec($ch);
    //     curl_close($ch);

    //     return $response;
    // }





      // --------------------------------------------------------------------
      function sendMessage($heading, $content, $player_ids)
    {
        $fields = array(
            'app_id' => getenv('ONESIGNAL_APP_ID'),
            'include_player_ids' => $player_ids,
            'contents' => array("en" => $content),
            'headings' => array("en" => $heading),
        );

        $fields = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . getenv('ONESIGNAL_API_KEY')
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function genearate_key($data,$salt){


        $hash_data=hash('sha512',
        ($data['key'].'|'.
        $data['txnid'].'|'.
        $data['amount'].'|'.
        $data['productinfo'].'|'.
        $data['firstname'].'|'.
        $data['email'].'|'.
        $data['udf1'].'|'.
        $data['udf2'].'|'.
        $data['udf3'].'|'.
        $data['udf4'].'|'.
        $data['udf5'].'|'.
        $data['udf6'].'|'.
        $data['udf7'].'|'.
        $data['udf8'].'|'.
        $data['udf9'].'|'.
        $data['udf10'].'|'.
        $salt
    ));
        return $hash_data;

    }

    public function image_upload($file, $filename, $folder, $filetype, $file_flag)
    {
        $awss3 = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => getenv('AWS_REGION'),
            'credentials' => [
                'key'    => getenv('AWS_ACCESS_KEY'),
                'secret' => getenv('AWS_SECRET_KEY')
            ]
        ]);
    
        try {
            if ($file_flag) {
                $result = $awss3->putObject([
                    'Bucket' => getenv('AWS_BUCKET'),
                    'Key'    => $folder . '/' . $filename,
                    'SourceFile' => $file,
                    'ACL'    => 'public-read',
                    'ContentType' => $filetype
                ]);
            } else {
                $result = $awss3->putObject([
                    'Bucket' => getenv('AWS_BUCKET'),
                    'Key'    => $folder . '/' . $filename,
                    'Body'   => $file,
                    'ACL'    => 'public-read',
                    'ContentType' => $filetype
                ]);
            }
    
            
            $objectUrl = $result->get('@metadata')['effectiveUri'];
    
            return [
                'CSP' => [
                    'URL' => $objectUrl,
                    'Bucket' => getenv('AWS_BUCKET'),
                    'Key' => $folder . '/' . $filename,
                    'Result' => $result
                ]
            ];
        } catch (\Aws\S3\Exception\S3Exception $e) {
            return [
                'CSP' => [
                    'Error' => $e->getMessage()
                ]
            ];
        }
    }


    public function start_work($serm_id, $tokendata)
    {

        $workcardItemsModel = new ServiceRequestItemsModel();
        $custmodel = new CustomerMasterModel();
        $commonutils = new Commonutils();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $workcardModel = new ServiceRequestMasterModel();
        $date = date("Y-m-d H:i:s");
        $inData = [
            'serm_id' => $serm_id,
            'serm_status'   => 28,
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']

        ];
        $inser_hist = [
            'srh_status_id' => 28,
            'srh_serm_id' => $serm_id,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];
        $items = $workcardItemsModel->where('sitem_serid', $serm_id)->findAll();
        for ($i = 0; $i < sizeof($items); $i++) {
            $itemsupd[$i] = [
                'sitem_createdby' => $tokendata['uid'],
                'sitem_updatedby' => $tokendata['uid'],
                'sitem_active_flag' => 1,
                'sitem_updatedby' =>  $tokendata['uid'],
                'sitem_updatedon' => $date
            ];
            $workcardItemsModel->update($items[$i]['sitem_id'], $itemsupd[$i]);
        }

        $servicehistoryModel->insert($inser_hist);
        $result = $workcardModel->update($serm_id, $inData);

        $serm_det = $workcardModel->select('serm_custid,serm_number')->where('serm_id', $serm_id)->first();
        $target_cust = $custmodel->where('cstm_id', $serm_det['serm_custid'])->first();
        $player_id = [];
        $custhead = "Work Started";
        $custcontent = "Work Started against " . $serm_det['serm_number'] . ". Tap to see";

        array_push($player_id, $target_cust['fcm_token_mobile']);
        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

        return $result;
    }
    


}
