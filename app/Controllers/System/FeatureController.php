<?php

namespace App\Controllers\System;

use App\Models\Customer\CustomerMasterModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Config\Commonutils;
use Config\Validation;
use CodeIgniter\HTTP\Request;
use App\Models\System\FeatureMasterModel;
use App\Models\System\FeatureActionModel;
use App\Models\User\UsersModel;
use App\Models\System\UserRoleModel;
use App\Models\System\FeatureMappingModel;
use App\Models\System\FeatureActionMappingModel;



class FeatureController extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    public function fetch_feature()
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
        $featureaction = $famodel->where('fa_delete_flag',0)->findAll();
        $featurelist = $ftmodel->where('ft_delete_flag',0)->findAll();
        $response['ret_data'] = 'success';
        $response['fe_list'] = $featurelist;
        $response['fe_action'] = $featureaction;
        return $this->respond($response, 200);
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
}
