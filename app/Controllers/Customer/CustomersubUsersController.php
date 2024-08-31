<?php

namespace App\Controllers\Customer;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Customer\CustomersubUsersModel;
use App\Models\Customer\CustomerActionsModel;
use App\Models\Customer\CustomerFeatureActionsModel;
use App\Models\Customer\CustomerFeatureListModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerRoleMappingModel;
use CodeIgniter\API\ResponseTrait;
use App\Models\Quotation\QuoteDetailsModel;
use App\Models\ServiceRequest\ServiceRequestModel;
use App\Models\ServiceRequest\ServiceRequestDetailsModel;
use App\Models\Customer\CustomerRolesModel;
use App\Models\ServiceRequest\ServiceRequestHistoryModel;
use App\Models\System\CatsalesHistoryModel;
use App\Models\System\CustomerDiscountModel;
use App\Models\User\UsersModel;
use Config\Commonutils;
use Config\Validation;
use CodeIgniter\HTTP\Request;
use App\Models\System\FeatureMappingModel;

class CustomersubUsersController extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index() {}

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        //
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
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'customer') {
            $custModel = new CustomerMasterModel();
            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $customersubUserModel = new CustomersubUsersModel();

        $rules = [
            'csub_name' => 'required',
            'csub_reference' => 'required',
            'csub_password' => 'required',
            'csub_custmasterId' => 'required',
            'csub_email' => 'required',
            
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $encrypter = \Config\Services::encrypter();
        $builder = $this->db->table('system_datas');
        $builder->select('encryption_key');
        $query = $builder->get();
        $keydata = $query->getRow();
        $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
        $user_pass = $commonutils->aes_encryption($org_pass, $this->request->getVar('csub_password'));
        $insertdata = [
            'csub_name' => $this->request->getVar('csub_name'),
            'csub_email' => $this->request->getVar('csub_email'),
            'csub_phone' => $this->request->getVar('csub_phone'),
            'csub_custmasterId' => $this->request->getVar('csub_custmasterId'),
            'csub_password' => $user_pass,
            'csub_reference'=>$this->request->getVar('csub_reference'),
        ];

        $us_id = $customersubUserModel->insert($insertdata);


        $response = $us_id ? ['ret_data' => 'success', 'id' => $us_id,'code'=>'200'] : ['ret_data' => 'fail','code'=>'500'];

        return $this->respond($response, 200);
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
        //
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

    public function get_customersubUsers()
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
        $customersubUserModel = new CustomersubUsersModel();
        $custuser_list = $customersubUserModel->where('csub_delete_flag', 0)->where('csub_custmasterId', $this->request->getVar('customerId'))
            ->join('customer_roles', 'cstr_id=csub_custroleId')
            ->orderBy('csub_id', 'desc')
            ->findAll();

        if (sizeof($custuser_list) > 0) {
            $response = [
                'ret_data' => 'success',
                'cust_list' => $custuser_list
            ];
        } else {
            $response['Message'] = 'No data Found';
        }
        return $this->respond($response, 200);
    }

    public function subuser_login()
    {

        $model = new CustomersubUsersModel();
        $common = new Commonutils();
        $featureroleModel = new FeatureMappingModel();

        $rules = [
            'email' => 'required',
            'password' => 'required',
        ];

        if (!$this->validate($rules))
            return $this->fail($this->validator->getErrors());

        $res = $model->where('csub_email', $this->request->getVar('email'))->first();

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
            $verify = strcmp($aeskey, $res['csub_password']);  // strcmp(base64_encode($aeskey), $res['csub_password']);

            if ($verify == 0) {

                $jwtres['token'] = $common->generate_user_jwt_token($res['csub_id'],"customersub");

                $token = $jwtres['token'];


                $subuserdata = array(
                    "csub_id" => $res['csub_id'],
                    "csub_custmasterId" => $res['csub_custmasterId'],
                    "csub_custroleId" => $res['csub_custroleId'],
                    "csub_name" => $res['csub_name'],
                    "csub_email" => $res['csub_email'],
                    "csub_phone" => $res['csub_phone'],
                    "csub_statusflagId" => $res['csub_statusflagId'],
                    "csub_vendorflagId" => $res['csub_vendorflagId'],
                    "csub_countrycodeId" => $res['csub_countrycodeId'],
                    "csub_fcm_token" => $token
                );
                $data['subuser_details'] = $subuserdata;
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
}
