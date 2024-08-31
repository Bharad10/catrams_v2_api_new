<?php

namespace App\Controllers\System;

use App\Models\Customer\CustomerMasterModel;
use App\Models\System\ToolTrackerModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolDetailsModel;
use App\Models\User\UsersModel;
use Config\Commonutils;
use Config\Validation;
use CodeIgniter\HTTP\Request;

class ToolMasterController extends ResourceController
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
        // return $this->fail("invalid user", 400);
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


        $ToolDetailsModel = new ToolDetailsModel();
        $tool_list = $ToolDetailsModel
        ->select('tool_id,
                 tool_created_on,
                 tool_description,
                 tool_images,
                 tool_rent_quantity,
                 tool_sale_quantity,
                 tool_total_quantity,
                 tool_name,
                 tool_rent_id,
                 tool_deposit_id,
                 tool_deposit_price,
                 tool_adv_payment,
                 tool_adv_price,
                 tool_cost,
                 tool_rent_cost')
        ->where('tool_delete_flag', 0)->get()->getResult();
        if ($tool_list) {
            $response = [
                'ret_data' => 'success',
                'tool_list' => $tool_list,
                'image_url'=>getenv('AWS_URL')
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'Message' => 'No Tools found',
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

        $ToolDetailsModel = new ToolDetailsModel();
        $tool_det = $ToolDetailsModel
            ->where('tool_active_flag', 0)
            ->where('tool_id', base64_decode($id))
            ->first();
        if ($tool_det) {
            $response = [
                'ret_data' => 'success',
                'tool_details' => $tool_det,
                'imageurl'=>getenv('AWS_URL')
            ];
        } else {
            $response = [
                'Message' => 'No tool data found'
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

    public function tool_create()
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
        $response['ret_data'] = 'error';
        $ToolDetailsModel = new ToolDetailsModel();

            $url_data= $this->request->getVar('base_version')==='local'?$this->request->getVar('imageurl'): $this->imageCloudUpload($this->request->getVar('imageurl'));
          
            $create_tool = [
                'tool_name' => $this->request->getVar('toolname'),
                'tool_description' =>$this->request->getVar('tooldescription'),
                'tool_images' => $this->request->getVar('base_version')==='local'?$url_data:$url_data['path'],
                'tool_total_quantity' => $this->request->getVar('availablequantity'),
                'tool_cost' =>  $this->request->getVar('price'),
                'tool_sale_quantity' =>  $this->request->getVar('salestock'),
                'tool_active_flag' => '0',
                'tool_rent_id' =>  $this->request->getVar('renttool_id'),
                'tool_rent_quantity' =>  $this->request->getVar('rentstock'),
                'tool_rent_cost' => $this->request->getVar('rentprice'),
                'tool_adv_payment' => $this->request->getVar('advanceflag'),
                'tool_adv_price' =>  $this->request->getVar('advancePayment'),
                'tool_delay_percentage' => $this->request->getVar('rentdelay'),
                'tool_deposit_id' =>  $this->request->getVar('depositflag'),
                'tool_deposit_price' => $this->request->getVar('depositamount'),
                'tool_created_on'=>$date,
                'tool_updated_on'=>$date,
                'tool_created_by'=>$tokendata['uid']
            ];
            $builder = $this->db->table('tool_details');
            $tool_id = $builder->insertBatch($create_tool);
            // $tool_id = $ToolDetailsModel->insert($create_tool);
            if ($tool_id) {
                $response = [
                    'ret_data' => 'success',
                    'id' => $tool_id
                ];
                return $this->respond($response, 200);
            }
        
    }
    // public function fetch_tool_details()
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
    //     $response['ret_data'] = 'error';
    //     $ToolDetailsModel = new ToolDetailsModel();
    //     $toolid = $this->request->getVar('toolid');
    //     if ($toolid) {
    //         $tool_det = $ToolDetailsModel
    //         ->where('tool_id', $toolid)->first();
    //         if ($tool_det) {
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'tool_details' => $tool_det
    //             ];
    //             return $this->respond($response, 200);
    //         } else {
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'Message' => 'No tool data found'
    //             ];   
    //         }
    //     } else {
    //         $response = [
    //             'ret_data' => 'success',
    //             'Message' => 'No tool selected'
    //         ];

    //     }
    //     return $this->respond($response, 200);
    // }

    public function update_tool_details()
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
        $ToolDetailsModel = new ToolDetailsModel();
        $tool_data = $this->request->getVar('tool_data');
        if ($tool_data) {
            $tool_id = $tool_data->toolid;
            $update_tool = [
                'tool_name' => $tool_data->toolname,
                'tool_description' => $tool_data->tooldescription,
                'tool_images' => $tool_data->imageurl,
                'tool_rent_quantity' => $tool_data->tool_rent_quantity,
                'tool_sale_quantity' => $tool_data->tool_total_quantity,
                'tool_cost' => $tool_data->tool_cost,
                'tool_active_flag' => '0',
                'tool_deposit_id' => $tool_data->tool_deposit_id,
                'tool_deposit_price' => $tool_data->tool_deposit_price,
                'tool_adv_payment' => $tool_data->tool_adv_payment,

            ];
            $results_tool = $ToolDetailsModel->update($this->db->escapeString($tool_id), $update_tool);
            if ($results_tool) {
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
            $response['Message'] = 'No Tool Data Provided';
            return $this->respond($response, 200);
        }
    }
    public function delete_tool_pack()
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
        $ToolDetailsModel = new ToolDetailsModel();
        $tool_id = $this->request->getVar('toolId');
        if ($tool_id) {
            $update_data = [
                'tool_delete_flag' => 1
            ];
            $results_tool = $ToolDetailsModel->update($this->db->escapeString($tool_id), $update_data);
        } else {
            $response['Message'] = 'No tool id found';
            return $this->respond($response, 200);
        }
        if ($results_tool) {
            $response = [
                'ret_data' => 'success',
                'Message' => 'Tool deleted succesfuly'
            ];
        } else {
            $response['Message'] = 'Error in deletion.';
        }
        return $this->respond($response, 200);
    }

    public function update_tool_status()
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
        $ToolDetailsModel = new ToolRequestDetailsModel();
        $actv_flag = $this->request->getVar('tool_active_flag');
        $tool_data = $this->request->getVar('tool_data');
        // return $this->respond($tool_data, 200);

        if ($actv_flag == '0') {
            $update_data = [
                'tool_active_flag' => 1,
            ];
        } else {
            $update_data = [
                'tool_active_flag' => 0,
            ];
        }
        $results_tool = $ToolDetailsModel->update($this->db->escapeString($this->request->getVar('tool_id')), $update_data);
        if ($results_tool) {
            $response['ret_data'] = 'success';
        } else {
            $response['Message'] = 'Error in updating status';
        }

        return $this->respond($response, 200);
    }

    public function tool_track_list()
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
            'tool_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $tooltrackModel = new ToolTrackerModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $tool_det = $ToolDetailsModel
            ->where('tool_active_flag', 0)
            ->where('tool_id', $this->request->getVar('tool_id'))
            ->join('tool_tracker', 'trk_tool_id=tool_id')
            ->join('customer_master', 'cstm_id=trk_created_by')
            ->orderBy('trk_id', 'desc')
            ->findAll();
        if ($tool_det) {
            $response = [
                'ret_data' => 'success',
                'tool_details' => $tool_det
            ];
        } else {
            $response = [
                'Message' => 'No tool data found'
            ];
        }

        return $this->respond($response, 200);
    }
    public function stock_update()
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
            'data' => 'required',
            'tool_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $toolmasterModel = new ToolDetailsModel();

        $tool_data = $this->request->getVar('data');

        $u_data = [
            'tool_total_quantity' => $tool_data->tool_total_quantity,
            'tool_sale_quantity' => $tool_data->tool_sale_quantity,
            'tool_rent_quantity' => $tool_data->tool_rent_quantity,
        ];
        $id = $toolmasterModel->update($this->request->getVar('tool_id'), $u_data);
        if ($id) {
            $response = [
                'ret_data' => 'success',
            ];
            return $this->respond($response, 200);
        } else {
            return $this->fail("wrong data", 400);
        }
    }

    public function tool_image_upload()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        helper(['form', 'url']);
        $UserModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $imageFile = $this->request->getFile('toolimage');
        $imageFile->move(ROOTPATH . 'public/uploads/Tool_images');
        $data = [
            'img_name' => $imageFile->getName(),
            'file'  => $imageFile->getClientMimeType(),
            'path' => 'uploads/ToolrequestTracker_images/' . $imageFile->getName(),
            'test' => $this->request->getVar("test_data"),
        ];
        $data['ret_data'] = "success";
        return $this->respond($data, 200);
    }
    public function imageCloudUpload($toolimage)
    {
        $commonutils = new Commonutils();
            
            if ($toolimage != '') {
                
                $image_parts = explode(";base64,", $toolimage);
               
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_name = date("d-m-Y") . "-" . time() . "." . $image_type_aux[1];
                $img_data = $commonutils->image_upload(base64_decode($image_parts[1]), $image_name, 'Tool_images', 'image/' . $image_type_aux[1], false);
                
                $img_url = $img_data?"Tool_images/" . $image_name:"";
                $data = [
                    'ret_data'=>"success",
                    'path' => $img_url,
                    'image_data'=>$img_data
                ];
                return $data;
                
            } 
            
    }
    public function tool_image_cUpload()
    {
        $commonutils = new Commonutils();
        $imageFile = $this->request->getvar('toolimage');
        $commonutils = new Commonutils();
            if ($imageFile != '') {
                $image_parts = explode(";base64,", $imageFile);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_name = date("d-m-Y") . "-" . time() . "." . $image_type_aux[1];
                $img_data = $commonutils->image_upload(base64_decode($image_parts[1]), $image_name, 'ToolrequestTracker_images', 'image/' . $image_type_aux[1], false);
                
                $img_url = $img_data?"ToolrequestTracker_images/" . $image_name:"";
                $data = [
                    'ret_data'=>"success",
                    'path' => $img_url,
                    'image_data'=>$img_data
                ];
                
                
            } else{
                $data = [
                    'ret_data'=>"success",
                    'path' => null,
                    'image_data'=>$imageFile
                ];
            }
            return $this->respond($data,200); ;
    }
}
