<?php

namespace App\Controllers\Customer;

use App\Models\Customer\CustomerActionsModel;
use App\Models\Customer\CustomerFeatureActionsModel;
use App\Models\Customer\CustomerFeatureListModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerRoleMappingModel;
use CodeIgniter\RESTful\ResourceController;
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

class CustomerSettingsController extends ResourceController
{
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
        $customerdiscountModel = new CustomerDiscountModel();
        $cust_list = $customermastermodel
            ->where('cstm_delete_flag', 0)
            ->join('customer_roles', 'cstr_id=cstm_cstr_id')
            ->join('customer_products', 'cp_cstm_id=cstm_id', 'left')
            ->orderBy('cstm_id', 'desc')
            ->findAll();
        $pm_cust = $cust_list;


        $cust_list2 = 0;
        if ($pm_cust) {
            foreach ($pm_cust as $pm_cust) {
                if (($pm_cust['cp_id']) != null) {
                    $cust_list2 = $cust_list2 + 1;
                }
            }
            if (($cust_list2)) {

                $total_pm_size = $cust_list2;
            } else {
                $total_pm_size = 0;
            }
        }

        $total_cust_size = sizeof($cust_list);
        $length_data = [
            'total_cust' => $total_cust_size,
            'total_pmcust' => $total_pm_size
        ];

        $discount_data = $customerdiscountModel
            ->where('cd_delete_flag', 0)
            ->join('users', 'us_id=cd_created_by', 'left')
            ->findAll();

        if ($cust_list) {
            $response = [
                'ret_data' => 'success',
                'cust_list' => $cust_list,
                'discount_data' => $discount_data,
                'length_data' => $length_data
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
        $customerdiscountModel = new CustomerDiscountModel();
        // $discount_data=$customerdiscountModel->where('cd_delete_flag',0)->findall();
        // foreach ($discount_data as $discount) {
        //     $data = array(
        //         'cd_active_flag' => 0,
        //         'cd_request_type' => $discount->cd_request_type,
        //         'cd_rate' => $discount->cd_rate,
        //         'cd_type' => $discount->cd_type,
        //         'cd_updated_by' => $tokendata['uid'],
        //         'cd_id' => $discount->cd_id
        //     );

        //     $p_data[$discount->cd_id] = $data;
        // }

        // $customerdiscountModel->updateBatch($p_data, 'cd_id');
        $cust_dic = [
            'cd_type' => $this->request->getVar('cd_type'),
            'cd_rate' => $this->request->getVar('cd_rate'),
            'cd_request_type' => $this->request->getVar('cd_request_type'),
            'cd_active_flag' => 1,
            'cd_created_by' => $tokendata['uid']
        ];

        $id = $customerdiscountModel->insert($cust_dic);
        if ($id) {
            $response = [
                'cd_id' => $id,
                'ret_data' => 'success'
            ];
        } else {
            $response = [
                'cd_id' => 0,
                'ret_data' => 'fail'
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

    public function set_active_status()
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
            'cd_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $customermastermodel = new CustomerMasterModel();
        $customerdiscountModel = new CustomerDiscountModel();
        $p_data = array();
        foreach ($this->request->getVar('discount_data') as $discount) {
            $data = array(
                'cd_active_flag' => 1,
                'cd_request_type' => $discount->cd_request_type,
                'cd_rate' => $discount->cd_rate,
                'cd_type' => $discount->cd_type,
                'cd_updated_by' => $tokendata['uid'],

                'cd_id' => $discount->cd_id
            );

            $p_data[$discount->cd_id] = $data;
        }

        $customerdiscountModel->updateBatch($p_data, 'cd_id');


        $un_data = [
            'cd_active_flag' => 0
        ];

        $customerdiscountModel->update($this->request->getVar('cd_id'), $un_data);

        $response = [
            'ret_data' => 'success'
        ];
        return $this->respond($response, 200);
    }

    public function getCustomer_roles(){
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

        $crolemasterModel=new CustomerRolesModel();
        $crolemappingModel = new CustomerRoleMappingModel();
        $cfeaturelistModel = new CustomerFeatureListModel();
        $cfeatureactionsModel = new CustomerFeatureActionsModel();

        $role_data=
        $crolemasterModel->select(
            'cstr_id,
            cstr_name,
            cstr_description'
           
        )->where('cstr_delete_flag',0)
        ->findall();

        // foreach($role_data as $eachrole){

        //     $eachrole['role_features']=$crolemappingModel->where('cfrm_role_id',$eachrole['cstr_id'])->findAll();

        // }
        for($i=0;$i<sizeof($role_data);$i++){

            $role_data[$i]['role_features']=$crolemappingModel
            ->select('cfrm_id,cfrm_role_id,cfrm_feature_id,cfrm_action_id,cft_name,cfa_name')
            ->join('customer_feature_actions','cfa_id=cfrm_action_id','left')
            ->join('customer_feature_list','cft_id=cfrm_feature_id','left')
            ->where('cfrm_role_id',$role_data[$i]['cstr_id'])->findAll();

        }
        $response=[
            'ret_data'=>'success',
            'customer_roles'=>$role_data,
            'customer_feature_list'=>$cfeaturelistModel->where('cft_delete_flag',0)->findAll(),
            'customer_feature_actions'=>$cfeatureactionsModel->findAll()
        ];
        return $this->respond($response,200);

    }
    public function getCustomer_actions(){
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
        $cactionmasterModel = new CustomerActionsModel();
        $response=[
            'ret_data'=>'success',
            'customer_roles'=>$cactionmasterModel->where('cactions_delete_flag',0)->findall()
        ];
        return $this->respond($response,200);

    }

    public function assign_vendor()
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
            'cstm_vendor_flag'=>'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $customermasterModel = new CustomerMasterModel();
        $data = [
            'cstm_vendor_flag' =>$this->request->getVar('cstm_vendor_flag'),
        ];
         $customermasterModel->update($this->request->getVar('cstm_id'),$data);

            $response = ['ret_data' => 'success'];
     
        return $this->respond($response, 200);
    }

    
}
