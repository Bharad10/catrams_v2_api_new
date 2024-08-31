<?php

namespace App\Controllers\System;

use App\Models\Customer\CustomerMasterModel;
use App\Models\Packages\ServiceRequestPackageModel;
use App\Models\System\ServicesMappingModel;
use App\Models\ToolRequest\ToolDetailsModel;
use App\Models\User\UsersModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use Config\Validation;
class ServiceMasterController extends ResourceController
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
        $ServiceRequestPackageModel = new ServiceRequestPackageModel();
        $servicesmappingModel=new ServicesMappingModel();
        $TooldetailsModel=new ToolDetailsModel();
        $data = $ServiceRequestPackageModel
        ->where('servpack_delete_flag', 0)
        ->orderBy('servpack_id', 'DESC')
        ->findAll();
        if ($data) {

           for($i=0;$i<sizeof($data);$i++){
            $map_d=[];
            $map_d=$servicesmappingModel->where('srm_delete_flag',0)
            ->where('srm_servpack_id',$data[$i]['servpack_id'])
            ->join('tool_details','tool_id=srm_tool_id')
            ->findAll();
            $data[$i]['tools']=$map_d?$map_d:0;
           }

            $response = [
                'ret_data' => 'success',
                'all_services' => $data,
            ];
        } else {
            $response['Message'] = 'Service packages not found';
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
        $response['ret_data'] = 'error';
        $ServicePackageModel = new ServiceRequestPackageModel();
        $servicesmappingModel= new ServicesMappingModel();
        $TooldetailsModel=new ToolDetailsModel();
        $data = $ServicePackageModel->where('servpack_id', base64_decode($id) )->first();
        $tool_data=$TooldetailsModel->where('tool_delete_flag',0)->findall();
            $map_d=$servicesmappingModel->where('srm_delete_flag',0)
            ->where('srm_servpack_id',$data['servpack_id'])
            ->join('tool_details','tool_id=srm_tool_id')
            ->findAll();
            $data['tools']=$map_d ? $map_d:0;

        $response = $data 
        ?   [
            'ret_data' => 'success',
            'Service_Request_details' => $data,
            'tool_data'=>$tool_data]
        :
            ['Message'=>'error fetching details'];

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
        $rules = [
            'servpack_name' => 'required',
            'servpack_cost' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $ServicePackageModel = new ServiceRequestPackageModel();
        $servicesmappingModel=new ServicesMappingModel();
            $data_updt = [
                'servpack_name' =>$this->request->getVar('servpack_name'),
                'servpack_desc' => $this->request->getVar('servpack_desc'),
                'servpack_cost' => $this->request->getVar('servpack_cost'),
            ];
            $results = $ServicePackageModel->insert( $data_updt);

            if($this->request->getVar('tools')&& sizeof($this->request->getVar('tools'))>0){
                $infdata=[];
                foreach($this->request->getVar('tools') as $eachurl){
                  $indata=[
                    'srm_servpack_id'=>$results,
                    'srm_tool_id'=>$eachurl->tool_id,
                    'srm_total_cost'=>$eachurl->tool_cost,
                    'srm_created_by'=>$tokendata['uid']
                  ];
       
                  array_push($infdata,$indata);
                }

                $servicesmappingModel->insertBatch($infdata);
            }

            $response=$results
            
            ?
                [
                    'ret_data'=>'success',
                    'servpack_id'=>$results,
                    'servpack_det'=>$data_updt
                ]
            :
                [
                    'ret_data'=>'error'
                ];
            
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
        $rules = [
            'serv_id' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $ServicePackageModel = new ServiceRequestPackageModel();
        $servicesmappingModel=new ServicesMappingModel();
            $data_updt = [
                'servpack_name' =>$this->request->getVar('servpack_name'),
                'servpack_desc' => $this->request->getVar('servpack_desc'),
                'servpack_cost' => $this->request->getVar('servpack_cost'),
            ];
            $results = $ServicePackageModel->update( $this->request->getVar('serv_id'), $data_updt);

            $servdata=$servicesmappingModel
            ->where('srm_delete_flag',0)
            ->where('srm_servpack_id',$this->request->getVar('serv_id'))
            ->findAll();
                 
            foreach($servdata as $servdata){
                $dlt_data=[
                    'srm_delete_flag'=>1
                ];
               $servicesmappingModel->update($servdata['srm_id'],$dlt_data);
            }
            if($this->request->getVar('tools')&& sizeof($this->request->getVar('tools'))>0){
                $infdata=[];
                foreach($this->request->getVar('tools') as $eachurl){
                        $indata=[
                            'srm_servpack_id'=>$this->request->getVar('serv_id'),
                            'srm_tool_id'=>$eachurl->tool_id,
                            'srm_total_cost'=>$eachurl->tool_cost,
                            'srm_created_by'=>$tokendata['uid'],
                          ];
                          array_push($infdata,$indata);
                }
                $servicesmappingModel->insertBatch($infdata);
            }
         
            $response=$results
            ?
                [
                    'ret_data'=>'success',
                    'servpack_id'=>$results
                ]
            :
                [
                    'ret_data'=>'error'
                ];
        
        return $this->respond($response, 200);
      
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id=null)
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
            $ServicePackageModel = new ServiceRequestPackageModel();
            $data=[
                'servpack_delete_flag'=>1
            ];
            $ServicePackageModel->update($this->request->getVar('serv_id'),$data);
            $response['ret_data']='success';
            return $this->respond($response, 200);
        
    }
}
