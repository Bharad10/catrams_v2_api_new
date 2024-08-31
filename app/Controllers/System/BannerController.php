<?php

namespace App\Controllers\System;


use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Config\Commonutils;
use Config\Validation;

use App\Models\Customer\CustomerMasterModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\System\AdvertisementmasterModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use App\Models\User\UsersModel;
use CodeIgniter\I18n\Time;

class BannerController extends ResourceController
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

        $bannermasterModel = new AdvertisementmasterModel();
        $banner_list=[];
        $banner_list = $bannermasterModel->where('ads_active_flag', 0)
            ->orderBy('ads_id', 'desc')
            ->findAll();
            
            
        if ($banner_list) {
            $response = [
                'ret_data' => 'success',
                'banner_list' => $banner_list,
                'imageurl'=>getenv('AWS_URL')
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'banner_list' => 6
            ];
           
        }

        
        $toolRequestModel=new ToolRequestDetailsModel();
        $toolrequesthistoryModel=new ToolRequestHistoryModel();

        $paymentTrackerModel=new PaymentTrackermasterModel();
        $tldet_data=$toolRequestModel->where('tldt_active_flag',0)->join('status_master','sm_id=tldt_status')
        ->join('request_payment_tracker','rpt_reqt_id=tldet_id')
        ->join('tool_details','tool_id=tldt_tool_id')
        ->join('customer_master','cstm_id=tldt_cstm_id')
        ->findAll();
        for($i=0;$i<sizeof($tldet_data);$i++){
            if($tldet_data[$i]['tldt_due_date']!=null){
                $tldt_due_date[$i] = Time::createFromFormat('Y-m-d H:i:s', $tldet_data[$i]['tldt_due_date']);
                $current_date = Time::now();
                if ($current_date->isAfter($tldt_due_date[$i])) {
                    

                 $time_difference_seconds[$i] = $current_date->getTimestamp() - $tldt_due_date[$i]->getTimestamp();
                 $time_difference_minutes[$i] = floor($time_difference_seconds[$i] / 60);
                 $time_difference_hours[$i] = floor($time_difference_minutes[$i] / 60);
                 $time_difference_days[$i] = floor($time_difference_hours[$i] / 24);
                 
                 if($time_difference_days[$i]> $tldet_data[$i]['rpt_due_days']){
                    
                    $tldet_data[$i]['due_days']=(string) $time_difference_days[$i];
                    $tldet_data[$i]['due_rent_price']=(string) ($time_difference_days[$i]*$tldet_data[$i]['tool_rent_cost']*$tldet_data[$i]['tldt_tool_quant']*$tldet_data[$i]['tool_delay_percentage'])/100;  
                    
                    $tr_updated_cost[$i]=$tldet_data[$i]['due_rent_price']+$tldet_data[$i]['rpt_amount'];
                    $payment_Data[$i]=[
                       'rpt_amount'=>$tr_updated_cost[$i],
                       'rpt_status'=>2,
                       'rpt_due_days'=>$time_difference_days[$i]
                    ];
                    if($tldet_data[$i]['tldt_status']!=15){
                        $master[$i]=[
                            'tldt_status'=>15,
                            'tldt_paymt_flag'=>1
                        ];
                        $hist[$i]=[
                            'trqh_tr_id'=>$tldet_data[$i]['tldet_id'],
                            'trqh_status_id'=>15
                        ];
                        $toolRequestModel->update($tldet_data[$i]['tldet_id'],$master[$i]);
                        $toolrequesthistoryModel->insert($hist[$i]);
                    }
                   
                    
                   $paymentTrackerModel->update($tldet_data[$i]['rpt_id'],$payment_Data[$i]);
                   
                 }
                 
             }
            }
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
        // return $this->respond('ddddddddddddddddddddddd', 200);

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
        // $response['ret_data'] = 'error';
        $CustomerMasterModel = new CustomerMasterModel();
        $bannermasterModel = new AdvertisementmasterModel();
        $result = $bannermasterModel->where('ads_active_flag', 0)
        ->where('ads_id', base64_decode($id))
        ->first();

        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            $response['result']['image_url']=getenv('AWS_URL');
           
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No details for this banner';
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
        $bannermasterModel = new AdvertisementmasterModel();
        $b_data = $this->request->getVar('banner_data');
        if ($b_data) {
           $url_data= $b_data->base_version==='local'?$b_data->ads_image: $this->ban_img_upload($b_data->ads_image);

        //    'path' => $img_url,
        //             'image_data'=>$img_data

            $create_banner = [
                'ads_name' => $b_data->ads_name,
                'ads_desc' => $b_data->ads_desc,
                'ads_image' => $b_data->base_version==='local'?$url_data:$url_data['path'],
                'ads_type' => $b_data->ads_type,
                'ads_active_flag' => 0,
                'ads_created_by' => $tokendata['uid'],
                'ads_updated_by' => $tokendata['uid'],
                
            ];
             $builder = $this->db->table('advertisement_details');
            $b_id = $builder->insertBatch($create_banner);
            // return $this->respond($url_data['path'],200); 

        }
        
        
            if ($b_id) {
                $response = [
                    'ret_data' => 'success',
                    'id' => $b_id,
                ];
            }
         else {
            $response['Message'] = 'Network Error';
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
        $response['ret_data'] = 'error';
        $bannermasterModel = new AdvertisementmasterModel();

        $b_data = $this->request->getVar('banner_data');
        if ($b_data) {
            $b_id = $b_data->ads_id;
            $update_banner = [
                'ads_name' => $b_data->ads_name,
                'ads_desc' => $b_data->ads_desc,
                'ads_image' => $b_data->ads_image,
                'ads_type' => $b_data->ads_type,
                'ads_active_flag' => 0,
                'ads_updated_by' => $tokendata['uid'], 
            ];
            $results_banner = $bannermasterModel->update($b_id, $update_banner);
            if ($results_banner) {
                $response = [
                    'ret_data' => 'success'
                ];
            } else {
                $response = [
                    'Message' => 'cant update try again'
                ];
            }
            return $this->respond($response, 200);
        } else {
            $response['Message'] = 'No  Data Provided';
            return $this->respond($response, 200);
        }
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
        $bannermasterModel = new AdvertisementmasterModel();
        if ($this->request->getVar('ads_id')) {
            
            $update_banner = [
                'ads_active_flag' => 1,
                'ads_updated_by' => $tokendata['uid'], 
            ];
            $results_banner = $bannermasterModel->update($this->request->getVar('ads_id'), $update_banner);
            if ($results_banner) {
                $response = [
                    'ret_data' => 'success'
                ];
            } else {
                $response = [
                    'Message' => 'cant update try again'
                ];
            }
        }else{
            $response = [
                'Message' => 'No details Provided'
            ];
        }
        return $this->respond($response, 200);
      
    }

    public function ban_img_upload($banimage)
    {
        $commonutils = new Commonutils();
            if ($banimage != '') {
                
                $image_parts = explode(";base64,", $banimage);
               
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_name = date("d-m-Y") . "-" . time() . "." . $image_type_aux[1];
                $img_data = $commonutils->image_upload(base64_decode($image_parts[1]), $image_name, 'banner_images', 'image/' . $image_type_aux[1], false);
                
                $img_url = $img_data?"banner_images/" . $image_name:"";
                
                $data = [
                    'ret_data'=>"success",
                    'path' => $img_url,
                    'image_data'=>$img_data
                ];
                return $data;
                
            } 
            
    }

    public function banner_img_upload()
    {
        

        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
           helper(['form', 'url']);
           $UserModel = new UsersModel();
           $validModel = new Validation();
           $commonutils = new Commonutils();
            $imageFile = $this->request->getFile('banimage');
            $imageFile->move(ROOTPATH . 'public/uploads/banner_images');
            $data = [
                'img_name' => $imageFile->getName(),
                'file'  => $imageFile->getClientMimeType(),
                'path' => 'uploads/banner_images/'.$imageFile->getName(),
                'test' => $this->request->getVar("test_data"),
            ];
            $data['ret_data'] = "success";

            return $this->respond($data, 200);
       
    }
}
