<?php

namespace App\Controllers\Vendor;

use App\Controllers\Payment\PaymentMasterController;
use App\Controllers\System\UsersNotificationController;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerVehicleModel;
use App\Models\Payment\PaymentHistoryModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\Quotation\QuoteItemsModel;
use App\Models\Quotation\QuoteMasterModel;
use App\Models\Sequence\SequenceGeneratorModel;
use App\Models\ServiceRequest\ServiceRequestHistoryModel;
use App\Models\ServiceRequest\ServiceRequestItemsModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\ServiceRequest\ServiceRequestMediaModel;
use App\Models\StatusMaster\StatusMasterModel;
use App\Models\System\CatsalesHistoryModel;
use App\Models\System\CustomerDiscountModel;
use App\Models\System\NotificationmasterModel;
use App\Models\User\UsersModel;
use App\Models\Vendor\VendorItemsModel;
use App\Models\Vendor\VendorModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use Config\Validation;

class VendorMasterController extends ResourceController
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
        $customermastermodel = new CustomerMasterModel();
        $ServiceRequestMasterModel = new ServiceRequestMasterModel();
        $vendorMasterModel = new VendorModel();
        $servicerequestitemsModel = new ServiceRequestItemsModel();

        $cust_list = $customermastermodel
            ->where('cstm_delete_flag', 0)
            ->where('cstm_vendor_flag', 1)
            ->findAll();
        $total_req = 0;
        $wk_req = 0;
        $wkitems_req = 0;

        for ($i = 0; $i < sizeof($cust_list); $i++) {
            $servicereq_det = $ServiceRequestMasterModel
                ->where('serm_vendor_flag', 1)
                ->where('serm_assigne', $cust_list[$i]['cstm_id'])
                ->findAll();
            $wk_req = sizeof($servicereq_det);
            $serv_items = $servicerequestitemsModel
                ->where('sitem_deleteflag', 0)
                ->where('sitem_assignee_type', 1)
                ->where('sitem_assignee', $cust_list[$i]['cstm_id'])
                ->findAll();
            $wkitems_req = sizeof($serv_items);
            $total_req = $wk_req + $wkitems_req;

            $cust_list[$i]['total_request'] = $total_req > 0 ? $total_req : 0;
            $total_req = 0;
            $wk_req = 0;
            $wkitems_req = 0;
        }


        $servicereq_det = $ServiceRequestMasterModel
            ->where('serm_vendor_flag', 1)
            ->join('status_master', 'sm_id=serm_status', 'left')
            ->findAll();

        if ($servicereq_det) {
            for ($i = 0; $i < sizeof($servicereq_det); $i++) {
                $servicereq_det[$i] = $vendorMasterModel
                    ->where('vm_serm_id', $servicereq_det[$i]['serm_id'])
                    ->join('customer_master', 'cstm_id=vm_cstm_id')
                    ->join('servicerequest_master', 'serm_id=vm_serm_id')
                    ->first();
            }
        }

        // return $this->respond($servicereq_det, 200);





        if (!$servicereq_det) {
            $servicereq_det = 0;
        }

        if ($cust_list) {
            $response = [
                'ret_data' => 'success',
                'vend_list' => $cust_list,
                'servicereq_det' => $servicereq_det
            ];
        } else {
            $response['Message'] = 'No data Found';
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
        } else 
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $cusmodel = new CustomerMasterModel();
        $catsalesModel = new CatsalesHistoryModel();
        $ServiceRequestMasterModel = new ServiceRequestMasterModel();
        $servicesModel = new ServiceRequestItemsModel();
        $serequestmediaModel = new ServiceRequestMediaModel();
        $vendorMasterModel = new VendorModel();
        $paymenttrackerModel = new PaymentTrackermasterModel();
        $customerdiscountModel = new CustomerDiscountModel();
        $paymenthistoryModel = new PaymentHistoryModel();

        $servicereq_det = [];
        $vendorDetails=[];
        $servicereq_det = $ServiceRequestMasterModel
            ->where('serm_id', base64_decode($id))
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('status_master', 'sm_id=serm_status', 'left')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->join('vendor_master', 'vm_serm_id=serm_id')
            ->where('vm_active_flag',0)
            ->orderBy('vm_id','DESC')
            ->first();
        $servicereq_det['customer_dicounts'] = $servicereq_det['cstm_type'] == 1 ? $customerdiscountModel->where('cd_active_flag', 0)->first() : [];
        $services = $servicereq_det ?  $servicesModel->where('sitem_serid', base64_decode($id))
            ->where('sitem_deleteflag', 0)
            ->findAll() : [];
        $medias = $servicereq_det ? $serequestmediaModel
            ->where('smedia_deleteflag', 0)
            ->where('smedia_sereqid', base64_decode($id))->findAll() : [];

        $vendor_Data = $vendorMasterModel
            ->where('vm_serm_id', base64_decode($id))
            ->orderBy('vm_updated_on', 'desc')
            ->first();

        $rpt_data = $paymenttrackerModel->where('rpt_type', 1)->where('rpt_reqt_id', base64_decode($id))->first();
        $rpt_hist = $paymenthistoryModel->where('rph_type', 0)->where('rph_rq_id', base64_decode($id))->first();
        if ($rpt_data) {
            $servicereq_det['rpt_id'] = $rpt_data['rpt_id'];
            $servicereq_det['rpt_data'] = $rpt_data;
            $servicereq_det['rpt_hist'] = $rpt_hist;
        }

        if ($servicereq_det) {
            $response['ret_data'] = "success";
            $response['servicereq_det'] = $servicereq_det;
            $response['services'] = $services;
            $response['medias'] = $medias;
            $response['vendor_Data'] = $vendor_Data;

            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            return $this->respond($response, 200);
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
        $custModel = new CustomerMasterModel();
        $userModel = new UsersModel();

        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'customer') {
            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $CustomerMasterModel = new CustomerMasterModel();
        $servicequestModel = new ServiceRequestMasterModel();
        $servicerequestitemsModels = new ServiceRequestItemsModel();
        $serequestModel = new ServiceRequestMasterModel();
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $seqModel = new SequenceGeneratorModel();
        $statusmasterModel = new StatusMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $custmodel = new CustomerMasterModel();
        $notificationmasterModel = new NotificationmasterModel();
        $notificationController = new UsersNotificationController();
        $vendorMasterModel = new VendorModel();
        $date = date("Y-m-d H:i:s");
        $rules = [
            'serm_custid' => 'required',
            'serm_vehid' => 'required',
            'serm_id' => 'required',
            'quote_items' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $customer_details = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();

        $v_cost = $this->request->getVar("totalcost") - (($customer_details['cstm_vendor_percent'] * $this->request->getVar("totalcost")) / 100);

        $seq = $seqModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_QT" . $seq[0]['quote_sequence']);

        $inData = [
            'qtm_custid' => $this->request->getVar("serm_custid"),
            'qtm_vehid' => $this->request->getVar("serm_vehid"),
            'qtm_number' => $nextval,
            'qtm_serm_id' => $this->request->getVar("serm_id"),
            'qtm_cost' => $this->request->getVar("totalcost"),
            'qtm_created_by' => $tokendata['uid'],
            'qtm_created_on' => $date,
            'qtm_updated_by' => $tokendata['uid'],
            'qtm_updated_on' => $date,
        ];

        $vend_data = [
            'vm_status' => 1,
            'vm_updated_by' => $tokendata['uid'],
            'vm_updated_on' => $date,
            'vm_total_cost' => $v_cost,
            'vm_cash_percent' => $customer_details['cstm_vendor_percent']
        ];

        $result = $vendorMasterModel->update($this->request->getVar("vm_id"), $vend_data);

        $result = $quotemodel->insert($inData);
        $upd_data = [
            'qtm_status_id' => 58,
            'qtm_updated_by' => $tokendata['uid'],
            'qtm_updated_on' => $date,
        ];
        $upd_data2 = [
            'serm_status' => 58,
            'serm_cost' => $this->request->getVar("totalcost"),
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];
        $inst_data = [
            'srh_serm_id' => $this->request->getVar("serm_id"),
            'srh_status_id' => 58,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];

        $result_insert = $servicehistoryModel->insert($inst_data);
        $result_updt = $quotemodel->update($result, $upd_data);
        $result_ipdt2 = $servicequestModel->update($this->request->getVar("serm_id"), $upd_data2);
        if ($result) {
            if (sizeof($this->request->getVar("quote_items")) > 0) {
                $serviceData = [];
                foreach ($this->request->getVar("quote_items") as $eachservice) {
                    $each_data = [
                        'qti_qm_id'   => $result,
                        'qti_type' => 0,
                        'qti_cost' => $eachservice->amount,
                        'qti_items_vendor' => $eachservice->qti_items_vendor,
                        'qti_created_by' => $tokendata['uid'],
                        'qti_created_on' => $date,
                        'qti_updated_by' => $tokendata['uid'],
                        'qti_updated_on' => $date,
                    ];
                    array_push($serviceData, $each_data);
                }
                $resultitem = $quoteitemsmodel->insertBatch($serviceData);
            }

            $serm_data = $servicequestModel->where('serm_id', $this->request->getVar('serm_id'))->first();

            $us_id = $userModel->where('us_id', $serm_data['serm_assigne'])->findAll();
            $ntf_data = [];

            foreach ($us_id as $eachurl) {

                $indata = [
                    'id' => $eachurl['us_id'],
                    'headers' => "Quotation Created!!",
                    'content' => "Service Request " . $serm_data['serm_number'] . " Quote Created By Expert!!",
                    'sourceid' => $tokendata['uid'],
                    'destid' => $eachurl['us_id'],
                    'date' => $date,
                    'nt_request_type' => 0,
                    'nt_type_id' => $serm_data['serm_id'],
                    'nt_type' => 0,
                    'nt_req_number' => $serm_data['serm_number']
                ];
                array_push($ntf_data, $indata);
            }

            $notificationController->create_us_notification($ntf_data);

            if ($result && $resultitem) {
                $seq = (intval($seq[0]['quote_sequence']) + 1);
                $seq_data = ['quote_sequence' => $seq];
                $seqModel->update(1, $seq_data);
                $response['ret_data'] = "success";

                return $this->respond($response, 200);
            } else {
                $response['ret_data'] = "fail";
                $response['Message'] = 'Cannot Update';
                return $this->respond($response, 200);
            }
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'Cannot Update';
            return $this->respond($response, 200);
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
    public function update($id = null) {}

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null) {}


    public function vend_serm_list()
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
        $serequestModel = new ServiceRequestMasterModel();
        $serviceRequestMediaModel =  new ServiceRequestMediaModel();
        $servicerequestitemsModels = new ServiceRequestItemsModel();
        $vendorMasterModel = new VendorModel();

        $result = $vendorMasterModel
            ->where('vm_delete_flag', 0)
            ->where('vm_active_flag', 0)
            ->where('vm_status!=', 2)
            ->join('servicerequest_master', 'vm_serm_id=serm_id')
            ->where('vm_cstm_id', $tokendata['uid'])
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->join('status_master', 'sm_id=serm_status')
            ->orderBy('vm_id', 'desc')
            ->findAll();

        //  return $this->respond($result, 200);


        if (sizeof($result) > 0) {
            for ($j = 0; $j < sizeof($result); $j++) {

                $result[$j]['services'] = $result[$j]['vm_status'] != 0 ? $servicerequestitemsModels->where('sitem_deleteflag', 0)
                    ->where('sitem_serid', $result[$j]['serm_id'])
                    ->orderBy('sitem_id', 'DESC')
                    ->findAll() : [];
                $result[$j]['medias'] = $result[$j]['vm_status'] != 0 ? $serviceRequestMediaModel
                    ->where('smedia_deleteflag', 0)
                    ->where('smedia_sereqid', $result[$j]['serm_id'])->findAll() : [];
            }
        }


        $services = $this->fetch_job_list($tokendata);

        $total_req = array_merge($result, $services);



        if (sizeof($total_req) > 0) {

            $response['ret_data'] = "success";
            $response['services'] = $total_req;
            $response['services_assigned_job'] = $services;


            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No Pending service request';
            return $this->respond($response, 200);
        }
    }


    public function vendorreject_request()
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
        } else if ($tokendata['aud'] == 'user') {

            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $serequestModel = new ServiceRequestMasterModel();
        $customervehicleModel = new CustomerVehicleModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $notificationmasterController = new UsersNotificationController;
        $vendorMasterModel = new VendorModel();
        $date = date("Y-m-d H:i:s");
        $rules = [
            'serm_id' => 'required',
            'reason' => 'required',
            'vm_id' => 'required',
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $vend_data = [
            'vm_status' => 2,
            'vm_reject_reason' => $this->request->getVar('reason'),
            'vm_updated_by' => $tokendata['uid'],
            'vm_updated_on' => $date,
        ];

        $result = $vendorMasterModel->update($this->request->getVar('vm_id'), $vend_data);


        $serm_data = $servicerequestMasterModel->where('serm_id', $this->request->getVar('serm_id'))->first();

        $us_id = $userModel->where('us_id', $serm_data['serm_assigne'])->findAll();
        $ntf_data = [];

        foreach ($us_id as $eachurl) {

            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "Service Rejected!!",
                'content' => "Service Request " . $serm_data['serm_number'] . " Rejected By Expert!!",
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_data['serm_id'],
                'nt_type' => 0,
                'nt_req_number' => $serm_data['serm_number']
            ];
            array_push($ntf_data, $indata);
        }

        $notificationmasterController->create_us_notification($ntf_data);

        if ($result) {
            $response['ret_data'] = 'success';
        } else {
            $response['Message'] = 'Didnt updated';
        }
        return $this->respond($response, 200);
    }


    public function vendorcompleted_requestbyid()
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
        $vendorMasterModel = new VendorModel();
        $total_req = $vendorMasterModel
            ->where('vm_delete_flag', 0)
            ->whereIn('vm_status', [2, 10])
            ->join('servicerequest_master', 'vm_serm_id=serm_id')
            ->where('vm_cstm_id', $tokendata['uid'])
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->orderBy('serm_id', 'desc')->findAll();
        if ($total_req) {
            $response = [
                'ret_data' => 'success',
                'data' => $total_req
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'Message' => 'No data for this request'
            ];
        }
        return $this->respond($response, 200);
    }


    public function v_quote()
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
        $statusmaster = new StatusMasterModel();
        $servicerequestModel = new ServiceRequestMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $quotemasterModel = new QuoteMasterModel();
        $serequestitemsModel = new ServiceRequestItemsModel();
        $paymenthistModel = new PaymentHistoryModel();
        $paymenttrackerModel = new PaymentTrackermasterModel();
        $paymentmasterController = new PaymentMasterController;

        $status_id = $this->request->getVar('status_id');
        $flag_id = $this->request->getVar('flag_id');
        $qtm_id = $this->request->getVar('qtm_id');
        $services = $this->request->getVar("qt_details");
        $sr_id = $this->request->getVar('serm_id');

        $vendorMasterModel = new VendorModel();

        $vendoritems = new VendorItemsModel();

        // return $this->respond($services, 200);

        $date = date("Y-m-d H:i:s");



        if ($status_id == 21) {
            if ($flag_id == 1) {
                $next_status = 22;
            }
            $next_status = 23;
        }
        $servq_status_master = $statusmaster->select('sm_name,sm_code')->where('sm_id', $next_status)->findAll();
        foreach ($servq_status_master as $servq_status_master) {
            $servq_req_status = $servq_status_master['sm_name'];
            $servq_req_status_code = $servq_status_master['sm_code'];
        }

        $updtdata = [
            'serm_status' => $next_status,
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];
        $insertdata = [
            'srh_status_id' => $next_status,
            'srh_serm_id' => $sr_id,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];
        $insertquote = [
            'qtm_status_id' => $next_status,
            'qtm_updated_by' => $tokendata['uid'],
            'qtm_updated_on' => $date,
        ];
        $vend_data = [
            'vm_cstm_id' => $tokendata['uid'],
            'vm_serm_id' => $this->request->getVar('serm_id'),
            'vm_status' => 4,
            'vm_created_by' => $tokendata['uid'],
            'vm_created_on' => $date,
            'vm_updated_by' => $tokendata['uid'],
            'vm_updated_on' => $date,
        ];

        $result = $vendorMasterModel->update($this->request->getVar('vm_id'), $vend_data);
        $vm_items = [];
        $serm_data = $servicerequestModel->where('serm_id', $this->request->getVar('serm_id'))->first();

        foreach ($services as $eachservice) {
            $items = [
                'vitem_vm_id' => $result,
                'vitem_assigne' => $serm_data['serm_assigne'],
                'vitem_type' => 0,
                'vitem_rq_id' => $this->request->getVar('serm_id'),
                'vitem_paid_status' => 0,
                'vitem_cost' => $eachservice->qti_cost,
                'vitem_createdon' => $date,
                'vitem_createdby' => $tokendata['uid'],
                'vitem_updatedon' => $date,
                'vitem_updatedby' => $tokendata['uid']

            ];

            array_push($vm_items, $items);
        }

        if (sizeof($vm_items) > 0) {
            $results_item = $vendoritems->insertBatch($vm_items);
        }
        $results1 = $servicerequestModel->update($sr_id, $updtdata);
        $toolhistid1 = $servicehistoryModel->insert($insertdata);
        $quoteinsert = $quotemasterModel->update($qtm_id, $insertquote);

        if ($next_status == 23) {

            $updtdata = [
                'serm_status' => 25,
                'serm_cost' => $this->request->getVar('total_amount'),
                'serm_ad_type' => $this->request->getVar('serm_ad_type'),
                'serm_ad_charge' => $this->request->getVar('serm_ad_charge'),
                'serm_ad_charge_cost' => $this->request->getVar('serm_ad_charge_cost'),
                'serm_custpay_amount' => $this->request->getVar('paid_amount'),
                'serm_updatedon' => $date,
                'serm_updatedby' => $tokendata['uid'],
                'serm_discount_amount' => $this->request->getVar('amount_after_discount')
            ];
            $insertdata = [
                'srh_status_id' => 25,
                'srh_serm_id' => $sr_id,
                'srh_created_on' => $date,
                'srh_created_by' => $tokendata['uid']
            ];
            $insertquote = [
                'qtm_status_id' => $next_status,
                'qtm_updated_by' => $tokendata['uid'],
                'qtm_updated_on' => $date,
            ];

            $paid_amount = $this->request->getVar('paid_amount');
            $t_details = $this->request->getVar('transaction_details');
            if ($paid_amount > 0) {
                $hist1 = [
                    'rph_type' => 0,
                    'rph_rq_id' => $sr_id,
                    'rph_status' => 0,
                    'rph_amount' => $paid_amount,
                    'rph_created_on' => $date,
                    'rph_created_by' => $tokendata['uid'],
                ];
                $paymenthistModel->insert($hist1);

                $balance_amount = ($this->request->getVar('amount_after_discount') > 0) ? $this->request->getVar('amount_after_discount') - $paid_amount :
                    $this->request->getVar('total_amount') - $paid_amount;

                if ($balance_amount == 0) {
                    $track_data = [
                        'rpt_type' => 1,
                        'rpt_reqt_id' => $sr_id,
                        'rpt_amount' => $balance_amount,
                        'rpt_cust_id' => $tokendata['uid'],
                        'rpt_status' => 1,
                        'rpt_created_on' => $date,
                        'rpt_created_by' => $tokendata['uid'],
                        'rpt_updated_on' => $date,
                        'rpt_updated_by' => $tokendata['uid'],
                        'rpt_transaction_id' => $this->request->getVar('txnid'),

                    ];
                    $histdata = [
                        'srh_status_id' => 31,
                        'srh_serm_id' => $sr_id,
                        'srh_created_on' => $date,
                        'srh_created_by' => $tokendata['uid']
                    ];
                    $servicehistoryModel->insert($histdata);
                    if (count($services) > 0) {
                        $in_data = array();
                        for ($i = 0; $i < count($services); $i++) {
                            $infdata = [
                                'sitem_vendor'   => $services[$i]->qti_items_vendor,
                                'sitem_serid'   => $sr_id,
                                'sitem_type' => 0,
                                'sitem_cost' => $services[$i]->qti_cost,
                                'sitem_createdon' => $date,
                                'sitem_createdby' =>  $tokendata['uid'],
                                'sitem_updatedby' =>  $tokendata['uid'],
                                'sitem_updatedon' => $date,
                                'sitem_paid_status' => 2,
                            ];
                            array_push($in_data, $infdata);
                        }
                        $ret = $serequestitemsModel->insertBatch($in_data);
                    }
                } else {
                    if (count($services) > 0) {
                        $in_data = array();
                        for ($i = 0; $i < count($services); $i++) {
                            $infdata = [
                                'sitem_vendor'   => $services[$i]->qti_items_vendor,
                                'sitem_serid'   => $sr_id,
                                'sitem_type' => 0,
                                'sitem_cost' => $services[$i]->qti_cost,
                                'sitem_createdon' => $date,
                                'sitem_createdby' =>  $tokendata['uid'],
                                'sitem_updatedby' =>  $tokendata['uid'],
                                'sitem_updatedon' => $date,
                                'sitem_paid_status' => 1,
                            ];
                            array_push($in_data, $infdata);
                        }
                        $ret = $serequestitemsModel->insertBatch($in_data);
                    }
                    $track_data = [
                        'rpt_type' => 1,
                        'rpt_reqt_id' => $sr_id,
                        'rpt_amount' => $balance_amount,
                        'rpt_cust_id' => $tokendata['uid'],
                        'rpt_status' => 0,
                        'rpt_created_on' => $date,
                        'rpt_created_by' => $tokendata['uid'],
                        'rpt_updated_on' => $date,
                        'rpt_updated_by' => $tokendata['uid'],
                        'rpt_transaction_id' => $this->request->getVar('txnid'),
                    ];


                    $track_data = [
                        'rpt_type' => 1,
                        'rpt_reqt_id' => $sr_id,

                        'rpt_amount' => $balance_amount,
                        'rpt_cust_id' => $tokendata['uid'],
                        'rpt_status' => 0,
                        'rpt_created_on' => $date,
                        'rpt_created_by' => $tokendata['uid'],
                        'rpt_updated_on' => $date,
                        'rpt_updated_by' => $tokendata['uid'],
                    ];
                }
                $paymenttrackerModel->insert($track_data);
                $results1 = $servicerequestModel->update($sr_id, $updtdata);
                $toolhistid1 = $servicehistoryModel->insert($insertdata);
                $quoteinsert = $quotemasterModel->update($qtm_id, $insertquote);
                if ($t_details->result == 'payment_successfull') {
                    $hist2 = [
                        'rph_type' => 0,
                        'rph_rq_id' => $sr_id,
                        'rph_status' => 1,
                        'rph_amount' => $paid_amount,
                        'rph_created_on' => $date,
                        'rph_created_by' => $tokendata['uid'],
                        'rph_transaction_id' => $this->request->getVar('txnid')
                    ];
                    $paymenthistModel->insert($hist2);

                    $data = $paymentmasterController->serv_balance_amount($t_details->payment_response);
                } else {
                    $hist2 = [
                        'rph_type' => 0,
                        'rph_rq_id' => $sr_id,
                        'rph_status' => 1,
                        'rph_amount' => $paid_amount,
                        'rph_created_on' => $date,
                        'rph_created_by' => $tokendata['uid'],
                        'rph_transaction_id' => $this->request->getVar('txnid')
                    ];
                    $paymenthistModel->insert($hist2);
                    $data = $paymentmasterController->failed_transaction($t_details->payment_response);
                }
            } else {
                if (count($services) > 0) {
                    $in_data = array();
                    for ($i = 0; $i < count($services); $i++) {
                        $infdata = [
                            'sitem_vendor'   => $services[$i]->qti_items_vendor,
                            'sitem_serid'   => $sr_id,
                            'sitem_type' => 0,
                            'sitem_cost' => $services[$i]->qti_cost,
                            'sitem_createdon' => $date,
                            'sitem_createdby' =>  $tokendata['uid'],
                            'sitem_updatedby' =>  $tokendata['uid'],
                            'sitem_updatedon' => $date,
                            'sitem_paid_status' => 0,
                        ];
                        array_push($in_data, $infdata);
                    }
                    $ret = $serequestitemsModel->insertBatch($in_data);
                }
                $hist5 = [
                    'rph_type' => 0,
                    'rph_rq_id' => $sr_id,
                    'rph_status' => 0,
                    'rph_amount' => $this->request->getVar('amount_after_discount'),
                    'rph_created_on' => $date,
                    'rph_created_by' => $tokendata['uid'],
                ];
                $paymenthistModel->insert($hist5);

                $track_data = [
                    'rpt_type' => 1,
                    'rpt_reqt_id' => $sr_id,
                    'rpt_amount' => $this->request->getVar('amount_after_discount'),
                    'rpt_cust_id' => $tokendata['uid'],
                    'rpt_status' => 0,
                    'rpt_created_on' => $date,
                    'rpt_created_by' => $tokendata['uid'],
                    'rpt_updated_on' => $date,
                    'rpt_updated_by' => $tokendata['uid'],
                ];
            }

            $paymenttrackerModel->insert($track_data);
            $results1 = $servicerequestModel->update($sr_id, $updtdata);
            $toolhistid1 = $servicehistoryModel->insert($insertdata);
            $quoteinsert = $quotemasterModel->update($qtm_id, $insertquote);

            $response = [
                'ret_data' => 'success',
                'sr_id' => $sr_id,
                'status' => $servq_req_status,
                'status_code' => $servq_req_status_code
            ];

            return $this->respond($response, 200);
        } else if ($next_status == 22) {


            $insertquote = [
                'qtm_rejected_reason' => $this->request->getVar('rejected_reason'),
                'qtm_status_id' => 22,
                'qtm_updated_by' => $tokendata['uid'],
                'qtm_updated_on' => $date,
            ];
            $quoteinsert = $quotemasterModel->update($this->request->getVar('qtm_id'), $insertquote);

            $upd_data2 = [
                'serm_status' => 22,
                'serm_updatedby' => $tokendata['uid'],
                'serm_updatedon' => $date,
                'serm_updatedby' => $tokendata['uid']

            ];
            $inst_data = [
                'srh_serm_id' => $this->request->getVar("serm_id"),
                'srh_status_id' => 22,
                'srh_updated_by' => $tokendata['uid'],
                'srh_created_on' => $date,
                'srh_created_by' => $tokendata['uid']
            ];

            $result_ipdt2 = $servicerequestModel->update($this->request->getVar("serm_id"), $upd_data2);
            $servicehistoryModel->insert($inst_data);

            $response = [
                'ret_data' => 'success',
                'sr_id' => $sr_id,
                'status' => $servq_req_status,
                'status_code' => $servq_req_status_code
            ];

            return $this->respond($response, 200);
        }
    }



    public function vquote_details()
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



        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $customerdiscountModel = new CustomerDiscountModel();

        $result = [];
        $result = $quotemodel->where('qtm_delete_flag', 0)->where('qtm_serm_id',  $this->request->getVar("request_id"))
            ->where('qtm_type', 0)
            ->join('customer_master', 'cstm_id=qtm_custid')
            ->join('servicerequest_master', 'serm_id=qtm_serm_id')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=customer_vehicle.custveh_veh_id')
            ->join('vendor_master', 'vm_serm_id=serm_id', 'left')
            ->orderBy('qtm_created_on', 'desc')
            ->first();

        $result['customer_dicounts'] = $result['cstm_type'] == 1 ? $customerdiscountModel->where('cd_active_flag', 0)->first() : [];

        $services = $quoteitemsmodel
            ->where('qti_deleted_flag', 0)
            ->where('qti_qm_id', $result['qtm_id'])
            ->findAll();


        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            $response['services'] = $services;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No details for this service request';
            return $this->respond($response, 200);
        }
    }


    //api for fetching all vendor request with details.
    public function fetch_vendor_details()

    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'customer') {
            $custModel = new CustomerMasterModel();
            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else 
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $cusmodel = new CustomerMasterModel();
        $catsalesModel = new CatsalesHistoryModel();
        $ServiceRequestMasterModel = new ServiceRequestMasterModel();
        $servicesModel = new ServiceRequestItemsModel();
        $serequestmediaModel = new ServiceRequestMediaModel();
        $vendoritemsModel = new VendorItemsModel();
        $servicerequestitemsModel = new ServiceRequestItemsModel();



        $request_data = [];
        $cust_info = $cusmodel->where('cstm_id', $this->request->getVar("cstm_id"))
            ->join('customer_bank_details', 'cb_cstm_id=cstm_id', 'left')
            ->join('customer_roles', 'cstr_id=cstm_cstr_id')->first();
        $servicereq_det = $cust_info ? $ServiceRequestMasterModel
            ->where('serm_vendor_flag', 1)
            ->where('serm_assigne', $cust_info['cstm_id'])
            ->join('status_master', 'sm_id=serm_status', 'left')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->join('customer_bank_details', 'cb_cstm_id=serm_assigne', 'left')
            ->join('vendor_master', 'vm_serm_id=serm_id')
            ->findAll() : [];


        for ($i = 0; $i < sizeof($servicereq_det); $i++) {
            $servicereq_det[$i]['services'] = $servicereq_det ?  $servicesModel->where('sitem_serid', $servicereq_det[$i]['serm_id'])
                ->findAll() : [];
            $servicereq_det[$i]['medias'] = $servicereq_det ? $serequestmediaModel
                ->where('smedia_deleteflag', 0)
                ->where('smedia_sereqid', $servicereq_det[$i]['serm_id'])->findAll() : [];
            $servicereq_det[$i]['payment_details'] = $servicereq_det ? $vendoritemsModel
                ->where('vitem_type', 0)
                ->where('vitem_rq_id', $servicereq_det[$i]['serm_id'])->findAll() : [];
        }
        foreach ($servicereq_det as $eachrequest) array_push($request_data, $eachrequest);
        $servicereq_items = $cust_info ? $servicerequestitemsModel
            ->where('sitem_assignee_type', 1)
            ->where('sitem_assignee', $cust_info['cstm_id'])
            ->join('servicerequest_master', 'serm_id=sitem_serid', 'left')
            ->join('status_master', 'sm_id=serm_status', 'left')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->join('vendor_master', 'vm_serm_id=serm_id', 'left')
            ->findAll() : [];
        for ($i = 0; $i < sizeof($servicereq_items); $i++) {
            $servicereq_items[$i]['services'] = $servicereq_items ?  $servicesModel->where('sitem_serid', $servicereq_items[$i]['serm_id'])
                ->where('sitem_deleteflag', 0)
                ->findAll() : [];

            $servicereq_items[$i]['medias'] = $servicereq_items ? $serequestmediaModel
                ->where('smedia_deleteflag', 0)
                ->where('smedia_sereqid', $servicereq_items[$i]['serm_id'])->findAll() : [];
            $servicereq_items[$i]['payment_details'] = $servicereq_items ? $vendoritemsModel
                ->where('vitem_type', 0)
                ->where('vitem_rq_id', $servicereq_det[$i]['serm_id'])->findAll() : [];
        }
        foreach ($servicereq_items as $eachrequest) array_push($request_data, $eachrequest);

        if ($cust_info) {
            $response["cust_info"] = $cust_info;
            $response['ret_data'] = "success";
            $response['request_data'] = $request_data;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            return $this->respond($response, 200);
        }
    }



    public function get_item_det()

    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'customer') {
            $custModel = new CustomerMasterModel();
            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else 
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $cusmodel = new CustomerMasterModel();
        $catsalesModel = new CatsalesHistoryModel();
        $ServiceRequestMasterModel = new ServiceRequestMasterModel();
        $servicerequestitemsModels = new ServiceRequestItemsModel();
        $servicesModel = new ServiceRequestItemsModel();
        $serequestmediaModel = new ServiceRequestMediaModel();
        $vendorMasterModel = new VendorModel();

        $sitem_id = $this->request->getVar("sitem_id");
        //  return $this->respond($sitem_id, 200);


        $services_data = $servicerequestitemsModels->where('sitem_deleteflag', 0)
            ->where('sitem_id', $this->request->getVar("sitem_id"),)
            ->join('servicerequest_master', 'serm_id=sitem_serid')
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->join('status_master', 'sm_id=serm_status')
            ->first();



        $services = $services_data ?  $servicesModel->where('sitem_id', $this->request->getVar("sitem_id"))
            ->where('sitem_deleteflag', 0)
            ->join('service_request_package', 'servpack_id=sitem_itemid', 'left')
            ->findAll() : [];


        $medias = $services_data ? $serequestmediaModel
            ->where('smedia_deleteflag', 0)
            ->where('smedia_sereqid', $services_data['serm_id'])

            ->findAll() : [];

        // return $this->respond($services_data, 200);

        $vendor_Data = $vendorMasterModel
            ->where('vm_serm_id', $services_data['serm_id'])
            ->orderBy('vm_updated_on', 'desc')
            ->first();



        if ($services) {
            $response['ret_data'] = "success";
            $response['servicereq_det'] = $services_data;
            $response['services'] = $services;
            $response['medias'] = $medias;
            $response['vendor_Data'] = $vendor_Data;

            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            return $this->respond($response, 200);
        }
    }

    public function item_confirm_expert()

    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'customer') {
            $custModel = new CustomerMasterModel();
            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else 
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $cusmodel = new CustomerMasterModel();
        $catsalesModel = new CatsalesHistoryModel();
        $ServiceRequestMasterModel = new ServiceRequestMasterModel();
        $servicerequestitemsModels = new ServiceRequestItemsModel();
        $servicesModel = new ServiceRequestItemsModel();
        $serequestmediaModel = new ServiceRequestMediaModel();
        $vendorMasterModel = new VendorModel();

        $rules = [
            'flag' => 'required',
            'sitem_id' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $data = (($this->request->getVar("flag")) == 1) ?
            [
                'sitem_assignee' => 0,
                'sitem_status_flag' => 7,
                'sitem_updatedby' =>  $tokendata['uid'],
                'sitem_updatedon' => $date,
                'sitem_active_flag' => 1,
                'sitem_status_flag' => 0,
                'sitem_assignee_type' => 0,
                'sitem_assignee' => 0
            ]
            :
            [
                'sitem_status_flag' => 0,
                'sitem_updatedby' =>  $tokendata['uid'],
                'sitem_updatedon' => $date,
            ];

        $servicerequestitemsModels->update(($this->request->getVar("sitem_id")), $data);

        $response = [
            'ret_data' => 'success'
        ];

        return $this->respond($response, 200);
    }

    public function job_complete()

    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $userModel = new UsersModel();
        $custModel = new CustomerMasterModel();
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
        $rules = [
            'services' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $services = $this->request->getVar("services");


        $servicerequestitemsModels = new ServiceRequestItemsModel();
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $notificationmasterController = new UsersNotificationController;



        $inf_data = [
            'sitem_status_flag' => 6,
            'sitem_id' => $services->sitem_id,
            'sitem_updatedby' =>  $tokendata['uid'],
            'sitem_updatedon' => $date,
        ];



        $servicerequestitemsModels->update($services->sitem_id, $inf_data);

        $serm_id = $servicerequestitemsModels->select('sitem_serid')->where('sitem_id', $services->sitem_id)->first();

        $serm_data = $servicerequestMasterModel->where('serm_id', $serm_id)->first();

        $us_id = $userModel->where('us_id', $serm_data['serm_assigne'])->findAll();
        $ntf_data = [];

        foreach ($us_id as $eachurl) {

            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "Expert Service Completed",
                'content' => "Service Request " . $serm_data['serm_number'] . " Completed By Expert!!",
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_data['serm_id'],
                'nt_type' => 0,
                'nt_req_number' => $serm_data['serm_number']
            ];
            array_push($ntf_data, $indata);
        }

        $notificationmasterController->create_us_notification($ntf_data);



        $response = $services->sitem_id ?
            ['ret_data' => 'success']
            :
            ['ret_data' => 'fail'];
        return $this->respond($response, 200);
    }


    public function v_quote_approval()
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
        $CustomerMasterModel = new CustomerMasterModel();
        $servicequestModel = new ServiceRequestMasterModel();
        $servicerequestitemsModels = new ServiceRequestItemsModel();
        $serequestModel = new ServiceRequestMasterModel();
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $seqModel = new SequenceGeneratorModel();
        $statusmasterModel = new StatusMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $custmodel = new CustomerMasterModel();
        $notificationmasterModel = new NotificationmasterModel();
        $notificationController = new UsersNotificationController();
        $vendorMasterModel = new VendorModel();
        $date = date("Y-m-d H:i:s");
        $rules = [
            'serm_custid' => 'required',
            'serm_vehid' => 'required',
            'serm_id' => 'required',
            'quote_items' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());



        $seq = $seqModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_QT" . $seq[0]['quote_sequence']);
        $inData = [
            'qtm_custid' => $this->request->getVar("serm_custid"),
            'qtm_vehid' => $this->request->getVar("serm_vehid"),
            'qtm_number' => $nextval,
            'qtm_serm_id' => $this->request->getVar("serm_id"),
            'qtm_cost' => $this->request->getVar("totalcost"),
            'qtm_created_by' => $tokendata['uid'],
            'qtm_created_on' => $date,
            'qtm_updated_by' => $tokendata['uid'],
            'qtm_updated_on' => $date,
        ];


        $vend_data = [
            'vm_status' => 1,
            'vm_updated_by' => $tokendata['uid'],
            'vm_updated_on' => $date,
            'vm_total_cost' => $this->request->getVar("v_cost"),
            'vm_cash_percent' => $this->request->getVar("cstm_vendor_percent")
        ];

        $result = $vendorMasterModel->update($this->request->getVar("vm_id"), $vend_data);

        $result = $quotemodel->insert($inData);
        $upd_data = [
            'qtm_status_id' => 21,
            'qtm_updated_by' => $tokendata['uid'],
            'qtm_updated_on' => $date,
        ];
        $upd_data2 = [
            'serm_status' => 21,
            'serm_cost' => $this->request->getVar("totalcost"),
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];
        $inst_data = [
            'srh_serm_id' => $this->request->getVar("serm_id"),
            'srh_status_id' => 21,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];

        $result_insert = $servicehistoryModel->insert($inst_data);
        $result_updt = $quotemodel->update($result, $upd_data);
        $result_ipdt2 = $servicequestModel->update($this->request->getVar("serm_id"), $upd_data2);
        if ($result) {
            if (sizeof($this->request->getVar("quote_items")) > 0) {
                $serviceData = [];
                foreach ($this->request->getVar("quote_items") as $eachservice) {
                    $each_data = [
                        'qti_qm_id'   => $result,
                        'qti_type' => 0,
                        'qti_cost' => $eachservice->qti_cost,
                        'qti_items_vendor' => $eachservice->qti_items_vendor,
                        'qti_created_by' => $tokendata['uid'],
                        'qti_created_on' => $date,
                        'qti_updated_by' => $tokendata['uid'],
                        'qti_updated_on' => $date,
                    ];
                    array_push($serviceData, $each_data);
                }
                $resultitem = $quoteitemsmodel->insertBatch($serviceData);
            }


            $target_cust = $custmodel->where('cstm_id', $this->request->getVar("serm_custid"))->first();
            $player_id = [];
            $custhead = "New Quotation created";
            $custcontent = "New Quote created against " . $nextval . ". Tap to see";

            array_push($player_id, $target_cust['fcm_token_mobile']);
            if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

            if ($ret_res) {
                $notif_data = [
                    'nt_sourceid' => $tokendata['uid'],
                    'nt_destid' => $this->request->getVar("serm_custid"),
                    'nt_destid' => $nextval,

                    'nt_sourcetype' => 1,
                    'nt_header' => $custhead,
                    'nt_content' => $custcontent,
                    'nt_created_on' => $date
                ];
                $notificationmasterModel->insert($notif_data);
            }
            if ($result && $resultitem) {
                $seq = (intval($seq[0]['quote_sequence']) + 1);
                $seq_data = ['quote_sequence' => $seq];
                $seqModel->update(1, $seq_data);
                $response['ret_data'] = "success";

                return $this->respond($response, 200);
            } else {
                $response['ret_data'] = "fail";
                $response['Message'] = 'Cannot Update';
                return $this->respond($response, 200);
            }
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'Cannot Update';
            return $this->respond($response, 200);
        }
    }

    public function vendor_payment()
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
        $CustomerMasterModel = new CustomerMasterModel();
        $servicequestModel = new ServiceRequestMasterModel();
        $servicerequestitemsModels = new ServiceRequestItemsModel();
        $serequestModel = new ServiceRequestMasterModel();
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $seqModel = new SequenceGeneratorModel();
        $statusmasterModel = new StatusMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $custmodel = new CustomerMasterModel();
        $notificationmasterModel = new NotificationmasterModel();
        $notificationController = new UsersNotificationController();
        $vendorMasterModel = new VendorModel();
        $date = date("Y-m-d H:i:s");


        $r_data =  $this->request->getVar("data");
        $b_amount = $r_data->vm_total_cost - $r_data->paid_amount;

        $u_data = $b_amount == 0 ?
            [
                'vm_payment_status' => 2,
                'vm_txn_id' => $r_data->txn_id,
                'vm_paid_amount' => $r_data->paid_amount

            ] :
            [
                'vm_payment_status' => 1,
                'vm_txn_id' => $r_data['txn_id'],
                'vm_paid_amount' => $r_data->paid_amount

            ];

        $vendorMasterModel->update($r_data->vm_id, $u_data);

        $response = ['ret_data' => 'success'];
        return $this->respond($response, 200);
    }

    public function fetch_job_list($tokendata)
    {

        $serequestModel = new ServiceRequestMasterModel();
        $serviceRequestMediaModel =  new ServiceRequestMediaModel();
        $servicerequestitemsModels = new ServiceRequestItemsModel();

        $services = $servicerequestitemsModels->where('sitem_deleteflag', 0)
            ->where('sitem_assignee_type', 1)
            ->where('sitem_assignee', $tokendata['uid'])
            ->join('servicerequest_master', 'serm_id=sitem_serid')
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->join('status_master', 'sm_id=serm_status')
            ->orderBy('sitem_id', 'DESC')
            ->findAll();

        if (sizeof($services) > 0) {

            for ($i = 0; $i < sizeof($services); $i++) {
                $services[$i]['medias'] = $serviceRequestMediaModel
                    ->where('smedia_deleteflag', 0)
                    ->where('smedia_sereqid', $services[$i]['serm_id'])->findAll();
            }
        }

        return $services;
    }

    public function vendor_status_update()
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
        $custmodel = new CustomerMasterModel();
        $UserModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $workcardModel = new ServiceRequestMasterModel();
        $workcardItemsModel = new ServiceRequestItemsModel();
        $notificationmasterModel = new NotificationmasterModel();
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $services = $this->request->getVar("services");
        $notificationMasterController= new UsersNotificationController;
        $heddata = $this->request->headers();
        $vendorMasterModel = new VendorModel();
        $date = date("Y-m-d H:i:s");

        if ($this->request->getVar("type") == '0') {

            $inData = [
                'sitem_id' => $this->request->getVar("sitem_id"),
                'sitem_status_flag'   => $this->request->getVar("status"),
                'sitem_updatedby' =>  $tokendata['uid'],
                'sitem_updatedon' => $date
            ];
            $result = $workcardItemsModel->update($this->request->getVar("sitem_id"), $inData);
            $serm_det = $workcardModel->select('serm_custid,serm_number,serm_id')->where('serm_id', $this->request->getVar("serm_id"))->first();
            $ntf_data = [
                'id' => $serm_det['serm_custid'],
                'headers' => "Expert-Work Card Updated!!!",
                'content' => "Work card has been updated for - " . $serm_det['serm_number'] . ". Tap to see" ,
                'sourceid' => $tokendata['uid'],
                'destid' => $serm_det['serm_custid'],
                'nt_req_number' => $serm_det['serm_number'],
                'nt_type' => 0,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_det['serm_id'],
                'date' => $date
            ];
             $notificationMasterController->create_cust_notification($ntf_data);
            $us_id = $UserModel
            ->where('us_delete_flag', 0)
            ->findAll();
            $ntfus_data=[];

            foreach ($us_id as $eachurl) {
              $usntdata=  [
                    'id' => $eachurl['us_id'],
                    'headers' => "Expert-Work Card Updated!!!",
                    'content' => "Work card has been updated for - " . $serm_det['serm_number'],
                    'sourceid' => $tokendata['uid'],
                    'destid' => $eachurl['us_id'],
                    'date' => $date,
                    'nt_request_type' => 0,
                    'nt_type_id' => $serm_det['serm_id'],
                    'nt_type' => 0,
                    'nt_req_number' => $serm_det['serm_number']
                ];
                array_push($ntfus_data, $usntdata);
            }
         $notificationMasterController->create_us_notification($ntfus_data);

        } 
        else if ($this->request->getVar("type") == '1') {


            if ($this->request->getVar("status") == '29') {
                // return $this->fail('type=29', 400);
                $data = $paymentTrackerModel->where('rpt_id', $this->request->getVar("rpt_id"))->first();
                if ($this->request->getVar("c_status") == 56) {
                    if ($data['rpt_status'] == 0) {
                        $inData = [
                            'serm_id' => $this->request->getVar("serm_id"),
                            'serm_status'   => 30,
                            'serm_wkc_date' => date("Y-m-d H:i:s"),
                            'serm_reopen_flag' => 1,
                            'serm_updatedon' => $date,
                            'serm_updatedby' => $tokendata['uid']
                        ];
                        $inser_hist = [
                            'srh_status_id' => 29,
                            'srh_serm_id' => $this->request->getVar("serm_id"),
                            'srh_created_on' => $date,
                            'srh_created_by' => $tokendata['uid']
                        ];

                        $servicehistoryModel->insert($inser_hist);
                        $result = $workcardModel->update($this->request->getVar("serm_id"), $inData);
                        $inser_hist = [
                            'srh_status_id' => 30,
                            'srh_serm_id' => $this->request->getVar("serm_id"),
                            'srh_created_on' => $date,
                            'srh_created_by' => $tokendata['uid']
                        ];
                        $servicehistoryModel->insert($inser_hist);

                        $serm_det = $workcardModel->select('serm_custid,serm_number')->where('serm_id', $this->request->getVar("serm_id"))->first();
                        $target_cust = $custmodel->where('cstm_id', $serm_det['serm_custid'])->first();
                        $player_id = [];
                        $custhead = "Work Completed";
                        $custcontent = "Work Completed against " . $serm_det['serm_number'] . ". Payment Pending";

                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);
                    } else {
                        $inData = [
                            'serm_id' => $this->request->getVar("serm_id"),
                            'serm_status'   => 32,
                            'serm_wkc_date' => $date,
                            'serm_reopen_flag' => 1,
                            'serm_updatedon' => $date,
                            'serm_updatedby' => $tokendata['uid']
                        ];
                        $inser_hist = [
                            'srh_status_id' => 29,
                            'srh_serm_id' => $this->request->getVar("serm_id"),
                            'srh_created_on' => $date,
                            'srh_created_by' => $tokendata['uid']
                        ];

                        $servicehistoryModel->insert($inser_hist);
                        $result = $workcardModel->update($this->request->getVar("serm_id"), $inData);
                        $inser_hist = [
                            'srh_status_id' => 32,
                            'srh_serm_id' => $this->request->getVar("serm_id"),
                            'srh_created_on' => $date,
                            'srh_created_by' => $tokendata['uid']
                        ];
                        $servicehistoryModel->insert($inser_hist);

                        $serm_det = $workcardModel->select('serm_custid,serm_number')->where('serm_id', $this->request->getVar("serm_id"))->first();
                        $target_cust = $custmodel->where('cstm_id', $serm_det['serm_custid'])->first();
                        $player_id = [];
                        $custhead = "Request Completed";
                        $custcontent = "Request Completed against " . $serm_det['serm_number'] . ".";

                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);
                    }
                } else {
                    $serm_det = $workcardModel->select('serm_custid,serm_number')->where('serm_id', $this->request->getVar("serm_id"))->first();
                    $target_cust = $custmodel->where('cstm_id', $serm_det['serm_custid'])->first();

                    $vm_data = [
                        'vm_id' => $this->request->getvar('vm_id'),
                        'vm_status' => 10,
                        'vm_updated_by' => $tokendata['uid'],
                        'vm_updated_on' => $date
                    ];
                    $vendorMasterModel->update($this->request->getvar('vm_id'), $vm_data);
                    $player_id = [];
                    if ($data['rpt_status'] == 0) {
                        $master = [
                            'serm_id' => $this->request->getVar("serm_id"),
                            'serm_status'   => 30,
                            'serm_wkc_date' => $date,
                            'serm_reopen_flag' => 0,
                            'serm_updatedon' => $date,
                            'serm_updatedby' => $tokendata['uid']
                        ];
                        $inser_hist2 = [
                            'srh_status_id' => 29,
                            'srh_serm_id' => $this->request->getVar("serm_id"),
                            'srh_created_on' => $date,
                            'srh_created_by' => $tokendata['uid']
                        ];
                        $servicehistoryModel->insert($inser_hist2);
                        $inser_hist = [
                            'srh_status_id' => 30,
                            'srh_serm_id' => $this->request->getVar("serm_id"),
                            'srh_created_on' => $date,
                            'srh_created_by' => $tokendata['uid']
                        ];
                        $servicehistoryModel->insert($inser_hist);
                        $custhead = "Work Completed";
                        $custcontent = "Work Completed against " . $serm_det['serm_number'] . ". Payment Pending";
                    } else {
                        $master = [
                            'serm_id' => $this->request->getVar("serm_id"),
                            'serm_status'   => 32,
                            'serm_wkc_date' => $date,
                            'serm_reopen_flag' => 0,
                            'serm_updatedon' => $date,
                            'serm_updatedby' => $tokendata['uid']
                        ];
                        $inser_hist = [
                            'srh_status_id' => 29,
                            'srh_serm_id' => $this->request->getVar("serm_id"),
                            'srh_created_on' => $date,
                            'srh_created_by' => $tokendata['uid']
                        ];
                        $servicehistoryModel->insert($inser_hist);
                        $inser_hist1 = [
                            'srh_status_id' => 32,
                            'srh_serm_id' => $this->request->getVar("serm_id"),
                            'srh_created_on' => $date,
                            'srh_created_by' => $tokendata['uid']
                        ];
                        $servicehistoryModel->insert($inser_hist1);

                        $custhead = "Request Completed";
                        $custcontent = "Work Completed against " . $serm_det['serm_number'] . ". Thankyou";
                    }

                    $result = $workcardModel->update($this->request->getVar("serm_id"), $master);
                    array_push($player_id, $target_cust['fcm_token_mobile']);
                    if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);
                }
            } else {
                // $this->start_work($this->request->getVar("serm_id"), $tokendata);

                $result = $commonutils->start_work($this->request->getVar("serm_id"), $tokendata);
            }
        }




        if ($result) {
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->fail($data, 400);
        }
    }

    public function start_work($serm_id, $tokendata)
    {

        $workcardItemsModel = new ServiceRequestItemsModel();
        $custmodel = new CustomerMasterModel();
        $commonutils = new Commonutils();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $workcardModel = new ServiceRequestMasterModel();
        $date = date("Y-m-d H:i:s");
        $inData = [
            'serm_id' => $serm_id,
            'serm_status'   => 28,
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']

        ];
        $inser_hist = [
            'srh_status_id' => 28,
            'srh_serm_id' => $serm_id,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];
        $items = $workcardItemsModel->where('sitem_serid', $serm_id)->findAll();
        for ($i = 0; $i < sizeof($items); $i++) {
            $itemsupd[$i] = [
                'sitem_createdby' => $tokendata['uid'],
                'sitem_updatedby' => $tokendata['uid'],
                'sitem_active_flag' => 1,
                'sitem_updatedby' =>  $tokendata['uid'],
                'sitem_updatedon' => $date
            ];
            $workcardItemsModel->update($items[$i]['sitem_id'], $itemsupd[$i]);
        }

        $servicehistoryModel->insert($inser_hist);
        $result = $workcardModel->update($serm_id, $inData);

        $serm_det = $workcardModel->select('serm_custid,serm_number')->where('serm_id', $serm_id)->first();
        $target_cust = $custmodel->where('cstm_id', $serm_det['serm_custid'])->first();
        $player_id = [];
        $custhead = "Work Started";
        $custcontent = "Work Started against " . $serm_det['serm_number'] . ". Tap to see";

        array_push($player_id, $target_cust['fcm_token_mobile']);
        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

        return $result;
    }
}
