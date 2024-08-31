<?php

namespace App\Controllers\System;

use App\Models\Customer\CustomerMasterModel;
use App\Models\Products\ProductMasterModel;
use App\Models\ServiceRequest\ServiceRequestItemsModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\System\NotificationmasterModel;
use App\Models\User\UsersModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use Config\Validation;

class UsersNotificationController extends ResourceController
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
        $notificationmasterModel = new NotificationmasterModel();
        $cusmodel = new CustomerMasterModel();
        $productMaster=new ProductMasterModel();
        $servicerequestmasterModel= new ServiceRequestMasterModel();
        $servicerequestitemsModel= new ServiceRequestItemsModel();

   


        if($tokendata['aud'] == 'customer'){
            $notifications=$notificationmasterModel->where('nt_deleteflag',0)->where('nt_sourcetype',0)
            ->where('nt_destid',$tokendata['uid'])
            ->where('nt_read',0)
            ->join('customer_master','cstm_id=nt_sourceid','left')
            ->orderBy('nt_id','DESC')
            ->findAll();
            $cust_in=$cusmodel->where('cstm_id',$tokendata['uid'])->first();

            if($cust_in['cstm_vendor_flag']==1){
                $vendor_data_master=$servicerequestmasterModel->where('serm_deleteflag',0)
                ->where('serm_vendor_flag',1)->where('serm_assigne',$tokendata['uid'])->findAll();
    
                $vendor_data_items=$servicerequestitemsModel->where('sitem_deleteflag',0)->where('sitem_assignee_type',1)
                ->where('sitem_assignee',($tokendata['uid']))->findAll();
                $vendor_data=(sizeof($vendor_data_master) + sizeof($vendor_data_items)); 
    
    
            }else{
                $vendor_data=0;
            }
        }else{
           
            $notifications=$notificationmasterModel
            // ->where('nt_deleteflag',0)
            ->where('nt_sourcetype',1)
            ->where('nt_destid',$tokendata['uid'])
            ->where('nt_read',0)
            ->join('customer_master','cstm_id=nt_sourceid','left')
            ->orderBy('nt_id','DESC')
            ->findAll();
            // return  $this->respond($notifications,200);
            $vendor_data=0;  
        }
        

        $response=sizeof($notifications)>0?
            ['ret_data'=>'success','notifications'=>$notifications,'notif_len'=>sizeof($notifications),'vendor_data'=>$vendor_data]:
            ['ret_data'=>'fail','Message'=>'No Notifications','vendor_data'=>$vendor_data];

           return  $this->respond($response,200);

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

        $notificationmasterModel = new NotificationmasterModel();
        $cusmodel = new CustomerMasterModel();
        $productMaster=new ProductMasterModel();
        $servicerequestmasterModel= new ServiceRequestMasterModel();
        $servicerequestitemsModel= new ServiceRequestItemsModel();

        $cust_info=[];
        $cust_in = $cusmodel->where('cstm_id', base64_decode($id))
        ->join('customer_roles', 'cstr_id=cstm_cstr_id')
        ->join('customer_products','cp_cstm_id=cstm_id','left')
        ->first();
        if($cust_in['cp_serial']!=null){
            $product_det=$productMaster->where('pm_sl_nm',$cust_in['cp_serial'])->first();
            $cust_info = $product_det ? array_merge($cust_in, $product_det) : $cust_in;

                    
        }else{
            $cust_info=$cust_in; 
        }

        

        
        $result=$notificationmasterModel->where('nt_deleteflag',0)->where('nt_sourcetype',1)
            ->where('nt_destid',$tokendata['uid'])
            ->where('nt_read',0)
            ->join('customer_master','cstm_id=nt_sourceid','left')
            ->orderBy('nt_id','DESC')
            ->findAll();


        if ($cust_info||$result) {
            $response['ret_data'] = "success";
            $response['Nt_data'] = $result;
            $response['cust_data'] = $cust_info;
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No notifications';
            
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
        $notificationmasterModel = new NotificationmasterModel();
        $cusmodel = new CustomerMasterModel();
        $productMaster=new ProductMasterModel();
        $data=[
            'nt_read'=>1,
        ];
        $notificationmasterModel->update(($this->request->getVar("nt_id")),$data);
        $response=['ret_data'=>'success','nt_id'=>$this->request->getVar("nt_id")];
        return  $this->respond($response,200);
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
        $notificationmasterModel = new NotificationmasterModel();
        $cusmodel = new CustomerMasterModel();
        $productMaster=new ProductMasterModel();
        $data=[
            'nt_deleteflag'=>1,
        ];
        $notificationmasterModel->update(($this->request->getVar("nt_id")),$data);
        $response=['ret_data'=>'success','nt_id'=>$this->request->getVar("nt_id")];
        return  $this->respond($response,200);
    }


    public function push_notification($data)
    {
        // return $this->respond($data, 200);

        $custmodel = new CustomerMasterModel();
        $commonutils = new Commonutils();
        $usermodel = new UsersModel();
        if ($this->request->getVar("cstm_id")) {
            $target_usr = $custmodel->where('cstm_id', $this->request->getVar("cstm_id"))->first();
            $fcm_token = $target_usr['fcm_token_mobile'];
        } else {
            $target_usr = $usermodel->where('cstm_id', $this->request->getVar("us_id"))->first();
            $fcm_token = $target_usr['fcm_token_web'];
        }
        $player_id = [];
        $custhead = $this->request->getVar("head_message");
        $custcontent = $this->request->getVar("content_message");
        array_push($player_id, $fcm_token);
        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);
        return $this->respond('success', 200);
    }

    public function clear_notification()
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
        // return $this->respond($tokendata['uid'], 200);

        $notificationmasterModel = new NotificationmasterModel();
          
        $data = [
            'nt_read' => 1
        ];
        $notif_data=$notificationmasterModel->where('nt_destid',$tokendata['uid'])->where('nt_read',0)->findAll();
        if($notif_data){
            for($i=0;$i<sizeof($notif_data);$i++){
                // return $this->respond($notif_data[$i]['nt_id'], 200);
               // return $this->respond($data, 200);
               //  return $this->respond("Please provide test cases for nt_id and nt_read", 200);
               $res=$notificationmasterModel->update($notif_data[$i]['nt_id'],$data);
           }
           if ($res) {
            $response['ret_data'] = "success";
            
        } else {
            $response['ret_data'] = "fail";
            
        }
        }else{
            $response['Message'] = "No active notifications";
        }


        
        return $this->respond($response, 200);

    }

    public function create_us_notification($notif_data)
    {
      
        
        $userModel= new UsersModel();
        $custmodel= new CustomerMasterModel();
        $commonutils = new Commonutils();
        $notificationmasterModel=new NotificationmasterModel();
       
       
        foreach($notif_data as $eachurl){
            $target_cust = $userModel->where('us_id', $eachurl['id'])->first();
            $player_id = [];
            $headers = $eachurl['headers'];
            $content = $eachurl['content'];
            // array_push($player_id, $target_cust['fcm_token_web']);
            // if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($headers, $content, $player_id);
           
                $notife_data = [
                    'nt_sourceid' => $eachurl['sourceid'],
                    'nt_destid' => $eachurl['destid'],
                    'nt_sourcetype' => 1,
                    'nt_header' => $headers,
                    'nt_content' => $content,
                    'nt_created_on' => $eachurl['date'],
                    'nt_request_type'=>$eachurl['nt_request_type'],
                    'nt_type_id'=>$eachurl['nt_type_id'],
                    'nt_type'=>$eachurl['nt_type'],
                    
                ];
                $nt_id=$notificationmasterModel->insert($notife_data);
           
        }
         return $notif_data;
        
    }

    public function create_cust_notification($notif_data)
    {
      
        $custmodel= new CustomerMasterModel();
        $commonutils = new Commonutils();
        $notificationmasterModel=new NotificationmasterModel();
       
        // $target_cust = $userModel->where('us_id', $data->id)->first();
        $target_cust = $custmodel->where('cstm_id', $notif_data['id'])->first();
        $player_id = [];
        $headers = $notif_data['headers'];
        $content = $notif_data['content'];

        array_push($player_id, $target_cust['fcm_token_mobile']);
        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($headers, $content, $player_id);

        if ($ret_res) {

            $notife_data = [


                'nt_sourceid' => $notif_data['sourceid'],
                'nt_destid' => $notif_data['destid'],
                'nt_sourcetype' => 0,
                'nt_header' => $headers,
                'nt_content' => $content,
                'nt_created_on' => $notif_data['date'],

                'nt_req_number'=>$notif_data['nt_req_number'],
                'nt_type'=>$notif_data['nt_type'],
                'nt_request_type'=>$notif_data['nt_request_type'],
                'nt_type_id'=>$notif_data['nt_type_id']

            ];

            $nt_id=$notificationmasterModel->insert($notife_data);
            return $nt_id;
        }
    }

    public function clear_us_notif()
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
            'notif_data' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $notificationmasterModel = new NotificationmasterModel();

        
        $notif_d=[
            'nt_read'=>1
        ];

        foreach($this->request->getvar('notif_data') as $nt_data)
        {
            
        $notificationmasterModel->update($nt_data->nt_id,$notif_d);
        }

        $response=[
            'ret_data'=>'success',

        ];
        return $this->respond($response,200);



    }

}