<?php

namespace App\Controllers\Chat;

use App\Controllers\System\UsersNotificationController;
use App\Models\Chat\ChatDetailsModel;
use App\Models\Chat\ServicesChatModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\System\NotificationmasterModel;
use App\Models\User\UsersModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Validation;
use Config\Commonutils;

class ChatMasterController extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        //
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
        $custModel = new CustomerMasterModel();
        $userModel = new UsersModel();

        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'customer') {
            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else
         if ($tokendata['aud'] == 'user') {
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        
        $chatdetailsModel=new ChatDetailsModel();
        

        $c_data=
        $chatdetailsModel->where('c_us_type',0)->where('c_staff_id',base64_decode($id))->orderBy('desc')->findAll();
        $indata=[];
        for($i=0;$i<sizeof($c_data);$i++){
            $c_data[$i][]=$custModel->where('cstm_id',$c_data['c_customer_id'])->first();
        }

        $response=['ret_data'=>'success',$chat_hist=$c_data];
        


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
        $custModel = new CustomerMasterModel();
        $userModel = new UsersModel();


        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'customer') {
            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else
         if ($tokendata['aud'] == 'user') {
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        
        $rules =($tokendata['aud'] == 'user')?
        [
            'c_staff_id' => 'required',
            'c_message_type' => 'required',
            'c_message' => 'required',
            'c_customer_id' => 'required',

        ]:
        [
            'c_customer_id' => 'required',
            'c_message_type' => 'required',
            'c_message' => 'required',

        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $chatdetailsModel=new ChatDetailsModel();
        $notificationmasterModel= new NotificationmasterModel();
        $notificationmasterController= new UsersNotificationController;
        $date = date("Y-m-d H:i:s");

        $c_data=($tokendata['aud'] == 'user')?
        [
            'c_customer_id' =>   $this->request->getVar('c_customer_id'),
            'c_staff_id' =>   $this->request->getVar('c_staff_id'),
            'c_message_type' =>  $this->request->getVar('c_message_type'),
            'c_message' =>  $this->request->getVar('c_message'),
            'c_us_type' =>  0,
            'c_created_on' =>  $date,
            'c_updated_on' =>  $date,
            'c_status' =>  0,
        ]:
        [
            'c_customer_id' =>   $this->request->getVar('c_customer_id'),
            'c_message_type' =>  $this->request->getVar('c_message_type'),
            'c_message' =>  $this->request->getVar('c_message'),
            'c_us_type' =>  1,
            'c_created_on' =>  $date,
            'c_updated_on' =>  $date,
            'c_status' =>  0,
            'c_staff_id' =>   1,


        ];

        $c_id=$chatdetailsModel->insert($c_data);


        

        if($tokendata['aud'] == 'user')
        {
       
        $target_cust = $custModel->where('cstm_id', $this->request->getVar('c_customer_id'))->first();
        $player_id = [];
        $custhead = "RAMS- Support Chat!!";
        $custcontent =  $this->request->getVar('c_message') ;
        
    
            
            $notif_data = [
                'sourceid' => $this->request->getVar('c_staff_id'),
                'destid' => $this->request->getVar('c_customer_id'),
                'nt_req_number' => null,
                'id' => $this->request->getVar('c_customer_id'),
                'nt_sourcetype' => 1,
                'headers' => $custhead,
                'content' => $custcontent,
                'date' => $date,
                'nt_type'=>1,
                'nt_request_type'=>null,
                'nt_type_id'=>null
            ];
            $notificationmasterController->create_cust_notification($notif_data);
        
        
    }else{
       
        $custhead = "RAMS- Support Chat!!";
        $custcontent =  $this->request->getVar('c_message') ;
        $notif=[];
        $notif_data = [

            'sourceid' => $this->request->getVar('c_customer_id'),
            'destid' => 1,
            'nt_req_number' => null,
            'id'=>1,
            'nt_sourcetype' => 1,
            'headers' => $custhead,
            'content' => $custcontent,
            'date' => $date,
            'nt_type'=>1,
            'nt_request_type'=>null,
            'nt_type_id'=>null
        ];
        array_push($notif,$notif_data);
        $notificationmasterController->create_us_notification($notif);

    }

        $c_hist= ($tokendata['aud'] == 'user')?
        $chatdetailsModel->where('c_customer_id',$this->request->getVar('c_customer_id'))
        ->where('c_staff_id',$tokendata['uid'])
        ->orderBy('c_id', 'desc')
        ->join('customer_master','cstm_id=c_customer_id','left')
        ->join('users','us_id=c_staff_id','left')
        ->first():
        $chatdetailsModel->where('c_customer_id',$this->request->getVar('c_customer_id'))
        ->orderBy('c_id', 'desc')
        ->join('customer_master','cstm_id=c_customer_id','left')
        ->join('users','us_id=c_staff_id','left')
        ->first();

        $response=$c_id?
        
        ['ret_data'=>'success','chat_data'=>$c_hist]:
        ['ret_data'=>'fail','Message'=>'Servor Error!!!Please Try Again!!'];

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

    public function get_chat_history()
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $custModel = new CustomerMasterModel();
        $userModel = new UsersModel();

        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'customer') {
            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else
         if ($tokendata['aud'] == 'user') {
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $rules =
        [
            
            'c_customer_id' => 'required',
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        
        $chatdetailsModel=new ChatDetailsModel();
        

        
        $c_data= ($tokendata['aud'] == 'user')?
        $chatdetailsModel->where('c_customer_id',$this->request->getVar('c_customer_id'))
        ->where('c_staff_id',$tokendata['uid'])
        
        ->join('customer_master','cstm_id=c_customer_id','left')
        ->join('users','us_id=c_staff_id','left')
        ->findAll():
        $chatdetailsModel->where('c_customer_id',$this->request->getVar('c_customer_id'))
        
        ->join('customer_master','cstm_id=c_customer_id','left')
        ->join('users','us_id=c_staff_id','left')
        ->findAll();
        
        $response=(sizeof($c_data)>0)?

        ['ret_data'=>'success','chat_hist'=>$c_data]:
        ['ret_data'=>'fail','Message'=>'No Chat History!!'];
        return $this->respond($response, 200);


    }

    public function get_recent_serv_chat($req_id,$type)

    {
        
        $chatdetailsModel = new ServicesChatModel();


        if($type==0){
            $c_details = $chatdetailsModel
            ->where('sc_delete_flag', 0)
            ->where('sc_req_type', 0)
            ->where('sc_id', $req_id)
            ->join('customer_master', 'cstm_id=sc_customer_id', 'left')
            ->join('users', 'us_id=sc_staff_id', 'left')
            ->first();
        }else if ($type==1){
            $c_details = $chatdetailsModel
            ->where('sc_delete_flag', 0)
            ->where('sc_req_type', 1)
            ->where('sc_id', $req_id)
            ->join('customer_master', 'cstm_id=sc_customer_id', 'left')
            ->join('users', 'us_id=sc_staff_id', 'left')
            ->first();
        }else{
            $c_details = $chatdetailsModel
            ->where('sc_delete_flag', 0)
            ->where('sc_req_type', 2)
            ->where('sc_id', $req_id)
            ->join('customer_master', 'cstm_id=sc_customer_id', 'left')
            ->join('users', 'us_id=sc_staff_id', 'left')
            ->first();
        }
        

        $response = sizeof($c_details) > 0 ?

            $c_details
            :
            [];

        return ($response);
    }

    public function c_img_upload()
    {
        
        helper(['form', 'url']);
        $commonutils = new Commonutils();

        $imageFile = $this->request->getFile('c_image');
        $imageFile->move(ROOTPATH . 'public/uploads/Chat/service_based/Photo');
        
        $data = [
            'img_name' => $imageFile->getName(),
            'file'  => $imageFile->getClientMimeType(),
            'path' => 'uploads/Chat/service_based/Photo/' . $imageFile->getName(),
            'test' => $this->request->getVar("test_data"),
        ];
        $data['ret_data'] = "success";

        return $this->respond($data, 200);

        
       
    }
    public function c_img_cUpload()
    {
        $commonutils = new Commonutils();
           
           $chatImage = $this->request->getVar('c_image');
            if ($chatImage != '') {
                $image_parts = explode(";base64,", $chatImage);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_name = date("d-m-Y") . "-" . time() . "." . $image_type_aux[1];
                $img_data = $commonutils->image_upload(base64_decode($image_parts[1]), $image_name, 'chat/service_based/photo', 'image/' . $image_type_aux[1], false);
                $img_url = $img_data?"chat/service_based/photo/" . $image_name:"";
                $data = [
                    'ret_data'=>"success",
                    'path' => $img_url,
                    'image_data'=>$img_data
                ];
                return $this->respond($data,200) ;
            } else{
                $data = [
                    'ret_data'=>"error",
                    'path'=>'',
                    'image_data'=>$chatImage,
                    'Message'=>'No image data'
                ];
                return $this->respond($data,200);
            }

        
       
    }
    public function c_aud_upload()
    {
        $heddata = $this->request->headers();
        helper(['form', 'url']);
        $UserModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();

        $imageFile = $this->request->getFile('c_audio');
        $imageFile->move(ROOTPATH . 'public/uploads/Chat/service_based/Audio');
        
        $data = [
            'img_name' => $imageFile->getName(),
            'file'  => $imageFile->getClientMimeType(),
            'path' => 'uploads/Chat/service_based/Audio/' . $imageFile->getName(),
            'test' => $this->request->getVar("test_data"),
        ];
        $data['ret_data'] = "success";

        return $this->respond($data, 200);
       
    }
    public function c_aud_cUpload()
    {
        $commonutils = new Commonutils();
           
        $chatAudio = $this->request->getVar('c_audio');
         if ($chatAudio != '') {
             $audio_parts = explode(";base64,", $chatAudio);
             $audio_type_aux = explode("audio/", $audio_parts[0]);
             $audio_extension = $audio_type_aux[1];
             $audio_name = date("d-m-Y") . "-" . time() . "." . $audio_extension;
             $audio_data = base64_decode($audio_parts[1]);
             $aud_data = $commonutils->image_upload($audio_data, $audio_name, 'chat/service_based/audio', 'audio/' . $audio_extension, false);
             $audio_url = $aud_data ? "chat/service_based/audio/" . $audio_name : "";         
             $data = [
                 'ret_data'=>"success",
                 'path' => $audio_url,
             ];
             return $this->respond($data,200) ;
         } 
    }


    public function c_doc_upload()
    {
        $heddata = $this->request->headers();
        helper(['form', 'url']);
        $UserModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();

        $imageFile = $this->request->getFile('c_document');
        $imageFile->move(ROOTPATH . 'public/uploads/Chat/service_based/Documents');
        
        $data = [
            'img_name' => $imageFile->getName(),
            'file'  => $imageFile->getClientMimeType(),
            'path' => 'uploads/Chat/service_based/Documents/' . $imageFile->getName(),
            'test' => $this->request->getVar("test_data"),
        ];
        $data['ret_data'] = "success";

        return $this->respond($data, 200);
       
    }

    public function c_doc_cUpload()
    {
        $commonutils = new Commonutils();
           
        $chatDocuments = $this->request->getVar('c_document');
         if ($chatDocuments != '') {
             $document_parts = explode(";base64,", $chatDocuments);
             $mime_type = $document_parts[0];
             $document_type_aux = explode("/", $mime_type);
             $document_extension = $document_type_aux[1];
             $document_name = date("d-m-Y") . "-" . time() . "." . $document_extension;
             $document_data = base64_decode($document_parts[1]);
             $document = $commonutils->image_upload($document_data, $document_name, 'chat/service_based/documents', $mime_type, false);
             $document_url = $document ? "chat/service_based/documents/" . $document_name : "";
             
             $data = [
                 'ret_data'=>"success",
                 'path' => $document_url,
             ];
             return $this->respond($data,200) ;
         } 
    }
}