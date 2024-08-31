<?php

namespace App\Controllers\ToolRequest;

use App\Models\Customer\CustomerMasterModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use App\Models\ToolRequest\ToolRequestTrackerModel;
use App\Models\User\UsersModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use Config\Validation;

class ToolRequestrackerControler extends ResourceController
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
        $trequestrackerModel = new ToolRequestTrackerModel();
        $result = $trequestrackerModel->where('trt_delete_flag', 0)->where('trt_rq_id', base64_decode($id))->join('tool_request_details', 'tldet_id=trt_rq_id')->findAll();
        if ($result) {
            $response = [
                'ret_data' => 'success',
                'result' => $result
            ];
        } else {
            $response['Message'] = 'No data Found';
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
        $trequestrackerModel = new ToolRequestTrackerModel();
        $user_data = $this->request->getVar('custdata');
        if ($user_data) {
            $insert_data = [
                'cstm_name' => $user_data->customername,
                'cstm_email' => $user_data->email,
                'cstm_phone' => $user_data->customerphone,
                'cstm_cstp_id' => $user_data->custtype,
            ];

            $us_id = $trequestrackerModel->insert($insert_data);
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
        $trequestrackerModel = new ToolRequestTrackerModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $date = date("Y-m-d H:i:s");
        $insert_data = [
            'trt_rq_id' => $this->request->getVar("trt_rq_id"),
            'trt_type' => $this->request->getVar("trt_type"),
            'trt_notes' => $this->request->getVar("trt_notes"),
            'trt_url' => $this->request->getVar("trt_url"),
            'trt_created_by' => $tokendata['uid'],
            'trt_created_on' => $date,
            'trt_updated_by' => $tokendata['uid'],
            'trt_updated_on' => $date,

        ];
        $us_id = $trequestrackerModel->insert($insert_data);
        if ($us_id) {
            $hist = [
                'trqh_tr_id' => $this->request->getVar("trt_rq_id"),
                'trqh_status_id' => 42,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],

            ];
            $mast = [
                'tldt_status' => 42,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $ToolRequestDetailsModel->update($this->db->escapeString($this->request->getVar("trt_rq_id")), $mast);
            $us_id = $ToolRequestHistoryModel->insert($hist);
            $response = [
                'ret_data' => 'success',

            ];
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = 'success';
            $response['Message'] = 'Status not updated';
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
        //
    }
    public function tlrq_img_upload()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        // $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        // if ($tokendata['aud'] == 'customer') {
        //     $custModel = new CustomerMasterModel();
        //     $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
        //     if (!$customer) return $this->fail("invalid user", 400);
        // }else if($tokendata['aud'] == 'user'){
        //     $userModel = new UsersModel();
        //     $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
        //     if (!$users) return $this->fail("invalid user", 400);
        // }else{
        //     return $this->fail("invalid user", 400);
        // }
        helper(['form', 'url']);
        $UserModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();

        $imageFile = $this->request->getFile('toolimage');
        $imageFile->move(ROOTPATH . 'public/uploads/ToolrequestTracker_images');
        $data = [
            'img_name' => $imageFile->getName(),
            'file'  => $imageFile->getClientMimeType(),
            'path' => 'uploads/ToolrequestTracker_images/' . $imageFile->getName(),
            'test' => $this->request->getVar("test_data"),
        ];
        $data['ret_data'] = "success";

        return $this->respond($data, 200);
        // } else {
        //     $data['ret_data'] = "Invalid user";
        //     return $this->fail($data, 400);
        // }
    }
}
