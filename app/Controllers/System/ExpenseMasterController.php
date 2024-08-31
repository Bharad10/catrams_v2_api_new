<?php

namespace App\Controllers\System;

use App\Models\Customer\CustomerMasterModel;
use App\Models\System\ExpenseModel;
use App\Models\User\UsersModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use Config\Validation;
class ExpenseMasterController extends ResourceController
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
        $expenseModel=new ExpenseModel();
        $exp_data=$expenseModel->where('exp_delete_flag',0)->orderBy('exp_id','desc')->findAll();
        if($exp_data){
            $response=[
                'ret_data'=>'success',
                'exp_data'=>$exp_data
            ];
        }else{
            $response=[
                'ret_data'=>'error',
                'Message'=>'No Data Found'
            ];  
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
         if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }

        $rules = [
            'expensename' => 'required',
            'expenseamount' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $expenseModel=new ExpenseModel();

        $master=[
            'exp_name'=> $this->request->getVar('expensename'),
            'exp_cost'=> $this->request->getVar('expenseamount'),
            'exp_desc'=> $this->request->getVar('expensedescription'),
        ];

        $exp_id=$expenseModel->insert($master);
        if($exp_id){
            $response=[
                'ret_data'=>'success',
                'id'=>$exp_id
            ];
        }else{
            $response=[
                'ret_data'=>'error',
                'Message'=>'Servor Error!!!Please try again'
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

        $rules = [
            'exp_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $expenseModel=new ExpenseModel();

        $data=[
            'exp_delete_flag'=>1
        ];
        $expenseModel->update($this->request->getvar('exp_id'),$data);

        $response=['ret_data'=>'success'];

        return $this->respond($response,200);


    }
}
