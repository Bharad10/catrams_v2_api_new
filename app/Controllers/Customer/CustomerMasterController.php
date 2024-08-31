<?php

namespace App\Controllers\Customer;

use App\Models\Approval\ApprovalmasterModel;
use App\Models\Customer\CustomerActionsMappingModel;
use App\Models\Customer\CustomerDataCardModel;
use App\Models\Customer\CustomerItemsModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerProducts;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Quotation\QuoteDetailsModel;
use App\Models\ServiceRequest\ServiceRequestModel;
use App\Models\ServiceRequest\ServiceRequestDetailsModel;
use App\Models\Customer\CustomerRolesModel;
use App\Models\Customer\CustomerTypeModel;
use App\Models\Customer\CustomerVehicleModel;
use App\Models\Products\ProductMasterModel;
use App\Models\ServiceRequest\ServiceRequestHistoryModel;
use App\Models\System\CatsalesHistoryModel;
use App\Models\System\CustomerDiscountModel;
use App\Models\User\UsersModel;
use Config\Commonutils;
use Config\Validation;
use CodeIgniter\HTTP\Request;

class CustomerMasterController extends ResourceController
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
        $customermastermodel = new CustomerMasterModel();
        $cust_list = $customermastermodel->where('cstm_delete_flag', 0)
            ->join('customer_roles', 'cstr_id=cstm_cstr_id')
            ->orderBy('cstm_id', 'desc')
            ->findAll();

        if ($cust_list) {
            $response = [
                'ret_data' => 'success',
                'cust_list' => $cust_list
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
        $cusmodel = new CustomerMasterModel();
        $catsalesModel = new CatsalesHistoryModel();
        $productMaster = new ProductMasterModel();
        $customervehiclesModel= new CustomerVehicleModel();
        $customerdiscountModel= new CustomerDiscountModel();

        $cust_in = $cusmodel->where('cstm_id', base64_decode($id))
            ->join('customer_roles', 'cstr_id=cstm_cstr_id')
            ->join('customer_products', 'cp_cstm_id=cstm_id', 'left')
            ->first();
            $cust_in['customer_dicounts']=$cust_in['cstm_type']==1?$customerdiscountModel->where('cd_active_flag',0)
            // ->where('cd_request_type',[1,3])
            ->first():[];

        if ($cust_in['cp_serial'] != null) {
            $product_det = $productMaster->where('pm_sl_nm', $cust_in['cp_serial'])->first();
            $cust_info = $product_det ? array_merge($cust_in, $product_det) : $cust_in;

            // if($product_det){
            //     $cust_info= array_merge($cust_in,$product_det);
            // }else{
            //     $cust_info=$cust_in;
            // }
        } else {
            $cust_info = $cust_in;
        }

        $sales_history = $cust_info ? $catsalesModel->where('csh_gstin', $cust_info['cstm_gstin'])->findAll() : [];
        $cust_veh = $cust_info ? $customervehiclesModel
        ->where('custveh_cust_id', $cust_info['cstm_id'])
        ->join('cat_vehicle_data', 'id=customer_vehicle.custveh_veh_id')
        ->findAll() : [];

        if(sizeof($cust_veh)>0){
            $datacardModel=new CustomerDataCardModel();
            for($i=0;$i<sizeof($cust_veh);$i++){
                $cust_veh[$i]['datacards']=$datacardModel->where('cvehcard_delete_flag',0)->where('cvehcard_custveh_id',$cust_veh[$i]['custveh_id'])->findAll();
            }
        }
        if ($cust_info) {
            $response["data"] = $cust_info;
            $response["sales_data"] = $sales_history;
            $response["cust_veh"] = $cust_veh;
            $response['ret_data'] = "success";
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            return $this->respond($response, 200);
        }
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
        $date = date("Y-m-d H:i:s");
        $customermastermodel = new CustomerMasterModel();
        $insertdata = [
            'cstm_name' => $this->request->getVar('customername'),
            'cstm_email' => $this->request->getVar('email'),
            'cstm_phone' => $this->request->getVar('customerphone'),
            'cstm_alternate_num' => $this->request->getVar('alternatenumber'),
            'cstm_address' => $this->request->getVar('customeraddress'),
            'cstm_gstin' => $this->request->getVar('gstin'),
            'cstm_cstr_id' => $this->request->getVar('custtype'),
            'cstm_created_by' => $tokendata['uid'],
            'cstm_created_on' => $date,
            'cstm_updated_by' => $tokendata['uid'],
            'cstm_updated_on' => $date,
        ];
        $us_id = $customermastermodel->insert($insertdata);
        if ($us_id) {
            $response = [
                'ret_data' => 'success'
            ];
        }
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


        $response['ret_data'] = 'success';
        $customermastermodel = new CustomerMasterModel();
        $date = date("Y-m-d H:i:s");

        $updtdata = [
            'cstm_name' => $this->request->getVar('customername'),
            'cstm_profile_photo' => $this->request->getVar('cust_profile_pic'),
            'cstm_phone' => $this->request->getVar('cust_mobile'),
            'cstm_email' => $this->request->getVar('cstm_email'),
            'cstm_alternate_num' => $this->request->getVar('cstm_alternate_num'),
            'cstm_updated_by' => $tokendata['uid'],
            'cstm_updated_on' => $date,
        ];
        $us_id = $customermastermodel->update($this->request->getVar('cust_id'), $updtdata);
        if ($us_id) {
            $response = [
                'ret_data' => 'success',
                'id' => $us_id
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'Message' => 'Didnt Update'
            ];
        }
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
    public function cust_quote_view()
    {
        $response['ret_data'] = 'error';
    }
    public function get_customer_roles()
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
        $model = new CustomerRolesModel();
        $data['all_user_roles'] = $model->orderBy('cstr_id', 'DESC')->findAll();
        return $this->respond($data);
    }


    public function fetch_cust_list()
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
        $customermastermodel = new CustomerMasterModel();
        $user_list = $customermastermodel->findAll();
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
    public function create_customer()
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
        $date = date("Y-m-d H:i:s");

        $response['ret_data'] = 'error';
        $customermastermodel = new CustomerMasterModel();
        $user_data = $this->request->getVar('custdata');
        if ($user_data) {
            $insert_data = [
                'cstm_name' => $user_data->customername,
                'cstm_email' => $user_data->email,
                'cstm_phone' => $user_data->customerphone,
                'cstm_cstp_id' => $user_data->custtype,
                'cstm_cstr_id' => 1,
                'cstm_created_by' => $tokendata['uid'],
                'cstm_created_on' => $date,
                'cstm_updated_by' => $tokendata['uid'],
                'cstm_updated_on' => $date,
            ];

            $us_id = $customermastermodel->insert($insert_data);
            if ($us_id) {
                $response = [
                    'ret_data' => 'success',
                    'id' => $us_id
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response['Message'] = 'error';
        }
        return $this->respond($response, 200);
    }
    public function update_customer()
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
        $date = date("Y-m-d H:i:s");
        $response['ret_data'] = 'error';
        $customermastermodel = new CustomerMasterModel();
        $user_data = $this->request->getVar('user_data');
        if ($user_data) {
            $updtdata = [
                'cstm_name' => $user_data->customername,
                'cstm_email' => $user_data->email,
                'cstm_phone' => $user_data->customerphone,
                'cstm_cstp_id' => $user_data->custtype,
                'cstm_updated_by' => $tokendata['uid'],
                'cstm_updated_on' => $date,
            ];
            $us_id = $customermastermodel->update($this->db->escapeString($user_data['cust_id']), $updtdata);
            if ($us_id) {
                $response = [
                    'ret_data' => 'success',
                    'id' => $us_id
                ];
            }
        } else {
            $response['Message'] = 'error';
        }
        return $this->respond($response, 200);
    }

    public function delete_cust()
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
        $date = date("Y-m-d H:i:s");
        $customermastermodel = new CustomerMasterModel();
        $updt_data = [
            'cstm_delete_flag' => 1,
            'cstm_updated_by' => $tokendata['uid'],
            'cstm_updated_on' => $date,

        ];
        $us_id = $customermastermodel->update($this->db->escapeString($this->request->getVar('user_data')), $updt_data);
    }

    // public function update_customer_by_mobile()
    // {
    //     $validModel = new Validation();
    //     $commonutils = new Commonutils();
    //     $heddata = $this->request->headers();
    //     $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
    //     if ($tokendata['aud'] == 'customer') {
    //         $custModel = new CustomerMasterModel();
    //         $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
    //         if (!$customer) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $userModel = new UsersModel();
    //         $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
    //         if (!$users) return $this->fail("invalid user", 400);
    //     } else {
    //         return $this->fail("invalid user", 400);
    //     }


    //     $response['ret_data'] = 'success';
    //     $customermastermodel = new CustomerMasterModel();
    //     $updtdata = [
    //         'cstm_name' => $this->request->getVar('customername'),
    //         'cstm_profile_photo' => $this->$this->request->getVar('cust_profile_pic'),
    //         'cstm_phone' => $this->$this->request->getVar('cust_mobile'),
    //         'cstm_email' => $this->$this->request->getVar('cstm_email'),
    //         'cstm_alternate_num' => $this->$this->request->getVar('cstm_alternate_num'),
    //     ];
    //     $us_id = $customermastermodel->update($this->request->getVar('cust_id'), $updtdata);
    //     if ($us_id) {
    //         $response = [
    //             'ret_data' => 'success',
    //             'id' => $us_id
    //         ];
    //     } else {
    //         $response = [
    //             'ret_data' => 'success',
    //             'Message' => 'Didnt Update'
    //         ];
    //     }
    //     return $this->respond($response, 200);
    // }

    public function profilpic_upload()
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

        helper(['form', 'url']);
        $customermastermodel = new CustomerMasterModel();

        $imageFile = $this->request->getFile('customerImage');
        $date = date("Y-m-d H:i:s");


        $imageFile->move(ROOTPATH . 'public/uploads/customer_profilepics');
        $data = [
            'img_name' => $imageFile->getName(),
            'file'  => $imageFile->getClientMimeType(),
            'path' => ROOTPATH,
            'test' => $this->request->getVar("test_data"),
        ];
        if ($this->request->getVar("cstm_id")) {
            $updtdata = [
                'cstm_id' => $this->request->getVar("cstm_id"),
                'cstm_profile_photo' => 'uploads/customer_profilepics/' . $imageFile->getName(),

                'cstm_updated_by' => $tokendata['uid'],
                'cstm_updated_on' => $date,

            ];
            $result = $customermastermodel->update($this->request->getVar("cstm_id"), $updtdata);
        }

        if ($result) {
            $data['ret_data'] = "success";
            $data['prof_url'] = 'uploads/customer_profilepics/' . $imageFile->getName();
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->respond($data, 200);
        }
    }

    public function vendor_Assign()
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

        $rules = [
            'cstm_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $customermasterModel = new CustomerMasterModel();
        $data = [
            'cstm_vendor_flag' => 1,
            'cstm_updated_by' => $tokendata['uid'],
            'cstm_updated_on' => $date,
        ];
        $id = $customermasterModel->update(($this->request->getVar('cstm_id')), $data);

        if ($id) {
            $response = ['ret_data' => 'success'];
        } else {
            $response = ['ret_data' => 'fail'];
        }
        return $this->respond($response, 200);
    }
    public function vendor_Assign_update()
    {
        $date = date("Y-m-d H:i:s");
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

        $rules = [
            'cstm_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $customermasterModel = new CustomerMasterModel();
        $data = [
            'cstm_vendor_flag' => 0,
            'cstm_updated_by' => $tokendata['uid'],
            'cstm_updated_on' => $date,
        ];
        $id = $customermasterModel->update(($this->request->getVar('cstm_id')), $data);

        if ($id) {
            $response = ['ret_data' => 'success'];
        } else {
            $response = ['ret_data' => 'fail'];
        }
        return $this->respond($response, 200);
    }


    public function premiumreq_bycust()
    {
        $date = date("Y-m-d H:i:s");
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $custModel = new CustomerMasterModel();
        $userModel = new UsersModel();

        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'customer') {

            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }

        $rules = [
            'cp_serial' => 'required',
        ];
        $productmasterModel = new ProductMasterModel();
        $customerProducts = new CustomerProducts();
        $approvalModel = new ApprovalmasterModel();

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
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
                'cp_cstm_id' => $tokendata['uid'],
                'cp_created_by' => $tokendata['uid'],
                'cp_created_on' => $date,
                'cp_updated_by' => $tokendata['uid'],
                'cp_updated_on' => $date,

            ];
            $customerProducts->insert($track);

            $am = [
                'am_type' => 11,
                'am_requestedby' => $tokendata['uid'],
                'am_referenceid' => $prod_det['pm_id'],
                'am_status' => 0,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
                'am_createdby' => $tokendata['uid'],
                'am_createdon' => $date,
            ];
            $am_id = $approvalModel->insert($am);

            $response = [
                'ret_data' => 'success',
                'am_id' => $am_id
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
                'Message' => 'Wrong Serial Number/Serial Number already taken'
            ];
        }
        return $this->respond($response, 200);
    }

    public function get_customer_types()
    {
        $date = date("Y-m-d H:i:s");
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $custModel = new CustomerMasterModel();
        $userModel = new UsersModel();


       $customertypesModel= new CustomerRolesModel();

      
        $types = $customertypesModel
            ->where('cstr_delete_flag', 0)
            ->findall();

            $response=sizeof($types)>0?
            ['ret_data'=>'success','cust_types'=>$types]:
            ['ret_data'=>'fail','Message'=>'No Customer Types Found!!!'];
      
        return $this->respond($response, 200);
    }

    public function vendor_update()
    {
        $date = date("Y-m-d H:i:s");
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

        $rules = [
            'cstm_id' => 'required',
            'cstm_vendor_percent' => 'required',
            'cstm_vendor_flag' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $customermasterModel = new CustomerMasterModel();
        $data = [
            'cstm_vendor_flag' =>$this->request->getVar('cstm_vendor_flag'),
            'cstm_updated_by' => $tokendata['uid'],
            'cstm_updated_on' => $date,
            'cstm_vendor_percent'=>$this->request->getVar('cstm_vendor_percent')
        ];
         $customermasterModel->update($this->request->getVar('cstm_id'),$data);

            $response = ['ret_data' => 'success'];
     
        return $this->respond($response, 200);
    }

    //To add subusers by customer admin

    public function create_sub_users(){
        
        $date = date("Y-m-d H:i:s");
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

        $rules = [
            'cstr_id' => 'required',
            'citems_name' => 'required',
            'citems_email' => 'required',
            'citems_password' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $cmasterModel = new CustomerMasterModel();
        $citemsModel = new CustomerItemsModel();
        $cactionsmappingModel = new CustomerActionsMappingModel();
        
        $custDetails=[
            'citems_custmasterId'=>$tokendata['uid'],
            'citems_name'=>$this->request->getvar('citems_name'),
            'citems_email'=>$this->request->getvar('citems_email'),
            'citems_password'=>$this->request->getvar('citems_password'),
            'citems_roleId'=>$this->request->getvar('cstr_id'),
            'citems_phone'=>$this->request->getvar('citems_phone')?$this->request->getvar('citems_phone'):'',
            'citems_countrycodeId'=>$this->request->getvar('citems_countrycodeId')?$this->request->getvar('citems_countrycodeId'):'',
            'citems_vendorflagId'=>$this->request->getvar('citems_vendorflagId')?$this->request->getvar('citems_vendorflagId'):0,
            'citems_statusflagId'=>0,
        ];
        $citems_id=$citemsModel->insert($custDetails);

        if($citems_id && $citems_id!=''){
            $response=['ret_data'=>'success','citems_id'=>$citems_id];
            if(!$this->request->getvar('cactions_id')) return $this->respond($response,200);
               
            $actionMapData=[
                'cactionsmapping_roleId'=>$this->request->getvar('cstr_id'),
                'cactionsmapping_custId'=>$citems_id,
                'cactionsmapping_actionId'=>$this->request->getvar('cactions_id'),
                'cactionsmapping_featureId'=>$this->request->getvar('cfrm_feature_id'),
                'cactionsmapping_refernce'=>$this->request->getvar('cactionsmapping_refernce')

            ];
            $caction_id=$cactionsmappingModel->insert($actionMapData);
            $response=['ret_data'=>'success','citems_id'=>$citems_id,'caction_id'=>$caction_id];
        }else{

            $response=['ret_data'=>'fail','Message'=>'failed to insert value'];
        }
        return $this->respond($response,200);
    }

    
}
