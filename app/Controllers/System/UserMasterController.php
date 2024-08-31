<?php

namespace App\Controllers\System;

use App\Models\Customer\CustomerMasterModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\System\WorkCardSettingsModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\User\UsersModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Config\Commonutils;
use Config\Validation;
use \CodeIgniter\I18n\Time;

class UserMasterController extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    { {
            $this->db = \Config\Database::connect();
        }
    }
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'customer') {
            $custModel = new CustomerMasterModel();
            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $response['ret_data'] = 'error';
        $usermastermodel = new UsersModel();
        $user_list = $usermastermodel
            ->join('user_roles', 'role_Id=us_role_id')
            ->orderBy('us_id', 'desc')
            ->findAll();
        if ($user_list) {
            $response = [
                'ret_data' => 'success',
                'user_list' => $user_list
            ];
        } else {
            $response['Message'] = 'No data Found';
        }
        return $this->respond($response, 200);
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $usermastermodel = new UsersModel();
        $us_det = $usermastermodel
        ->where('us_id', base64_decode($id))
        ->join('user_roles','role_Id=us_role_id')
        ->first();
        // $encrypter = \Config\Services::encrypter();
        // $builder = $this->db->table('system_datas');
        // $builder->select('encryption_key');
        // $query = $builder->get();
        // $keydata = $query->getRow();
        // $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
        // $user_pass = $commonutils->aes_encryption($org_pass, $us_det['password']);
        if ($us_det) {
            $response = [
                'ret_data' => 'success',
                'us_details' => $us_det
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'Message' => 'No user data found'
            ];
        }

        return $this->respond($response, 200);
    }

    /**
     * Return a new resource object, with default properties
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        //
    }

    /**
     * Return the editable properties of a resource object
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $usermastermodel = new UsersModel();
        
        $rules = [
            'user_data' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $user_data = $this->request->getVar('user_data');
        
            if($user_data->password){
                $encrypter = \Config\Services::encrypter();
                $builder = $this->db->table('system_datas');
                $builder->select('encryption_key');
                $query = $builder->get();
                $keydata = $query->getRow();
                $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
                $user_pass = $commonutils->aes_encryption($org_pass, $user_data->password);
                $updtdata = [
                    'us_firstname' => $user_data->username,
                    'us_email' => $user_data->email,
                    'us_phone' => $user_data->mobilenumber,
                    'us_role_id' => $user_data->userrole,
                    'us_date_of_joining' => $user_data->dateofjoining,
                    'us_password' => $user_pass
    
                ];
            }else{
                
                $updtdata = [
                    'us_firstname' => $user_data->username,
                    'us_email' => $user_data->email,
                    'us_phone' => $user_data->mobilenumber,
                    'us_role_id' => $user_data->userrole,
                    'us_date_of_joining' => $user_data->dateofjoining,
    
                ];
            }
          

            $us_id = $usermastermodel->update($this->request->getVar('us_id'), $updtdata);
           

            $response=$us_id?['ret_data' => 'success','id' => $us_id]:['ret_data' => 'fail'];
        
            return $this->respond($response, 200);
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }


    public function create_user()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $usermastermodel = new UsersModel();
        $rules = [
            'user_data' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $user_data = $this->request->getVar('user_data');

            $encrypter = \Config\Services::encrypter();
            $builder = $this->db->table('system_datas');
            $builder->select('encryption_key');
            $query = $builder->get();
            $keydata = $query->getRow();
            $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
            $user_pass = $commonutils->aes_encryption($org_pass, $user_data->password);


            $insert_data = [
                'us_firstname' => $user_data->username,
                'us_email' => $user_data->email,
                'us_phone' => $user_data->mobilenumber,
                'us_role_id' => $user_data->userrole,
                'us_date_of_joining' => $user_data->dateofjoining,
                'us_password' => base64_encode($user_pass)
            ];
            // return $this->respond($insert_data, 200);

            $us_id = $usermastermodel->insert($insert_data);

            $response=$us_id?['ret_data' => 'success','id' => $us_id]:['ret_data' => 'fail'];
                       
            return $this->respond($response, 200);

    }

    public function get_userdet()
    {


        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->join('user_roles', 'role_Id=us_role_id')->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }

        $usermastermodel = new UsersModel();
        $servicerequestmasterModel = new ServiceRequestMasterModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $workcardsettingsModel = new WorkCardSettingsModel();

        $active_serv = $servicerequestmasterModel
            ->where('serm_vendor_flag', 0)
            ->where('serm_assigne', $tokendata['uid'])
            ->join('status_master', 'sm_id=serm_status')->findAll();
        if ($active_serv) {
            $response = [
                'ret_data' => 'success',
                'us_data' => $users,
                'active_tickets' => $active_serv
            ];
        } else {
            $response['Message'] = 'No data found';
        }
        return $this->respond($response, 200);
    }

    public function us_logout()
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->join('user_roles', 'role_Id=us_role_id')->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $usermastermodel = new UsersModel();
        $currentDatetime = date("Y-m-d H:i:s");
        

        $updt_data = [
            'last_login' => $currentDatetime,
        ];
        $us_id = $usermastermodel->update($tokendata['uid'], $updt_data);
        if ($us_id) {

            $response = [
                'ret_data' => 'success'
            ];
            return $this->respond($response, 200);
        } else {
            return $this->fail("user", 400);
        }
    }


    public function update_admin()
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $usermastermodel = new UsersModel();
        $user_data = $this->request->getVar('user_data');
        if ($user_data) {
        
            $updtdata = [
                'us_firstname' => $user_data->username,
                'us_email' => $user_data->email,
                'us_phone' => $user_data->mobilenumber,
            ];

            $us_id = $usermastermodel->update($tokendata['uid'], $updtdata);
            if ($us_id) {
                $response = [
                    'ret_data' => 'success',
                    'id' => $tokendata['uid']
                ];
            }
        } else {
            $response['Message'] = 'Error!!Failed';
        }
        return $this->respond($response, 200);
    }

    public function edit_profile()
    
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->join('user_roles', 'role_Id=us_role_id')->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $usermastermodel = new UsersModel();
        $currentDatetime = date("Y-m-d H:i:s");


    }


    public function check_password()
    
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $userModel = new UsersModel();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
          
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $usermastermodel = new UsersModel();
        $rules = [
            'password' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $user_data = $this->request->getVar('password');
        if ($user_data) {
            $encrypter = \Config\Services::encrypter();
            $builder = $this->db->table('system_datas');
            $builder->select('encryption_key');
            $query = $builder->get();
            $keydata = $query->getRow();
            $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
            $user_pass = $commonutils->aes_encryption($org_pass, $user_data);
            $insert_data = [
                'us_password' => base64_encode($user_pass)
            ];

            $verify=($users['us_password']==$insert_data['us_password'])?0:1;

        $response=$insert_data?['ret_data'=>'success','data'=>$verify]:['ret_data'=>'fail'];
        return $this->respond($response, 200);
    }
}

}

