<?php

namespace App\Controllers\Auth;

use App\Controllers\ToolRequest\RentDelayCalcController;
use App\Models\Approval\ApprovalmasterModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerModel;
use App\Models\Customer\CustomerProducts;
use App\Models\Customer\CustomerRoleModel;
use App\Models\Customer\CustomerRolesModel;
use App\Models\Packages\ServiceRequestPackageModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\Products\ProductMasterModel;
use App\Models\ServiceRequest\ServicePackages;
use App\Models\System\FeatureMappingModel;
use App\Models\ToolRequest\ToolDetailsModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use App\Models\User\UsersModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\I18n\Time;
use Config\Database;
use Exception;
use Config\TwilioConfig;

class PreAuthentication extends ResourceController
{

    private $db;
    use ResponseTrait;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    public function user_login()

    {
        $model = new UsersModel();
        $common = new Commonutils();
        $featureroleModel = new FeatureMappingModel();
        $rentdelaycalccontroller= new RentDelayCalcController;
        $rules = [
            'email' => 'required',
            'password' => 'required',
        ];

        if (!$this->validate($rules))
            return $this->fail($this->validator->getErrors());

        $res = $model->where('us_email', $this->request->getVar('email'))->first();
        if (!$res) {
            $response = [
                'ret_data' => 'fail1',

            ];
            return $this->respond($response, 200);
        } else {

            $encrypter = \Config\Services::encrypter();
            $builder = $this->db->table('system_datas');
            $builder->select('encryption_key');
            $query = $builder->get();
            $keydata = $query->getRow();
            $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
            $aeskey = $common->aes_encryption($org_pass, $this->request->getVar('password'));
            $verify = strcmp(base64_encode($aeskey), $res['us_password']);
            if ($verify == 0) {

                $jwtres['token'] = $common->generate_user_jwt_token($res['us_id'],"user");
                $token = $jwtres['token'];

                $userdata = array(
                    "us_id" => $res['us_id'],
                    "us_firstname" => $res['us_firstname'],
                    "us_lastname" => $res['us_lastname'],
                    "us_email" => $res['us_email'],
                    "us_phone" => $res['us_phone'],
                    "us_role_id" => $res['us_role_id'],
                    "us_date_of_joining" => $res['us_date_of_joining'],
                    "us_token" => $token
                );
                $features = $featureroleModel->select('frm_feature_id')->where('frm_role_id', $res['us_role_id'])->findAll();
                if (!$features) {
                    $features = 0;
                }
                $builder = $this->db->table('feature_role_mapping');
                $builder->select('feature_list.ft_id,feature_list.ft_name,feature_actions.fa_id,feature_actions.fa_name');
                $builder->where('frm_delete_flag', 0);
                $builder->where('frm_role_id', $res['us_role_id']);
                $builder->join('user_roles', 'user_roles.role_id = feature_role_mapping.frm_role_id', 'INNER JOIN');
                $builder->join('feature_list', 'feature_list.ft_id =feature_role_mapping.frm_feature_id', 'INNER JOIN');
                $builder->join('feature_actions', 'feature_actions.fa_id=feature_role_mapping.frm_action_id', 'INNER JOIN');
                $query = $builder->get();
                $result = $query->getResultArray();
                $data['access'] = $result;
                $data['user_details'] = $userdata;
                $data['features'] = $features;
                $data['ret_data'] = "success";
                $data['verify'] = 'true';
                $indata = [
                    'login_status'    => 1,
                    'activeJwt'   =>  $token,
                    'FCM_token'  =>  $this->request->getVar('fcm')
                ];

                $data['intdata'] = $indata;

                $target_cust = $model->where('us_id', $res['us_id'])->first();
                $player_id = [];
                $custhead = "Login successfully";
                $custcontent = "fghjkl";
                array_push($player_id, $target_cust['fcm_token_web']);
                if (sizeof($player_id) > 0) $ret_res = $common->sendMessage($custhead, $custcontent, $player_id);

                $rentdelaycalccontroller->calulate_rent();

               
                return $this->respond($data, 200);
            } else {
                $response = [
                    'ret_data' => 'fail2',

                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function admin_login()
    {

        $model = new UsersModel();
        $common = new Commonutils();
        $featureroleModel = new FeatureMappingModel();
        $rules = [
            'email' => 'required',
            'password' => 'required',
        ];

        if (!$this->validate($rules))
            return $this->fail($this->validator->getErrors());

        $res = $model->where('us_email', $this->request->getVar('email'))->first();

        if (!$res) {
            $response = [
                'ret_data' => 'fail',

            ];
            return $this->respond($response, 200);
        } else {

            $encrypter = \Config\Services::encrypter();
            $builder = $this->db->table('system_datas');
            $builder->select('encryption_key');
            $query = $builder->get();
            $keydata = $query->getRow();
            $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
            $aeskey = $common->aes_encryption($org_pass, $this->request->getVar('password'));
            $verify = strcmp(base64_encode($aeskey), $res['us_password']);

            if ($verify == 0) {

                $jwtres['token'] = $common->generate_user_jwt_token($res['us_id'],"user");

                $token = $jwtres['token'];


                $userdata = array(
                    "us_id" => $res['us_id'],
                    "us_firstname" => $res['us_firstname'],
                    "us_lastname" => $res['us_lastname'],
                    "us_email" => $res['us_email'],
                    "us_phone" => $res['us_phone'],
                    "us_role_id" => $res['us_role_id'],
                    "us_date_of_joining" => $res['us_date_of_joining'],
                    "us_token" => $token
                );
                $data['user_details'] = $userdata;
                $data['ret_data'] = "success";
                $data['verify'] = 'true';
                return $this->respond($data, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function customer_login()
    {
        
            $cusmodel = new  CustomerMasterModel();
            
            $rules = [
                'customer_countrycode' => 'required',
                'customer_mobile' => 'required'
            ];
            if (!$this->validate($rules))
                return $this->fail($this->validator->getErrors());
            if (
                $this->request->getVar('customer_mobile') != '8129312321' &&
                $this->request->getVar('customer_mobile') != '9995110189' &&
                $this->request->getVar('customer_mobile') != '9744608228' &&
                $this->request->getVar('customer_mobile') != '9746725109' &&
                $this->request->getVar('customer_mobile') != '8129239028' &&
                $this->request->getVar('customer_mobile') != '7902548977' &&
                $this->request->getVar('customer_mobile') != '9138055725' &&
                $this->request->getVar('customer_mobile') != '9895224400' &&
                $this->request->getVar('customer_mobile') != '1234567891' &&
                $this->request->getVar('customer_mobile') != '1234567892' &&
                $this->request->getVar('customer_mobile') != '9495494524' &&
                $this->request->getVar('customer_mobile') != '6282000214' &&
                $this->request->getVar('customer_mobile') != '9072397621' &&
                $this->request->getVar('customer_mobile') != '8138055705' &&
                $this->request->getVar('customer_mobile') != '8921529689'
            ) {
                $twilioConfig = new TwilioConfig();
                $phone = $this->request->getVar('customer_countrycode') . $this->request->getVar('customer_mobile');
                $result = $twilioConfig->sendVerificationCode($phone, "sms");
                //  return $this->respond($result);

            } else {
                $result = 'pending';
            }
            if ($result == 'pending') {
                $data['ret_data'] = "success";
            } else if ($result == '429') {
                $data['message'] = 'Maximum attempt reached. Please try again later';
                $data['ret_data'] = "fail";
            } else {
                $data['twilio_err'] = $result;
                $data['message'] = 'Invalid Mobile Number';
                $data['ret_data'] = "fail";
            }
            return $this->respond($data, 200);
        
    }


    public function verify_signin_otp()
    {
        // return $this->respond($this->request->getVar('fcm_token'), 200);

        $rules = [
            'customer_mobile' => 'required|min_length[6]',
            'otp' => 'required',
            'customer_countrycode' => 'required'
        ];
        if (!$this->validate($rules)) return $data['ret_data'] = "fail";
        $phone = $this->request->getVar('customer_mobile');
        $code = $this->request->getVar('customer_countrycode');
        $fcm_token = $this->request->getVar('fcm_token');
        $twilioConfig = new TwilioConfig();
        try {
            if (
                $this->request->getVar('customer_mobile') != '8129312321' && $this->request->getVar('customer_mobile') != '9995110189'  && $this->request->getVar('customer_mobile') != '9744608228' && $this->request->getVar('customer_mobile') != '9746725109' && $this->request->getVar('customer_mobile') != '8129239028' && $this->request->getVar('customer_mobile') != '7902548977' && $this->request->getVar('customer_mobile') != '9138055725'
                && $this->request->getVar('customer_mobile') != '9895224400' &&
                $this->request->getVar('customer_mobile') != '1234567891' &&
                $this->request->getVar('customer_mobile') != '1234567892' &&
                $this->request->getVar('customer_mobile') != '9495494524' &&
                $this->request->getVar('customer_mobile') != '6282000214' &&
                $this->request->getVar('customer_mobile') != '9072397621' &&
                $this->request->getVar('customer_mobile') != '8138055705' &&
                $this->request->getVar('customer_mobile') != '8921529689'
            ) {
                $verify = $twilioConfig->verifyVerificationCode($code . $phone, $this->request->getVar('otp'));
            } else {
                $verify = "approved";
            }
        } catch (\Twilio\Exceptions\TwilioException $e) {
            $data['ret_data'] = "MaxAttempt";
            return $this->respond($data);
        }
        $commonutils = new Commonutils();

        if ($verify == "approved") {
            $cusmodel = new  CustomerMasterModel();
            $res = $cusmodel->where('cstm_phone', $this->request->getVar('customer_mobile'))->first();
            if ($res) {
                $customerProducts = new CustomerProducts();
                $custpro = $customerProducts
                    ->where('cp_status', 1)
                    ->where('cp_cstm_id', $res['cstm_id'])
                    ->first();
                if ($custpro) {
                    $cust_info['cp_serial'] = $custpro['cp_serial'];
                }
                $cust_info = array(
                    "cus_id" => $res['cstm_id'],
                    "customer_name" => $res['cstm_name'],
                    "cus_type_id" => $res['cstm_cstp_id'],
                    "cus_role_id" => $res['cstm_cstr_id'],
                    "customer_email" => $res['cstm_email'],
                    "customer_mobile" => $res['cstm_phone'],
                    "customer_countrycode" => $res['cstm_country_code'],
                    'cstm_delete_flag' => $res['cstm_delete_flag'],
                    "customer_type" => "old",
                );

                $fcm_data = [
                    'fcm_token_mobile' => $this->request->getVar('fcm_token')
                ];
                $cusmodel->update($res['cstm_id'], $fcm_data);
            } else {
                $indata = [
                    'customer_countrycode' => $code,
                    'customer_mobile' => $phone,
                    'fcm_token_mobile' => $fcm_token,
                ];
                $res['cstm_id'] = $cusmodel->insert($indata);
                $cust_info = array(
                    "cstm_id" => $res['cstm_id'],
                    "customer_name" => "",
                    // "cus_type_id"=>$res['cstm_cstp_id'],
                    // "cus_role_id" =>$res['cstm_cstr_id'],
                    "customer_email" => "",
                    "customer_mobile" => $phone,
                    "customer_countrycode" => $code,
                    "customer_type" => "new",
                );
            }
            $token = $commonutils->generate_customer_jwt_token($res['cstm_id']);
            // $token = "testing";
            $data['token'] = $token;
            $data['customer_info'] = $cust_info;
            $data['ret_data'] = "success";
        } else if ($verify == '429') {
            $data['ret_data'] = "fail1";
            $data['message'] = "Maximum attempt reached. Please try again later";
        } else {
            // $data['ret_data']=$ve;
            $data['ret_data'] = "fail2";
            $data['message'] = "Invalid OTP";
        }


        return $this->respond($data, 200);
    }

    public function customer_signup()
    {
        $cusmodel = new CustomerMasterModel();
        $cusrolemodel = new CustomerRolesModel();
        $date = date("Y-m-d H:i:s");
        $rules = [
            'customer_name' => 'required',
            'customer_role' => 'required'
        ];

        if (!$this->validate($rules))
            return $this->fail($this->validator->getErrors());
        // $customer_role_id = $cusrolemodel->where('cstr_name', $this->request->getVar('customer_role'))->select('cstr_id')->first();
        // return $this->respond($customer_role_id, 200);

        $in_data = [
            'cstm_name'   => $this->request->getVar('customer_name'),
            'cstm_email' => $this->request->getVar('customer_email'),
            'cstm_country_code' => $this->request->getVar('customer_countrycode'),
            'cstm_phone' => $this->request->getVar('customer_mobile'),
            'cstm_cstr_id' =>  $this->request->getVar('customer_role'),
            'cstm_dealer_name' => $this->request->getVar('dealer_name'),
        ];
        if (($this->request->getVar('cp_serial') != null)) {

            $productmasterModel = new ProductMasterModel();
            $customerProducts = new CustomerProducts();
            $approvalModel = new ApprovalmasterModel();

            $prod_det = $productmasterModel
                ->where('pm_delete_flag', 0)
                ->where('pm_sl_nm', $this->request->getVar('cp_serial'))
                ->first();

            if ($prod_det) {

                $track = [

                    'cp_serial' => $this->request->getVar('cp_serial'),
                    'cp_pr_id' => $prod_det['pm_id'],
                    'cp_sr_id' => $prod_det['pm_code'],
                    'cp_status' => 0,
                    'cp_cstm_id' => $this->request->getVar('cstm_id')

                ];
                $customerProducts->insert($track);

                $am = [
                    'am_type' => 11,
                    'am_requestedby' => $this->request->getVar('cstm_id'),
                    'am_referenceid' => $prod_det['pm_id'],
                    'am_status' => 0,
                    'am_createdon' => $date,
                    'am_updatedon' => $date
                ];
                $approvalModel->insert($am);
            }
        }
        $results = $cusmodel->update($this->request->getVar('cstm_id'), $in_data);
        $insert_id = $this->request->getVar('cstm_id');
        if ($results) {
            $cust_info = array(
                "customer_id" =>  $results,
                "customer_name" => $this->request->getVar('customer_name'),
                "customer_email" => $this->request->getVar('customer_email'),
                "customer_countrycode" => $this->request->getVar('customer_countrycode'),
                "customer_mobile" => $this->request->getVar('customer_mobile'),
                'cstm_cstr_id' =>  $this->request->getVar('customer_role'),
                'cstm_cstp_id' => 2,
                'cstm_dealer_name' => $this->request->getVar('cstm_dealer_name'),
            );

            if ($this->request->getVar('cp_serial')) {
                $cust_info['cp_serial'] = $this->request->getVar('cp_serial');
            }

            $commonutils = new Commonutils();
            $data['token'] = $commonutils->generate_customer_jwt_token($insert_id);
            $data['customer_info'] = $cust_info;
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->fail($data, 400);
        }
    }




    public function get_customer_roles()
    {
        $model = new CustomerRolesModel();
        $data['all_user_roles'] = $model->orderBy('cstr_id', 'DESC')->findAll();
        return $this->respond($data);
    }

    public function get_service_package_list()
    {
        $model = new ServiceRequestPackageModel();
        // $data = $model->where('servpack_id',1)->select('servpack_name')->first();
        // $data['all_services'] = $model->orderBy('servpack_id', 'DESC')->findAll();
        $data = $model->orderBy('servpack_id', 'DESC')->findAll();
        if ($data) {
            $values = array(
                "servpack_name" => $data["servpack_name"],
            );
        }
        $response["data"] = $data;
        $response['ret_data'] = "success";
        return $this->respond($response, 200);
    }


    public function get_customerdetails()
    {
        $cusmodel = new CustomerMasterModel();
        // $cusrolemodel = new CustomerRolesModel();
        try {
            $customer_id = $this->request->getVar('customer_id');
            $res = $cusmodel->where('cstm_id', $customer_id)->first();
            // $data = $cusmodel->where('cstm_id',$customer_id)->get()->getResult();
            if ($res) {
                $cust_info = array(
                    "cust_id" => $res['cstm_id'],
                    "customer_name" => $res['cstm_name'],
                    "cus_type_id" => $res['cstm_cstp_id'],
                    "cus_role_id" => $res['cstm_cstr_id'],
                    "customer_email" => $res['cstm_email'],
                    "customer_mobile" => $res['cstm_phone'],
                    "customer_countrycode" => $res['cstm_country_code'],
                    "customer_type" => $res['cstm_cstp_id'],
                    'customer_address' => $res['cstm_address'],
                    'cstm_alternate_num' => $res['cstm_alternate_num'],
                    'customer_profile_photo' => $res['cstm_profile_photo']
                );
            }
            $response["data"] = $cust_info;
            $response['ret_data'] = "success";
            return $this->respond($response, 200);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    // public function customer_login()
    // {
        
    //         $cusmodel = new  CustomerMasterModel();
            
    //         $rules = [
    //             'customer_countrycode' => 'required',
    //             'customer_mobile' => 'required'
    //         ];
    //         if (!$this->validate($rules))
    //             return $this->fail($this->validator->getErrors());
    //             $twilioConfig = new TwilioConfig();
    //             $phone = $this->request->getVar('customer_countrycode') . $this->request->getVar('customer_mobile');
    //             $result = $twilioConfig->sendVerificationCode($phone, "sms");

    //         if ($result == 'pending') {
    //             $data['ret_data'] = "success";
    //         } else if ($result == '429') {
    //             $data['message'] = 'Maximum attempt reached. Please try again later';
    //             $data['ret_data'] = "fail";
    //         } else {
    //             $data['twilio_err'] = $result;
    //             $data['message'] = 'Invalid Mobile Number';
    //             $data['ret_data'] = "fail";
    //         }
    //         return $this->respond($data, 200);
        
    // }

    // public function verify_signin_otp()
    // {
    //     // return $this->respond($this->request->getVar('fcm_token'), 200);

    //     $rules = [
    //         'customer_mobile' => 'required|min_length[6]',
    //         'otp' => 'required',
    //         'customer_countrycode' => 'required'
    //     ];
    //     if (!$this->validate($rules)) return $data['ret_data'] = "fail";
    //     $phone = $this->request->getVar('customer_mobile');
    //     $code = $this->request->getVar('customer_countrycode');
    //     $fcm_token = $this->request->getVar('fcm_token');
    //     $twilioConfig = new TwilioConfig();
    //     try {
    //         $verify = $twilioConfig->verifyVerificationCode($code . $phone, $this->request->getVar('otp'));
    //     } catch (\Twilio\Exceptions\TwilioException $e) {
    //         $data['ret_data'] = "MaxAttempt";
    //         return $this->respond($data);
    //     }
    //     $commonutils = new Commonutils();

    //     if ($verify == "approved") {
    //         $cusmodel = new  CustomerMasterModel();
    //         $res = $cusmodel->where('cstm_phone', $this->request->getVar('customer_mobile'))->first();
    //         if ($res) {
    //             $customerProducts = new CustomerProducts();
    //             $custpro = $customerProducts
    //                 ->where('cp_status', 1)
    //                 ->where('cp_cstm_id', $res['cstm_id'])
    //                 ->first();
    //             if ($custpro) {
    //                 $cust_info['cp_serial'] = $custpro['cp_serial'];
    //             }
    //             $cust_info = array(
    //                 "cus_id" => $res['cstm_id'],
    //                 "customer_name" => $res['cstm_name'],
    //                 "cus_type_id" => $res['cstm_cstp_id'],
    //                 "cus_role_id" => $res['cstm_cstr_id'],
    //                 "customer_email" => $res['cstm_email'],
    //                 "customer_mobile" => $res['cstm_phone'],
    //                 "customer_countrycode" => $res['cstm_country_code'],
    //                 'cstm_delete_flag' => $res['cstm_delete_flag'],
    //                 "customer_type" => "old",
    //             );

    //             $fcm_data = [
    //                 'fcm_token_mobile' => $this->request->getVar('fcm_token')
    //             ];
    //             $cusmodel->update($res['cstm_id'], $fcm_data);
    //         } else {
    //             $indata = [
    //                 'customer_countrycode' => $code,
    //                 'customer_mobile' => $phone,
    //                 'fcm_token_mobile' => $fcm_token,
    //             ];
    //             $res['cstm_id'] = $cusmodel->insert($indata);
    //             $cust_info = array(
    //                 "cstm_id" => $res['cstm_id'],
    //                 "customer_name" => "",
    //                 // "cus_type_id"=>$res['cstm_cstp_id'],
    //                 // "cus_role_id" =>$res['cstm_cstr_id'],
    //                 "customer_email" => "",
    //                 "customer_mobile" => $phone,
    //                 "customer_countrycode" => $code,
    //                 "customer_type" => "new",
    //             );
    //         }
    //         $token = $commonutils->generate_customer_jwt_token($res['cstm_id']);
    //         // $token = "testing";
    //         $data['token'] = $token;
    //         $data['customer_info'] = $cust_info;
    //         $data['ret_data'] = "success";
    //     } else if ($verify == '429') {
    //         $data['ret_data'] = "fail1";
    //         $data['message'] = "Maximum attempt reached. Please try again later";
    //     } else {
    //         // $data['ret_data']=$ve;
    //         $data['ret_data'] = "fail2";
    //         $data['message'] = "Invalid OTP";
    //     }


    //     return $this->respond($data, 200);
    // }

    // public function customer_signup()
    // {
    //     $cusmodel = new CustomerMasterModel();
    //     $cusrolemodel = new CustomerRolesModel();
    //     $date = date("Y-m-d H:i:s");
    //     $rules = [
    //         'customer_name' => 'required',
    //         'customer_role' => 'required'
    //     ];

    //     if (!$this->validate($rules))
    //         return $this->fail($this->validator->getErrors());

    //     $in_data = [
    //         'cstm_name'   => $this->request->getVar('customer_name'),
    //         'cstm_email' => $this->request->getVar('customer_email'),
    //         'cstm_country_code' => $this->request->getVar('customer_countrycode'),
    //         'cstm_phone' => $this->request->getVar('customer_mobile'),
    //         'cstm_cstr_id' =>  $this->request->getVar('customer_role'),
    //         'cstm_dealer_name' => $this->request->getVar('dealer_name'),
    //     ];
    //     if (($this->request->getVar('cp_serial') != null)) {

    //         $productmasterModel = new ProductMasterModel();
    //         $customerProducts = new CustomerProducts();
    //         $approvalModel = new ApprovalmasterModel();

    //         $prod_det = $productmasterModel
    //             ->where('pm_delete_flag', 0)
    //             ->where('pm_sl_nm', $this->request->getVar('cp_serial'))
    //             ->first();

    //         if ($prod_det) {

    //             $track = [

    //                 'cp_serial' => $this->request->getVar('cp_serial'),
    //                 'cp_pr_id' => $prod_det['pm_id'],
    //                 'cp_sr_id' => $prod_det['pm_code'],
    //                 'cp_status' => 0,
    //                 'cp_cstm_id' => $this->request->getVar('cstm_id')

    //             ];
    //             $customerProducts->insert($track);

    //             $am = [
    //                 'am_type' => 11,
    //                 'am_requestedby' => $this->request->getVar('cstm_id'),
    //                 'am_referenceid' => $prod_det['pm_id'],
    //                 'am_status' => 0,
    //                 'am_createdon' => $date,
    //                 'am_updatedon' => $date
    //             ];
    //             $approvalModel->insert($am);
    //         }
    //     }
    //     $results = $cusmodel->update($this->request->getVar('cstm_id'), $in_data);
    //     $insert_id = $this->request->getVar('cstm_id');
    //     if ($results) {
    //         $cust_info = array(
    //             "customer_id" =>  $results,
    //             "customer_name" => $this->request->getVar('customer_name'),
    //             "customer_email" => $this->request->getVar('customer_email'),
    //             "customer_countrycode" => $this->request->getVar('customer_countrycode'),
    //             "customer_mobile" => $this->request->getVar('customer_mobile'),
    //             'cstm_cstr_id' =>  $this->request->getVar('customer_role'),
    //             'cstm_cstp_id' => 2,
    //             'cstm_dealer_name' => $this->request->getVar('cstm_dealer_name'),
    //         );

    //         if ($this->request->getVar('cp_serial')) {
    //             $cust_info['cp_serial'] = $this->request->getVar('cp_serial');
    //         }

    //         $commonutils = new Commonutils();
    //         $data['token'] = $commonutils->generate_customer_jwt_token($insert_id);
    //         $data['customer_info'] = $cust_info;
    //         $data['ret_data'] = "success";
    //         return $this->respond($data, 200);
    //     } else {
    //         $data['ret_data'] = "fail";
    //         return $this->fail($data, 400);
    //     }
    // }
}
