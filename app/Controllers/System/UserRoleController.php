<?php

namespace App\Controllers\System;

use App\Models\Customer\CustomerMasterModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\System\FeatureMasterModel;
use App\Models\System\FeatureActionModel;
use App\Models\System\UserRoleModel;
use App\Models\System\FeatureMappingModel;
use App\Models\System\FeatureActionMappingModel;
use App\Models\User\UsersModel;
use Config\Commonutils;
use Config\Validation;
use CodeIgniter\HTTP\Request;




class UserRoleController extends ResourceController
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
        $userroleModel = new UserRoleModel();
        $userroles = $userroleModel
        ->where('role_delete_flag', 0)
        ->orderBy('role_Id','desc')
        ->findAll();


        $response = [
            'ret_data' => "Error"
        ];
        if ($userroles) {
            $response = [
                'UserRoles' => $userroles,
                'ret_data' => "success"
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
        $famodel = new FeatureActionModel();
        $ftmodel = new FeatureMasterModel();
        $userroleModel = new UserRoleModel();
        $featuremappingModel = new FeatureMappingModel();
        $featureactionmappingmodel = new FeatureActionMappingModel();
        $response['ret_data'] = 'error';


        $userrole = $userroleModel->where("role_id", base64_decode($id))->where("role_delete_flag", 0)
            ->select('role_id,role_name,role_description')->first();

        $builder = $this->db->table('feature_role_mapping');
        $builder->select('feature_list.ft_id,feature_list.ft_name,feature_actions.fa_id,feature_actions.fa_name,user_roles.role_name');
        $builder->where('frm_role_id', base64_decode($id));
        $builder->join('user_roles', 'user_roles.role_id = feature_role_mapping.frm_role_id', 'INNER JOIN');
        $builder->join('feature_list', 'feature_list.ft_id =feature_role_mapping.frm_feature_id', 'INNER JOIN');
        $builder->join('feature_actions', 'feature_actions.fa_id=feature_role_mapping.frm_action_id', 'INNER JOIN');
        $builder->orderBy('frm_feature_id');
        $query = $builder->get();
        $features = $query->getResultArray();


        if ($userrole) {
            $data['ret_data'] = "success";
            $data['userrole'] = $userrole;
            $data['feature'] = $features;
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
        $rules = [
            'rname' => 'required',
            'features' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $userroleModel = new UserRoleModel();
        $featuremappingModel = new FeatureMappingModel();
        $features = $this->request->getVar('features');
        $indata = [
            'role_name'    => $this->request->getVar('rname'),
            'role_description'   => $this->request->getVar('rdesc'),
            'role_created_by'    => $tokendata['uid'],
            'role_updated_by'   => $tokendata['uid']
        ];
        $results = $userroleModel->insert($indata);
        if ($results) {
            foreach ($features as $feature) {
                $in_data = array();
                for ($i = 0; $i < count($feature->actions); $i++) {

                    $infdata = [
                        'frm_role_id'   => $results,
                        'frm_feature_id' => $feature->featureId,
                        'frm_action_id' => $feature->actions[$i],
                    ];
                    array_push($in_data, $infdata);
                }
                $ret = $featuremappingModel->insertBatch($in_data);
            }
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->respond($data, 200);
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
        $rules = [
            'roleid' => 'required',
            'rname' => 'required',
        ];
        $features = $this->request->getVar("features");
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $indata = [
            'role_name'    => $this->request->getVar('rname'),
            'role_groupid'    => $this->request->getVar('groupid'),
            'role_description'   => $this->request->getVar('rdesc'),
            'role_created_by'    => $tokendata['uid'],
            'role_updated_by'   => $tokendata['uid']
        ];
        $userroleModel = new UserRoleModel();
        $featuremappingModel = new FeatureMappingModel();

        $results = $userroleModel->update($this->request->getVar('roleid'), $indata);

        if ($results) {
            $ret_result = $featuremappingModel->where('frm_role_id', $this->request->getVar('roleid'))->delete();

            foreach ($features as $feature) {
                $in_data = array();
                for ($i = 0; $i < count($feature->actions); $i++) {

                    $infdata = [
                        'frm_role_id'   => $this->request->getVar('roleid'),
                        'frm_feature_id' => $feature->featureId,
                        'frm_action_id' => $feature->actions[$i],
                    ];
                    array_push($in_data, $infdata);
                }
                $ret = $featuremappingModel->insertBatch($in_data);
            }
            if ($ret) {
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "failed to update feature details";
                return $this->fail($data, 400);
            }
        } else {
            $data['ret_data'] = "failed to update role details";
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
    public function fetch_user_role_features()
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
        $famodel = new FeatureActionModel();
        $ftmodel = new FeatureMasterModel();
        $userroleModel = new UserRoleModel();
        $featuremappingModel = new FeatureMappingModel();
        $featureactionmappingmodel = new FeatureActionMappingModel();
        $response['ret_data'] = 'error';
        $roleid = $this->request->getVar('roleId');


        if ($roleid) {
            $features = $userroleModel->where('role_Id', $roleid)->first();
            if ($features) {
                $features['data'] = $featuremappingModel
                    ->where('frm_role_id', $roleid)
                    ->join('feature_list', 'ft_id=frm_feature_id')
                    ->join('feature_actions', 'fa_id=frm_action_id')
                    ->findAll();
            }
            if ($features) {
                $response = [
                    'features' => $features,
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
        } else {

            $response = [
                'ret_data' => 'No data found'
            ];
            return $this->respond($response, 404);
        }

        return $this->respond($response, 200);
    }

    public function delete_user_role()
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
        $userroleModel = new UserRoleModel();
        $roleid = $this->request->getVar('roleid');
        $response['ret_data'] = 'fail';
        if ($roleid) {
            $data = [
                'role_delete_flag' => 1
            ];
            $results = $userroleModel->update($this->db->escapeString($roleid), $data);
            if ($results) {
                $response = [
                    'ret_data' => 'success',
                    'roleid' =>  $roleid
                ];
                return $this->respond($response, 200);
            }
        }
        return $this->respond($response, 404);
    }
}
