<?php

namespace App\Controllers\WorkCard;

use CodeIgniter\RESTful\ResourceController;
use App\Controllers\Quote\QuoteMasterController;
use App\Models\Approval\ApprovalmasterModel;
use CodeIgniter\API\ResponseTrait;
use App\Models\ServiceRequest\ServiceRequestModel;
use App\Models\WorkCard\WorkCardDetailsModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerVehicleModel;
use App\Models\Packages\ServiceRequestPackageModel;
use App\Models\Payment\PaymentHistoryModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\Quotation\QuoteItemsModel;
use App\Models\Quotation\QuoteMasterModel;
use App\Models\ServiceRequest\ServiceRequestDetailsModel;
use App\Models\ServiceRequest\ServiceRequestAssignedModel;
use App\Models\Request\RequesMasterModel;
use App\Models\Request\RequestStatusMasterModel;
use App\Models\Sequence\SequenceGeneratorModel;
use App\Models\ServiceRequest\ServiceRequestHistoryModel;
use App\Models\ServiceRequest\ServiceRequestItemsModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\System\NotificationmasterModel;
use App\Models\System\WorkCardSettingsModel;
use App\Models\User\UsersModel;
use CodeIgniter\Validation\Validation as ValidationValidation;
use Config\Commonutils;
use Config\Validation;

class WorkCardSettingsController extends ResourceController
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
        $WorkcardsettingsModel=new WorkCardSettingsModel();
        $result = $WorkcardsettingsModel->first();
       
        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No settings';
            return $this->respond($response, 200);
        }
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
     
        $workcardsettingsModel=new WorkCardSettingsModel();
        $inData = [
            'ws_rp_days' => $this->request->getVar('ws_rp_days')
        ];

        $res = $workcardsettingsModel->update($this->request->getVar("ws_id"), $inData);
        $result=[
            'ws_rp_days'=>$this->request->getVar('ws_rp_days')
        ];
        if ($res) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
       
        } else {
            $response['ret_data'] = "fail";
       
        }
        return $this->respond($response, 200);
       
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
