<?php

namespace App\Controllers\Coupon;

use App\Models\Coupon\CouponModel;
use App\Models\Coupon\CoupontypeModel;
use App\Models\Coupon\SpecificCouponModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\User\UsersModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use Config\Validation;
use Config\Validation as ConfigValidation;

class CouponMasterController extends ResourceController
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
        $couponModel = new CouponModel();
       

            $result = $couponModel->where('coupon_delete_flag', 0
            )->join('coupon_type_master', 'coupon_type_master.coupon_type_id=coupon_master.coupon_type_id', 'left')
            ->orderBy('coupon_id','desc')
            ->findAll();
            if ($result) {
                $data['ret_data'] = "success";
                $data['coupon_list'] = $result;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "No data found";
                return $this->fail($data, 200);
            }
        
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        
       
        $couponModel = new CouponModel();
        $specificCoupons = new SpecificCouponModel();
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
            $coupon_data = $couponModel->where('coupon_delete_flag', 0)->where('coupon_id', base64_decode($id))
                ->join('coupon_type_master', 'coupon_type_master.coupon_type_id=coupon_master.coupon_type_id', 'left')->first();
            if ($coupon_data['coupon_type_id'] == 1) {
                $coupon_data['cust_list'] = $specificCoupons->
                where('sc_deleteflag', 0)->where('sc_type', 1)
                ->where('sc_coupon_id', base64_decode($id))
                ->join('customer_master','cstm_id=sc_item_id','left')
                ->findAll();
            } else {
                $coupon_data['cust_list'] = [];
            }
            if ($coupon_data) {
                $data['ret_data'] = "success";
                $data['coupon_data'] = $coupon_data;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "No data found";
                return $this->fail($data, 400);
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
       
       
        $couponModel = new CouponModel();
        $specificCoupons = new SpecificCouponModel();
        
            $rules = [
                'coupon_code' => 'required',
                'coupon_discount' => 'required',
                'coupon_valid_from' => 'required',
                'coupon_valid_to' => 'required',
                'coupon_min_amount' => 'required',
                'coupon_max_discount' => 'required',
                'coupon_discount_type' => 'required',
                'coupon_total_usage' => 'required'
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            
            if($this->request->getVar('coupon_type')=='cstm_specific'){
                $coupon_type_id=1;
            }else{
                $coupon_type_id=0;
            }
            $inData = [
                'coupon_code' => $this->request->getVar('coupon_code'),
                
                'coupon_type_id' => $coupon_type_id,
                'coupon_discount' => $this->request->getVar('coupon_discount'),
                'coupon_description' => $this->request->getVar('coupon_description'),
                'coupon_valid_from' => $this->request->getVar('coupon_valid_from'),
                'coupon_valid_to' => $this->request->getVar('coupon_valid_to'),
                'coupon_min_amount' => $this->request->getVar('coupon_min_amount'),
                'coupon_max_discount' => $this->request->getVar('coupon_max_discount'),
                'coupon_discount_type' => $this->request->getVar('coupon_discount_type'),
                'coupon_total_usage' => $this->request->getVar('coupon_total_usage'),
                'coupon_created_by' => $tokendata['uid'],
                'coupon_updated_by' => $tokendata['uid'],
            ];
            


            $result = $couponModel->insert($inData);
            $cust_list = $this->request->getVar('cust_list');
            $in_data = array();
            if (sizeof($cust_list) > 0 ){
                foreach($cust_list as $cust_list){
                    $infdata = [
                        'sc_coupon_id'=> $result,
                        'sc_type' => 1,
                        'sc_item_id' => $cust_list->cstm_id,
                        'sc_created_by' => $tokendata['uid'],
                        'sc_updated_by' => $tokendata['uid'],
                    ];
                    array_push($in_data, $infdata);
                }
                    
            }


            if(count($in_data)>0){
                $specificCoupons->insertBatch($in_data);
            }
            if ($result) {
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->fail($data, 400);
            }
        
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
        $UserModel = new UsersModel();
        
        $couponModel = new CouponModel();
        $specificCoupons = new SpecificCouponModel();
       
            $rules = [
                'coupon_id' => 'required',
                'coupon_code' => 'required',
                'coupon_type_id' => 'required',
                'coupon_discount' => 'required',
                'coupon_valid_from' => 'required',
                'coupon_valid_to' => 'required',
                'coupon_min_amount' => 'required',
                'coupon_max_discount' => 'required',
                'coupon_discount_type' => 'required',
                'coupon_total_usage' => 'required'
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $inData = [
                'coupon_code' => $this->request->getVar('coupon_code'),
                'coupon_type_id' => $this->request->getVar('coupon_type_id'),
                'coupon_discount' => $this->request->getVar('coupon_discount'),
                'coupon_description' => $this->request->getVar('coupon_description'),
                'coupon_valid_from' => $this->request->getVar('coupon_valid_from'),
                'coupon_valid_to' => $this->request->getVar('coupon_valid_to'),
                'coupon_min_amount' => $this->request->getVar('coupon_min_amount'),
                'coupon_max_discount' => $this->request->getVar('coupon_max_discount'),
                'coupon_discount_type' => $this->request->getVar('coupon_discount_type'),
                'coupon_total_usage' => $this->request->getVar('coupon_total_usage'),
                'coupon_created_by' => $tokendata['uid'],
                'coupon_updated_by' => $tokendata['uid'],
            ];

            $result = $couponModel->update($this->request->getVar('coupon_id'), $inData);
            $cust_list = $this->request->getVar("cust_list");
            $in_data = [];
            if (count($cust_list) > 0) {
                $specificCoupons->where('sc_coupon_id', $this->request->getVar('coupon_id'))->where('sc_type', 1)->delete();
                foreach ($cust_list as $eachcust) {

                    $infdata = [
                        'sc_coupon_id'   => $this->request->getVar('coupon_id'),
                        'sc_type' => 1,
                        'sc_item_id' => $eachcust->cstm_id,
                        'sc_created_by' => $tokendata['uid'],
                        'sc_updated_by' => $tokendata['uid'],
                    ];
                    array_push($in_data, $infdata);
                }
            }
            
            if(count($in_data)>0){
                $specificCoupons->insertBatch($in_data);
            }
            if ($result) {
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->fail($data, 400);
            }
        
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        
    }
    public function get_coupontypelist()
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
        $couponTypeModel = new CoupontypeModel();
       
            $result = $couponTypeModel->where('coupon_type_delete_flag', 0)->findAll();
            if ($result) {
                $data['ret_data'] = "success";
                $data['coupon_typelist'] = $result;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "No data found";
                return $this->fail($data, 400);
            }
       
    }
    public function get_couponsforcustomer()

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
        $couponModel = new CouponModel();
        $specificCoupons = new SpecificCouponModel();
       
            $UserModel = new UsersModel();
           
        $rules = [
            'totalamount' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $result['general'] = $couponModel->where('coupon_delete_flag', 0)->where('coupon_type_id', 0)->where('CAST(coupon_min_amount AS DECIMAL(10,2))<=',$this->request->getVar("totalamount"))->where("coupon_valid_to >=", date('Y-m-d'))
            ->findAll();
            $retarray = [];
        if(count($result['general'] )>0){
            foreach($result['general'] as $eachg){
                if($eachg['coupon_discount_type']==1){            
                    $temp_discount = floatval($this->request->getVar("totalamount")) * floatval($eachg['coupon_discount']) / 100;
                    if(floatval($temp_discount)>floatval($eachg['coupon_max_discount']))  $eachg['discountamount']=strVal(floatval($eachg['coupon_max_discount']));
                    else  $eachg['discountamount']=strVal(floatval($temp_discount));                
                }else{
                    $eachg['discountamount'] =strVal($eachg['coupon_discount']); 
                }
                $eachg['category'] ='general'; 
                array_push($retarray,$eachg);
            }
        }
        
        $result['customer_specific'] = $specificCoupons->where('sc_deleteflag', 0)->where('sc_type', 1)
            ->where('sc_item_id',  $tokendata['uid'])
            ->join('coupon_master', 'coupon_master.coupon_id=sc_coupon_id')
            ->where("coupon_valid_to >=", date('Y-m-d'))->where('CAST(coupon_min_amount AS DECIMAL(10,2))<=',$this->request->getVar("totalamount"))
            ->join('coupon_type_master', 'coupon_type_master.coupon_type_id=coupon_master.coupon_type_id', 'left')
            ->findAll();
            // $data['coupons'] =  $result['customer_specific'];
            //  return $this->respond($result, 200);
            if(count($result['customer_specific'] )>0){
                foreach($result['customer_specific'] as $eachc){
                    if($eachc['coupon_discount_type']==1){            
                        $temp_discount = floatval($this->request->getVar("totalamount")) * floatval($eachc['coupon_discount']) / 100;
                        if(floatval($temp_discount)>floatval($eachc['coupon_max_discount']))  $eachc['discountamount']=strVal(floatval($eachc['coupon_max_discount']));
                        else  $eachc['discountamount']=strval(floatval($temp_discount));                
                    }else{
                        $eachc['discountamount'] =strVal($eachc['coupon_discount']); 
                    }
                $eachc['category'] ='customer'; 
                array_push($retarray,$eachc);
                }
            }
        
        if ($result) {
            $data['ret_data'] = "success";
            $data['coupons'] = $retarray;
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "No data found";
            return $this->fail($data, 400);
        }
    }

    public function delete_coupon(){
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
        $couponModel = new CouponModel();
        $inData=[
            'coupon_delete_flag'=>1
        ];
        $result = $couponModel->update($this->request->getVar('coupon_id'), $inData);
        if($result){
            $response=[
                'ret_data'=>'success',
                
            ];
        }else{
            $response=[
                'ret_data'=>'fail',
                
            ];
        }
        return $this->respond($response, 200);
    }


    public function check_appliedcoupon()
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $couponModel = new CouponModel();        
        $specificCoupons = new SpecificCouponModel();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $user = $userModel->where("us_id", $tokendata['uid'])->where("us_status_flag", 0)->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'customer') {
            $custModel = new CustomerMasterModel();


            $customer = $custModel->get_customer_by_id($tokendata['uid']);
            if (!$customer) {
                return $this->fail("invalid customer", 400);
            }
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        $ret = $couponModel->where('coupon_delete_flag', 0)->where('coupon_code', $this->request->getVar("coupon_code"))
        ->where("coupon_valid_to >=", date('Y-m-d'))->first();
            $result['general']=$ret;
            $result['customer_specific']=null;
            $result['package_specific']=null;
            $result['vehicle_specific']=null;
        if($ret&& $ret['coupon_type_id']!=4){
            $result['customer_specific'] = $specificCoupons->where('sc_deleteflag', 0)->where('sc_type', 1)->where('sc_item_id', $this->request->getVar("cust_id"))->where('sc_coupon_id', $ret['coupon_id'])
            ->join('coupon_master', 'coupon_master.coupon_id=sc_coupon_id')->join('coupon_type_master', 'coupon_type_master.coupon_type_id=coupon_master.coupon_type_id', 'left')
            ->findAll();
        $result['package_specific'] = $specificCoupons->where('sc_deleteflag', 0)->where('sc_type', 3)->where('sc_item_id', $this->request->getVar("pack_id"))->where('sc_coupon_id', $ret['coupon_id'])
            ->join('coupon_master', 'coupon_master.coupon_id=sc_coupon_id')->join('coupon_type_master', 'coupon_type_master.coupon_type_id=coupon_master.coupon_type_id', 'left')
            ->findAll();
        $result['vehicle_specific'] = $specificCoupons->where('sc_deleteflag', 0)->where('sc_type', 2)->where('sc_item_id', $this->request->getVar("vgroup_id"))->where('sc_coupon_id', $ret['coupon_id'])
            ->join('coupon_master', 'coupon_master.coupon_id=sc_coupon_id')->join('coupon_type_master', 'coupon_type_master.coupon_type_id=coupon_master.coupon_type_id', 'left')
            ->findAll();
        }
        if ($ret) {
            $data['ret_data'] = "success";            
            $data['general'] = $result['general'];
            $data['customer_specific'] = $result['customer_specific'];
            $data['package_specific'] = $result['package_specific'];
            $data['vehicle_specific'] = $result['vehicle_specific'];
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "No data found";
            return $this->respond($data, 200);
        }
    }
}
