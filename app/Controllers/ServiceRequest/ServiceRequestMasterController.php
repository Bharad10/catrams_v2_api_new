<?php

namespace App\Controllers\ServiceRequest;

use App\Controllers\Chat\ChatMasterController;
use App\Controllers\Payment\PaymentMasterController;
use App\Controllers\System\UsersNotificationController;
use App\Controllers\ToolRequest\ToolRequestMasterController;
use App\Models\Approval\ApprovalmasterModel;
use App\Models\Chat\ServicesChatModel;
use App\Models\Customer\CustomerDataCardModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerVehicleModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Media\RequestMediaModel;
use App\Models\Packages\ServiceRequestPackageModel; 
use App\Models\Payment\PaymentHistoryModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\Quotation\QuoteItemsModel;
use App\Models\Quotation\QuoteMasterModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\User\UsersModel;
use App\Models\Sequence\SequenceGeneratorModel;
use App\Models\ServiceRequest\ServiceRequestHistoryModel;
use App\Models\ServiceRequest\ServiceRequestItemsModel;
use App\Models\ServiceRequest\ServiceRequestMediaModel;
use App\Models\StatusMaster\StatusMasterModel;
use App\Models\System\CustomerDiscountModel;
use App\Models\System\NotificationmasterModel;
use App\Models\System\ServicesMappingModel;
use App\Models\System\WorkCardSettingsModel;
use App\Models\ToolRequest\ToolDetailsModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\VehicleMaster\CatVehicleDataModel;
use App\Models\Vendor\VendorModel;
use Config\Commonutils;
use Config\Validation;
use CodeIgniter\HTTP\Request;
use CodeIgniter\I18n\Time;
use DateInterval;
use DateTime;

class ServiceRequestMasterController extends ResourceController
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
    $token = $validModel->getbearertoken($heddata['Authorization']);
    $tokendata = $commonutils->decode_jwt_token($token);

    if ($tokendata['aud'] == 'customer') {
        $custModel = new CustomerMasterModel();
        $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
        if (!$customer) return $this->fail("invalid user", 400);
    } elseif ($tokendata['aud'] == 'user') {
        $userModel = new UsersModel();
        $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
        if (!$users) return $this->fail("invalid user", 400);
    } else {
        return $this->fail("invalid user", 400);
    }

    $serequestModel = new ServiceRequestMasterModel();
    $quotemasterModel = new QuoteMasterModel();
    $vendorMasterModel = new VendorModel();

    // Optimize the main query to fetch all needed data
    $result = $serequestModel->select('serm_id, cstm_name, cstm_vendor_flag, cstm_id, custveh_regnumber, custveh_vinnumber, custveh_datacard_url, custveh_veh_id, make_name, model_name, variant_name, sm_name, sm_code, sm_pk_id, serm_status, serm_number, serm_id, serm_updatedon, serm_hold_flag, serm_cost, serm_vendor_flag')
        ->join('customer_master', 'cstm_id=serm_custid', 'left')
        ->join('customer_vehicle', 'custveh_id = serm_vehid', 'left')
        ->join('cat_vehicle_data', 'id=custveh_veh_id', 'left')
        ->join('status_master', 'sm_id=serm_status', 'left')
        ->join('users', 'us_id=serm_createdby', 'left')
        ->where('serm_deleteflag', 0)
        ->where('serm_active_flag', 0)
        ->where('serm_status!=', 34)
        ->where('serm_status!=', 32)
        ->orderBy('serm_number', 'DESC')
        ->findAll();

    if (!$result) {
        return $this->respond(['ret_data' => 'fail', 'Message' => 'No Pending service request'], 200);
    }

    // Fetch all related data in one go
    $serm_ids = array_column($result, 'serm_id');
    $quotes = $quotemasterModel->whereIn('qtm_serm_id', $serm_ids)->findAll();
    $vendors = $vendorMasterModel->whereIn('vm_serm_id', $serm_ids)->findAll();

    // Create associative arrays for quick lookup
    $quote_data = [];
    foreach ($quotes as $quote) {
        $quote_data[$quote['qtm_serm_id']] = $quote;
    }

    $vendor_data = [];
    foreach ($vendors as $vendor) {
        $vendor_data[$vendor['vm_serm_id']] = $vendor;
    }

    // Combine results with quote and vendor data
    $data_res = [];
    $open_requests = 0;

    foreach ($result as $row) {
        $serm_id = $row['serm_id'];
        $combined = $row;

        if (isset($quote_data[$serm_id])) {
            $combined = array_merge($combined, $quote_data[$serm_id]);
        }

        if (isset($vendor_data[$serm_id])) {
            $combined = array_merge($combined, $vendor_data[$serm_id]);
        }

        $data_res[] = $combined;

        if ($row['serm_status'] == 20) {
            $open_requests++;
        }
    }

    return $this->respond(['open_request' => $open_requests, 'ret_data' => 'success', 'result' => $data_res], 200);
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
        } else if ($tokendata['aud'] == 'user') {

            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $CustomerMasterModel = new CustomerMasterModel();
        $serequestModel = new ServiceRequestMasterModel();
        $servicesModel = new ServiceRequestItemsModel();
        $servicepackageModel = new ServiceRequestPackageModel();
        $UsersModel = new UsersModel();
        $serequestmediaModel = new ServiceRequestMediaModel();
        $quotemasterModel = new QuoteMasterModel();
        $vendorMasterModel = new VendorModel();
        $paymenttrackerModel = new PaymentTrackermasterModel();
        $servicesmappingModel = new ServicesMappingModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $workcardsettingsModel = new WorkCardSettingsModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $customerdatacardModel = new CustomerDataCardModel();
        $customerdiscountModel = new CustomerDiscountModel();
        $requestmediaModel = new RequestMediaModel();

        $result = $serequestModel->where('serm_deleteflag', 0)->where('serm_id', base64_decode($id))
            ->join('status_master', 'sm_id=serm_status', 'left')
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=customer_vehicle.custveh_veh_id')
            ->join('vendor_master', 'vm_serm_id=serm_id', 'left')
            ->first();


        if ($result) {
            $result['customer_details'] = $result ? $custModel
                ->where('cstm_id',  $result['serm_custid'])
                ->findAll() : [];

            $result['cus_media']['documents'] = $result ? $requestmediaModel
                ->where('rmedia_request_id',  base64_decode($id))
                ->where('rmedia_type', 4)
                ->where('rmedia_by_type', 0)
                ->findAll() : [];

            $result['customer_dicounts'] = $result['cstm_type'] == 1 ? $customerdiscountModel->where('cd_active_flag', 0)->first() : [];

            $result['data_cards'] = $result['custveh_datacard_url'] != 0 ?
                $customerdatacardModel->where('cvehcard_delete_flag', 0)->where('cvehcard_custveh_id', $result['custveh_id'])->findAll() : [];

            $result['days_left_to_reopen'] = 0;
            if ($result['serm_wkc_date'] != null && $result['serm_reopen_flag'] == 0) {

                $reopen_days = $workcardsettingsModel->select('ws_rp_days')->where('ws_delete_flag', 0)->first();
                $wkc_date = Time::createFromFormat('Y-m-d H:i:s', $result['serm_wkc_date']);
                $current_date = Time::now();
                $time_difference_seconds = $current_date->getTimestamp() - $wkc_date->getTimestamp();
                $time_difference_minutes = floor($time_difference_seconds / 60);
                $time_difference_hours = floor($time_difference_minutes / 60);
                $time_difference_days = floor($time_difference_hours / 24);

                $days = $reopen_days['ws_rp_days'] - $time_difference_days;
                $result['days_left_to_reopen'] = $days > 0 ? $days : 0;
                if ($days > 0) {
                    $current__newdate = new DateTime();
                    $current__newdate->add(new DateInterval("P{$days}D"));
                    $result['date_left_to_reopen'] = $current__newdate->format('d-m-y');
                }
            }





            $rpt_data = $paymenttrackerModel->where('rpt_type', 1)->where('rpt_reqt_id', base64_decode($id))->first();
            if ($rpt_data) {
                $result['rpt_id'] = $rpt_data['rpt_id'];
                $result['rpt_data'] = $rpt_data;
            }




            $approvalmasterModel = new ApprovalmasterModel();
            $app_Data_work = $approvalmasterModel
                ->where('am_reqid', base64_decode($id))
                ->whereIn('am_type', [4, 6])
                ->orderBy('am_id', 'desc')
                ->first();

            if (!$app_Data_work) {
                $app_Data_work = 0;
            }
            $quote_data = $quotemasterModel->where('qtm_type', 0)->Where('qtm_serm_id', $result['serm_id'])->first();

            if ($quote_data) {
                $result['qtm_id'] = $quote_data['qtm_id'];
                $result['qtm_rejected_reason'] = $quote_data['qtm_rejected_reason'];
            } else {
                $result['qtm_id'] = null;
            }

            $services = [];
            if ($result['serm_vendor_flag'] == 1) {
                $services = $servicesModel->where('sitem_serid', base64_decode($id))
                    ->where('sitem_deleteflag', 0)
                    ->findAll();
                $vend_Det = $vendorMasterModel->where('vm_serm_id', base64_decode($id))
                    ->orderBy('vm_id', 'desc')
                    ->first();

                $outdata = $services;
            } else {
                $services = $servicesModel->where('sitem_serid', base64_decode($id))
                    ->where('sitem_deleteflag', 0)
                    ->findAll();
                $vend_Det = 0;

                if ($result['serm_status'] == 28 || $result['serm_status'] == 29 || $result['serm_status'] == 50) {

                    if (($app_Data_work) == 0) {
                        for ($i = 0; $i < sizeof($services); $i++) {
                            $app_job = $approvalmasterModel
                                ->whereIn('am_type', [5, 7])
                                ->where('am_reqid', $services[$i]['sitem_id'])
                                ->orderBy('am_id', 'desc')
                                ->first();
                            if ($app_job) break;
                        }
                        if ($app_job) {
                            $app_Data_work = $app_job;
                        } else {
                            $app_Data_work = 0;
                        }
                    }
                }
                $outdata = [];

                for ($i = 0; $i < sizeof($services); $i++) {


                    if ($services[$i]['sitem_type'] == 0) {

                        if($services[$i]['sitem_assignee_type'] == 1){
                            $data = $servicepackageModel->where('servpack_id', $services[$i]['sitem_itemid'])
                            ->first();
                            $sitemAssign = $CustomerMasterModel->select('customer_master.cstm_name as sitem_assign')
                            ->where('cstm_id', $services[$i]['sitem_assignee'])
                            ->first();
                           
                            if ($sitemAssign) {
                                 $data['sitem_assign'] = $sitemAssign['sitem_assign'];;
                            } else {
                                $data['sitem_assign'] = null;
                            }
                        }else{
                            $data = $servicepackageModel->where('servpack_id', $services[$i]['sitem_itemid'])
                            ->first();
                        }
                    } else {
                        $data = $ToolDetailsModel->where('tool_id', $services[$i]['sitem_itemid'])->first();
                        $services[$i]['tools'] = $data;
                        if ($services[$i]['sitem_status_flag'] == 4) {
                            $data['requet_details'] = $toolrequestmasterModel
                                ->where('tldet_id', $services[$i]['sitem_reference'])
                                ->join('status_master', 'sm_id=tldt_status', 'left')
                                ->first();
                        }
                    }
                    $outdata[$i] = $services[$i] + $data;
                }
            }
            if ($result['serm_assigne']) {

                if ($result['serm_vendor_flag'] == 0) {

                    $det = $UsersModel->where('us_id', $result['serm_assigne'])->first();
                    $result['assignee_name'] = $det['us_firstname'];
                } else {

                    $det = $result['expert_details'] = $CustomerMasterModel->where('cstm_id', $result['serm_assigne'])->first();
                    $result['expert_details']['status_Details'] = $vend_Det;

                    $result['assignee_name'] = $result['expert_details']['cstm_name'];
                }
            } else {
                $det = 0;
            }
        }



        $packages = $servicepackageModel->where('servpack_delete_flag', 0)->findAll();

        for ($i = 0; $i < sizeof($packages); $i++) {
            $map_d = [];
            $map_d = $servicesmappingModel->where('srm_delete_flag', 0)
                ->where('srm_servpack_id', $packages[$i]['servpack_id'])
                ->join('tool_details', 'tool_id=srm_tool_id')
                ->findAll();
            $packages[$i]['tools'] = $map_d ? $map_d : 0;
        }

        $medias = $serequestmediaModel->where('smedia_deleteflag', 0)->where('smedia_sereqid', base64_decode($id))->findAll();

        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            $response['services'] = $outdata;
            $response['medias'] = $medias;
            $response['Packages'] = $packages;
            $response['app_workcard'] = $app_Data_work;
            $response['Assigne_det'] = $det;
            $response['vend_Det'] = $vend_Det;
            $response['imageurl'] = getenv('AWS_URL');



            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No details for this service request';
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
        $UserModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $seqModel = new SequenceGeneratorModel();
        $serequestModel = new ServiceRequestMasterModel();
        $customervehicleModel = new CustomerVehicleModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $serequestmediaModel = new ServiceRequestMediaModel();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $rules = [
            'serm_custid' => 'required',
            'custveh_vinnumber' => 'required',
            'custveh_regnumber' => 'required',

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $seq = $seqModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_SR" . $seq[0]['request_sequence']);
        $check_veh_exist = $customervehicleModel->where('custveh_vinnumber', ($this->request->getVar('custveh_vinnumber')))
            ->where('custveh_veh_id', ($this->request->getVar('serm_vehid')))
            ->where('custveh_cust_id', ($this->request->getVar('serm_custid')))
            ->first();
        $vehdata = [
            'custveh_veh_id' => $this->request->getVar('serm_vehid'),
            'custveh_regnumber' => $this->request->getVar('custveh_regnumber'),
            'custveh_vinnumber' => $this->request->getVar('custveh_vinnumber'),
            'custveh_cust_id' => $this->request->getVar('serm_custid'),
        ];

        if ($check_veh_exist) {
            $customervehicleModel->update($check_veh_exist['custveh_id'], $vehdata);
            $cust_veh_id = $check_veh_exist['custveh_id'];
        } else {
            $cust_veh_id = $customervehicleModel->insert($vehdata);
        }
        $inData = [
            'serm_custid' => $this->request->getVar('serm_custid'),
            'serm_vehid' => $cust_veh_id,
            'serm_number' => $nextval,
            'serm_complaint' => $this->request->getVar('serm_complaint'),
            'serm_createdon' => $date,
            'serm_createdby' => $tokendata['uid'],
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid'],
            'serm_assigne' =>  $this->request->getVar('serm_assigne'),
            'serm_vendor_flag' =>  $this->request->getVar('serm_vendor_flag'),
        ];
        $result = $serequestModel->insert($inData);

        // $imageFile = $this->request->getFile('service_request_audio');
        // $imageFile->move(ROOTPATH . 'public/uploads/ServiceRequest_audio');
        // $infdata = [
        //     'smedia_sereqid'   => $result,
        //     'smedia_type' => 1,
        //     'smedia_url' =>  'uploads/ServiceRequest_audio/' . $imageFile->getName(),
        //     'smedia_createdby' => $tokendata['uid'],
        //     'smedia_updatedby' => $tokendata['uid'],
        // ];
        // $serequestmediaModel->insert($infdata);

        $hist = [
            'srh_serm_id' => $result,
            'srh_status_id' => 19,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];
        $servicehistoryModel->insert($hist);


        $data = [
            'serm_active_flag' => 0,
            'serm_status' => 57,
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];
        $serequestModel->update($result, $data);
        $hist1 = [
            'srh_serm_id' => $result,
            'srh_status_id' => 20,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']

        ];
        $servicehistoryModel->insert($hist1);

        $hist2 = [
            'srh_serm_id' => $result,
            'srh_status_id' => 57,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']

        ];
        $servicehistoryModel->insert($hist2);


        if ($result) {
            $seq = (intval($seq[0]['request_sequence']) + 1);
            $seq_data = ['request_sequence' => $seq];
            $seqModel->update(1, $seq_data);
            $data['request_id'] = $result;
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->fail($data, 400);
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
        $UserModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $seqModel = new SequenceGeneratorModel();
        $serequestModel = new ServiceRequestMasterModel();
        $serequestitemsModel = new ServiceRequestItemsModel();
        $serequestmediaModel = new ServiceRequestMediaModel();
        $services = $this->request->getVar("services");
        $attachments = $this->request->getVar("attachments");
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
            'customer_id' => 'required',
            'service_request_vehicle_brand' => 'required',
            'service_request_vehicle_model' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $seq = $seqModel->where("RAMS_SR", 1)->first();
        $nextval = ("REQ" . $seq['0']);
        $inData = [
            'serm_custid' => $this->request->getVar('customer_id'),
            'serm_vehid' => $this->request->getVar('vehicle_id'),
            'serm_number' => $nextval,
            'serm_complaint' => $this->request->getVar('service_request_complaint_details'),
            'serm_cost' => $this->request->getVar('cost'),
            'serm_createdon' => $date,
            'serm_createdby' => $tokendata['uid'],
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];

        $result = $serequestModel->update($this->request->getVar('customer_id'), $inData);
        if ($result) {
            if (count($services) > 0) {

                $in_data = array();
                for ($i = 0; $i < count($services); $i++) {

                    $infdata = [
                        'sitem_itemid'   => $services[$i]->ser_id,
                        'sitem_serid'   => $result,
                        'sitem_type' => 0,
                        'sitem_cost' => $services[$i]->cost,
                        'sitem_createdon' => $date,
                        'sitem_createdby' =>  $tokendata['uid'],
                        'sitem_updatedby' =>  $tokendata['uid'],
                        'sitem_updatedon' => $date,
                    ];
                    array_push($in_data, $infdata);
                }
                $ret = $serequestitemsModel->updateBatch($in_data);
            }
        }
        $in_data = array();
        if (count($attachments) > 0) {
            for ($i = 0; $i < count($attachments); $i++) {
                $infdata = [
                    'smedia_sereqid'   => $result,
                    'smedia_type' => $attachments[$i]->ftype,
                    'smedia_url' => $attachments[$i]->url,
                    'smedia_createdby' => $tokendata['uid'],
                    'smedia_updatedby' => $tokendata['uid'],
                ];
                array_push($in_data, $infdata);
            }
            $ret = $serequestmediaModel->insertBatch($in_data);
        }
        if ($result) {
            $seq = (intval($seq['request_sequence']) + 1);
            $seq_data = ['request_sequence' => $seq];
            $seqModel->update(1, $seq_data);
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
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
        //
    }

    public function create_service_request()
    {

        $UserModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $seqModel = new SequenceGeneratorModel();
        $serequestModel = new ServiceRequestMasterModel();
        $customervehicleModel = new CustomerVehicleModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $serequestmediaModel = new ServiceRequestMediaModel();
        $notificationmasterModel = new NotificationmasterModel();
        $userModel = new UsersModel();
        $custModel = new CustomerMasterModel();
        $toolrequestmastercontroller = new ToolRequestMasterController;
        $notificationmasterController = new UsersNotificationController;
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
        $rules = [
            'customer_id' => 'required',
            'service_request_vehicle_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $seq = $seqModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_SR" . $seq[0]['request_sequence']);
        $check_veh_exist = $customervehicleModel->where('custveh_regnumber', ($this->request->getVar('service_request_vehicle_registration_no')))
            ->where('custveh_veh_id', ($this->request->getVar('service_request_vehicle_id')))
            ->where('custveh_cust_id', ($this->request->getVar('customer_id')))
            ->first();
        $vehdata = [
            'custveh_veh_id' => $this->request->getVar('service_request_vehicle_id'),
            'custveh_regnumber' => $this->request->getVar('service_request_vehicle_registration_no'),
            'custveh_vinnumber' => $this->request->getVar('service_request_vin_no'),
            'custveh_odometer' => $this->request->getVar('odometer'),
            'custveh_cust_id' => $this->request->getVar('customer_id'),
        ];

        if ($check_veh_exist) {
            $customervehicleModel->update($check_veh_exist['custveh_id'], $vehdata);
            $cust_veh_id = $check_veh_exist['custveh_id'];
        } else {
            $cust_veh_id = $customervehicleModel->insert($vehdata);
        }
        $inData = [
            'serm_custid' => $this->request->getVar('customer_id'),
            'serm_vehid' => $cust_veh_id,
            'serm_number' => $nextval,
            'serm_complaint' => $this->request->getVar('service_request_complaint_details'),
            'serm_createdby' => 1,
            'serm_createdon' => $date,
            'serm_createdby' => $tokendata['uid'],
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']

        ];
        $result = $serequestModel->insert($inData);
        // return $this->respond($result, 200);

        $audFile =$this->request->getvar('base_version')==='local'?
         $this->request->getFile('service_request_audio'):
         $this->request->getvar('service_request_audio');
        if ($audFile) {
            $audio_data=$this->request->getvar('base_version')==='local'?
             $this->audio_cUpload($this->request->getFile('service_request_audio'),$this->request->getVar('base_version')):
             $this->audio_cUpload($this->request->getvar('service_request_audio'),$this->request->getVar('base_version'));
                $infdata = [
                    'smedia_sereqid'   => $result,
                    'smedia_type' => 1,
                    'smedia_url' =>  $audio_data['path'],
                    'smedia_createdby' => $tokendata['uid'],
                    'smedia_updatedby' => $tokendata['uid'],
                ];
                $serequestmediaModel->insert($infdata);
        }
        $docfile =$this->request->getvar('base_version')==='local'?
         $this->request->getFile('service_request_document'):
         $this->request->getvar('service_request_document');
        if ($docfile) {
           $doc_data= $this->request->getvar('base_version')==='local'?
           $this->document_cUpload($this->request->getFile('service_request_document'),$this->request->getVar('base_version')):
           $this->document_cUpload($this->request->getvar('service_request_document'),$this->request->getVar('base_version'));
            $requestmediaModel = new RequestMediaModel();
            $docdata = [
                'rmedia_request_id'   => $result,
                'rmedia_type' => 4,
                'rmedia_url_type' => 0,
                'rmedia_url' =>  $doc_data['path'],
                'rmedia_by_type' => 0,
                'rmedia_created_on' => $date,
                'rmedia_updated_on' => $date,
                'rmedia_created_by' => $tokendata['uid'],
                'rmedia_updated_by' => $tokendata['uid'],
            ];
            $requestmediaModel->insert($docdata);
        }




        if ($result) {
            $seq = (intval($seq[0]['request_sequence']) + 1);
            $seq_data = ['request_sequence' => $seq];
            $seqModel->update(1, $seq_data);
            $data['request_id'] = $result;
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->fail($data, 400);
        }
        // } else {
        //     $data['ret_data'] = "Invalid user";
        //     return $this->fail($data, 400);
        // }
    }

    public function service_request_history()
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
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $servicerequestitemsModels = new ServiceRequestItemsModel();

        $hist_data = $servicerequestitemsModels->where('sitem_serid', $this->request->getVar('sitem_serid'))
            ->join('service_request_package', 'servpack_id =sitem_itemid')
            ->join('servicerequest_master', 'serm_id =sitem_serid')
            ->join('status_master', 'sm_id =serm_status');

        if ($hist_data) {
            $response['Data'] = $hist_data;
            $response['ret_data'] = 'success';
            return $this->respond($response, 200);
        } else {
            $response['Message'] = 'failed';
            return $this->respond($response, 200);
        }
    }

    public function imageupload()
    {

        helper(['form', 'url']);
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
        $serequestmediaModel = new ServiceRequestMediaModel();

        $imageFile = $this->request->getFile('uploadFile');
        // $data['ret_data'] = "success";
        // $data['imageFile'] = $imageFile;
        // return $this->respond($data, 200);
        $imageFile->move(ROOTPATH . 'public/uploads/Servicerequest_images');
        $data = [
            'img_name' => $imageFile->getName(),
            'file'  => $imageFile->getClientMimeType(),
            'path' => ROOTPATH,
            'test' => $this->request->getVar("test_data"),
        ];
        if ($this->request->getVar("service_req_id")) {
            $infdata = [
                'smedia_sereqid'   => $this->request->getVar("service_req_id"),
                'smedia_type' => 0,
                'smedia_url' => 'uploads/Servicerequest_images/' . $imageFile->getName(),
                'smedia_createdby' => 1,
                'smedia_updatedby' => 1,
            ];
            $ret = $serequestmediaModel->insert($infdata);
        }
        $data['ret_data'] = "success";

        return $this->respond($data, 200);
    }
    public function imagecUpload()
    {

        helper(['form', 'url']);
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
        $serequestmediaModel = new ServiceRequestMediaModel();
        $imageFile = $this->request->getvar('uploadFile');
        if ($imageFile != '') {
            $image_parts = explode(";base64,", $imageFile);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_name = date("d-m-Y") . "-" . time() . "." . $image_type_aux[1];
            $img_data = $commonutils->image_upload(base64_decode($image_parts[1]), $image_name, 'Service_request/Servicerequest_images', 'image/' . $image_type_aux[1], false);
            $img_url = $img_data?"Service_request/Servicerequest_images/" . $image_name:"";
            if ($this->request->getVar("service_req_id")) {
                $infdata = [
                    'smedia_sereqid'   => $this->request->getVar("service_req_id"),
                    'smedia_type' => 0,
                    'smedia_url' => $img_url,
                    'smedia_createdby' => 1,
                    'smedia_updatedby' => 1,
                ];
                $ret = $serequestmediaModel->insert($infdata);
            }
            $data=['ret_data'=>'success','smedia_id'=>$ret];
        } else{
            $data['ret_data'] = "fail";
            $data['Message'] = "Failed to get image URL";
            $data['uploadFile']=$this->request->getvar('uploadFile');
        }
        return $this->respond($data, 200);
    }

    public function fetch_service_pack()
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
        $packid = $this->request->getVar('serv_id');
        if ($packid) {
            $data = $ServicePackageModel->where('servpack_id', $packid)->get()->getResult();
        } else {
            $response['Message'] = 'No Pack id';
            return $this->respond($response, 200);
        }
        if ($data) {
            $response = [
                'ret_data' => 'success',
                'Service_Request_details' => $data
            ];
        } else {
            $response['Message'] = 'error fetching details';
        }
        return $this->respond($response, 200);
    }
    public function update_serv_details()
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
        $servdata = $this->request->getVar('serv_data');
        $date = date("Y-m-d H:i:s");
        if ($servdata) {
            $servpack_id = $servdata['serv_id'];
            $data_updt = [

                'servpack_name' => $servdata['servpack_name'],
                'servpack_desc' => $servdata['servpack_desc'],
                'servpack_cost' => $servdata['servpack_cost'],
                'servpack_active_flag' => $servdata['servpack_active_flag'],
                'servpack_updated_by' => $tokendata['uid'],
                'servpack_updated_on' => $date,

            ];
            $results = $ServicePackageModel->update($this->db->escapeString($servpack_id), $data_updt);
        } else {
            $response['Message'] = 'No service data';
            return $this->respond($response, 200);
        }
        if ($results) {
            $response = [
                'ret_data' => 'success',
                'Message' => 'Package Succefully Updated'
            ];
        } else {
            $response['Message'] = 'Data not updated';
        }
    }

    public function vehicle_make_list()
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
        $vehmaster = new CatVehicleDataModel();
        $sequencedataModel = new SequenceGeneratorModel();
        $seq = $sequencedataModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_SR" . $seq[0]['request_sequence']);
        $vehmake = $vehmaster->select('make_name')->distinct()->findAll();
        if ($vehmake) {
            $response = [
                'ret_data' => 'success',
                'veh_make' => $vehmake,
                'request_id' => $nextval
            ];
        } else {
            $response['Message'] = 'No Vehicle Data';
        }
        return $this->respond($response, 200);
    }
    public function vehicle_model_list()
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
        $vehmaster = new CatVehicleDataModel();
        $make_name = $this->request->getVar("make_name");
        if ($make_name) {
            $vehmodel = $vehmaster->select('model_name')->where('make_name', $make_name)->distinct()->findAll();
            if ($vehmodel) {
                $response = [
                    'ret_data' => 'success',
                    'veh_model' => $vehmodel
                ];
            } else {
                $response['Message'] = 'No Model Data';
            }
        } else {
            $response['Message'] = 'No Vehicle Id';
        }
        return $this->respond($response, 200);
    }
    public function vehicle_varient_list()
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
        $vehmaster = new CatVehicleDataModel();
        $vehmodelname = $this->request->getVar("model_name");
        if ($vehmodelname) {
            $vehvarient = $vehmaster->select('variant_name,id')->where('model_name', $vehmodelname)->distinct()->findAll();
            if ($vehvarient) {
                $response = [
                    'ret_data' => 'success',
                    'veh_varients' => $vehvarient,

                ];
            } else {
                $response['Message'] = 'No Model Data';
            }
        } else {
            $response['Message'] = 'No Vehicle Id';
        }
        return $this->respond($response, 200);
    }

    public function service_requestbycustomer()
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
        $serequestModel = new ServiceRequestMasterModel();
        $UsersModel = new UsersModel();
        $customervehicleModel = new CustomerVehicleModel();

        $serviceRequestMediaModel =  new ServiceRequestMediaModel();
        $result = $serequestModel
            ->where('serm_deleteflag', 0)
            ->where('serm_active_flag', 0)
            ->where('serm_status!=', 32)
            ->where('serm_status!=', 34)
            ->where('serm_custid', $tokendata['uid'])
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->join('status_master', 'sm_id=serm_status')
            ->orderBy('serm_id', 'desc')

            ->findAll();
        $requestdata = [];
        if (sizeof($result) > 0) {
            foreach ($result as $eachdata) {
                $eachdata['medias'] = $serviceRequestMediaModel->where('smedia_deleteflag', 0)->where('smedia_sereqid', $eachdata['serm_id'])->findAll();
                array_push($requestdata, $eachdata);
            }
        }
        if (sizeof($requestdata) > 0) {
            $response['ret_data'] = "success";
            $response['result'] = $requestdata;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No Pending service request';
            return $this->respond($response, 200);
        }
    }

    public function update_assign_to()
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
        $date = date("Y-m-d H:i:s");
        $serequestModel = new ServiceRequestMasterModel();
        $inData = [
            'serm_createdby' => $this->request->getVar('us_id'),
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];

        $result = $serequestModel->update($this->request->getVar('serm_id'), $inData);
        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'Cannot Update';
            return $this->respond($response, 200);
        }
    }
    public function update_servpackage()
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
        $date = date("Y-m-d H:i:s");
        $serequestModel = new ServiceRequestMasterModel();
        $inData = [
            'serm_createdby' => $this->request->getVar('us_id'),
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];

        $result = $serequestModel->update($this->request->getVar('serm_id'), $inData);
        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'Cannot Update';
            return $this->respond($response, 200);
        }
    }

    public function send_quote()
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
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $result = $servicequestModel->where('serm_deleteflag', 0)->where('serm_id', $this->request->getVar("serm_id"))->join('servicerequest_items', 'sitem_serid=serm_id')->findAll();
        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'Cannot Update';
            return $this->respond($response, 200);
        }
    }

    public function serv_history_byid()
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
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $servicequestModel = new ServiceRequestMasterModel();

        $result = $servicequestModel
            ->where('serm_id', $this->request->getVar('serm_id'))
            ->join('status_master', 'sm_id=serm_status')
            ->first();

        $data = $servicehistoryModel->where('srh_serm_id ', $this->request->getVar('serm_id'))->join('status_master', 'sm_id=srh_status_id')->findAll();
        $hist_data['hist_details'] = $data;
        if ($data) {
            $response = [
                'ret_data' => 'success',
                'history_details' => $hist_data,
                'current_status' => $result
            ];
        } else {
            $response['Message'] = 'failed';
        }

        return $this->respond($response, 200);
    }

    public function reject_request()
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
        $date = date("Y-m-d H:i:s");
        $serequestModel = new ServiceRequestMasterModel();
        $customervehicleModel = new CustomerVehicleModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $notificationMasterController = new UsersNotificationController;
        // return $this->respond(base64_decode($this->request->getVar('serm_id')), 200);

        $res = [
            'serm_reject_user' => $this->request->getVar('reason'),
            'serm_status' => 34,
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];
        $ins = [
            'srh_status_id' => 34,
            'srh_serm_id' => base64_decode($this->request->getVar('serm_id')),
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];
        $servicehistoryModel->insert($ins);

        $result = $serequestModel->update(base64_decode($this->request->getVar('serm_id')), $res);
        $serm_data=$serequestModel->Where('serm_id',base64_decode($this->request->getVar('serm_id')))->first();
        $ntf_data = [
            'id' => $serm_data['serm_custid'],
            'headers' => "Request Rejected ",
            'content' => "Request Rejected " .$serm_data['serm_number'] ,
            'sourceid' => $tokendata['uid'],
            'destid' => $serm_data['serm_custid'],
            'nt_req_number' => $serm_data['serm_number'],
            'nt_type' => 0,
            'nt_request_type' => 0,
            'nt_type_id' => $serm_data['serm_id'],
            'date' => $date
        ];
        $nt_id = $notificationMasterController->create_cust_notification($ntf_data);
        if ($result) {
            $response['ret_data'] = 'success';
        } else {
            $response['Message'] = 'Didnt updated';
        }
        return $this->respond($response, 200);
    }


    public function fetch_history()
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
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $result = $servicehistoryModel
            ->where('serm_active_flag', 0)
            ->join('servicerequest_master', 'serm_id=srh_serm_id')
            ->join('status_master', 'sm_id=srh_status_id')
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('users', 'us_id=serm_createdby')
            ->orderBy('srh_serm_id', 'desc')
            ->findAll();
        if ($result) {
            $response['ret_data'] = 'success';
            $response['result'] = $result;
        } else {
            $response['Message'] = 'No history data';
        }
        return $this->respond($response, 200);
    }




    public function completed_requestbyid()
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
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $serm_req = $servicerequestMasterModel
            ->where('serm_custid', $tokendata['uid'])
            ->where('serm_status', 32)
            ->orwhere('serm_status', 34)
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->orderBy('serm_id', 'desc')->findAll();
        if ($serm_req) {
            $response = [
                'ret_data' => 'success',
                'data' => $serm_req
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'Message' => 'No data for this request'
            ];
        }
        return $this->respond($response, 200);
    }

    public function fetch_sr_timeline()
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
            'serm_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $response['ret_data'] = 'error';
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $servicerequestmasterModel = new ServiceRequestMasterModel();
        $statusmaster = new StatusMasterModel();

        $curr_data = $servicerequestmasterModel->select('sm_name,sm_code,sm_id')->where('serm_id', base64_decode($this->request->getVar('serm_id')))->join('status_master', 'serm_status=sm_id')->first();
        $history_data = $servicehistoryModel->where('srh_serm_id', base64_decode($this->request->getVar('serm_id')))->get()->getResult();
        if ($history_data) {
            for ($i = 0; $i < sizeof($history_data); $i++) {
                $serm_id = $history_data[$i]->srh_serm_id;
                $status_id = $history_data[$i]->srh_status_id;
                $status_created_on = $history_data[$i]->srh_created_on;
                $serv_req_statusname = $statusmaster->select('sm_name,sm_code')->where('sm_id', $status_id)->findAll();
                $serv_req_statusname = $serv_req_statusname[0];
                $serv_req_status = $serv_req_statusname['sm_name'];
                $serv_req_status_code = $serv_req_statusname['sm_code'];
                $histdata = [
                    'serm_id' => $serm_id,
                    'status_id' => $status_id,
                    'status_name' => $serv_req_status,
                    'status_code' => $serv_req_status_code,
                    'status_created_on' => $status_created_on
                ];

                $data[$i] = $histdata;
            }
            $ret_data['history_details'] = $data;
            $response = [
                'ret_data' => 'success',
                'srqst' => $ret_data,
                'sr_status' => $curr_data,
            ];
        }

        return $this->respond($response, 200);
    }


    public function serv_hist()
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
        $serequestModel = new ServiceRequestMasterModel();
        $UsersModel = new UsersModel();
        $result = $serequestModel->where('serm_deleteflag', 0)
            ->where('serm_active_flag', 0)
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->join('status_master', 'sm_id=serm_status')
            ->join('users', 'us_id=serm_createdby', 'left')
            ->orderBy('serm_id', 'desc')
            ->findAll();
        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No Pending service request';
            return $this->respond($response, 200);
        }
    }
    public function update_assigne()
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
        $vendorMasterModel = new VendorModel();
        $notificationmasterController = new UsersNotificationController;
        $notificationmasterModel=new NotificationmasterModel();
        $date = date("Y-m-d H:i:s");
        if ($this->request->getVar('flag')) {
            $data = [
                'serm_assigne' => $this->request->getVar('serm_assigne'),
                'serm_vendor_flag' => 1,
                'serm_status' => 20,
                'serm_updatedon' => $date,
                'serm_updatedby' => $tokendata['uid']
            ];
            $hist_Data = [
                'srh_serm_id' => base64_decode($this->request->getVar('serm_id')),
                'srh_status_id' => 20,
                'srh_created_on' => $date,
                'srh_created_by' => $tokendata['uid']
            ];

            $lastVendor=$vendorMasterModel->where('vm_serm_id',base64_decode($this->request->getVar('serm_id')))->where('vm_delete_flag',0)->where('vm_active_flag',0)->first();
            //  return $this->respond($lastVendor,200);
            if($lastVendor){
                $inactiveLastVendor=[
                    'vm_updated_by' => $tokendata['uid'],
                    'vm_updated_on' => $date,
                    'vm_active_flag'=>1, //refer to vendorMasterModel
                    'vm_status'=>12 // refer to vendorMasterModel
                ];
                $vendorMasterModel->update($lastVendor['vm_id'],$inactiveLastVendor);
            }
            $vend_data = [
                'vm_cstm_id' => $this->request->getVar('serm_assigne'),
                'vm_serm_id' => base64_decode($this->request->getVar('serm_id')),
                'vm_status' => 0,
                'vm_created_by' => $tokendata['uid'],
                'vm_created_on' => $date,
                'vm_updated_by' => $tokendata['uid'],
                'vm_updated_on' => $date,
            ];

            $vendorMasterModel->insert($vend_data);
            $serequestModel->update(base64_decode($this->request->getVar('serm_id')), $data);
            
            // $serm_data = $serequestModel->where('serm_id', base64_decode($this->request->getVar('serm_id')))->first();
            // $us_id = $userModel->where('us_id', $this->request->getVar('serm_assigne'))->findAll();
            // $ntf_data = [];

            // foreach ($us_id as $eachurl) {

            //     $notife_data = [
            //         'nt_sourceid' =>$tokendata['uid'],
            //         'nt_destid' => $eachurl['us_id'],
            //         'nt_sourcetype' => 0,
            //         'nt_header' => "Service Request Assigned",
            //         'nt_content' => "Service Request" . $serm_data['serm_number'] . " Assigned!!",
            //         'nt_created_on' =>  $date,
            //         'nt_request_type'=>0,
            //         'nt_type_id'=> $serm_data['serm_id'],
            //         'nt_type'=>0,
                    
            //     ];
            //     array_push($ntf_data, $notife_data);
            //     // $indata = [
            //     //     'id' => $eachurl['us_id'],
            //     //     'headers' => "Service Request Assigned",
            //     //     'content' => "Service Request" . $serm_data['serm_number'] . " Assigned!!",
            //     //     'sourceid' => $tokendata['uid'],
            //     //     'destid' => $eachurl['us_id'],
            //     //     'date' => $date,
            //     //     'nt_request_type' => 0,
            //     //     'nt_type_id' => $serm_data['serm_id'],
            //     //     'nt_type' => 0,
            //     //     'nt_req_number'=>$serm_data['serm_number']
            //     // ];
            //     // array_push($ntf_data, $indata);
            // }
           
            // $ret=$notificationmasterModel->insertBatch($ntf_data);
           //$ret= $notificationmasterController->create_us_notification($ntf_data);
        } else {
            $data = [
                'serm_assigne' => $this->request->getVar('data')
            ];

            $serequestModel->update(($this->request->getVar('serm_id')), $data);

            $serm_data = $serequestModel->where('serm_id', $this->request->getvar('serm_id'))->first();

            $us_id = $userModel->where('us_id', $serm_data['serm_assigne'])->findAll();
            $ntf_data = [];

            // foreach ($us_id as $eachurl) {
            //     $indata = [
            //         'id' => $eachurl['us_id'],
            //         'headers' => "Service Request Assigned",
            //         'content' => "Service Request" . $serm_data['serm_number'] . " Assigned!!",
            //         'sourceid' => $tokendata['uid'],
            //         'destid' => $eachurl['us_id'],
            //         'date' => $date,
            //         'nt_request_type' => 0,
            //         'nt_type_id' => $serm_data['serm_id'],
            //         'nt_type' => 0,
            //         'nt_req_number'=>$serm_data['serm_number']
            //     ];
            //     array_push($ntf_data, $indata);
            // }

            // $notificationmasterController->create_us_notification($ntf_data);
        }


        $response = [
            'ret_data' => 'success',
        ];
        return $this->respond($response, 200);
    }


    public function actvt_serm_rq()

    {
        $userModel = new UsersModel();
        $custModel = new CustomerMasterModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        $notificationmasterController = new UsersNotificationController;
        if ($tokendata['aud'] == 'customer') {

            $customer = $custModel->where("cstm_id", $tokendata['uid'])->where("cstm_delete_flag", 0)->first();
            if (!$customer) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {

            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }

        $rules = [
            'serm_id' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $serm_id = $this->request->getVar('serm_id');
        $request_images = $this->request->getVar('request_images');
        $hist = [
            'srh_serm_id' => $this->request->getVar('serm_id'),
            'srh_status_id' => 19,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];
        $servicehistoryModel->insert($hist);
        $data = [
            'serm_active_flag' => 0,
            'serm_status' => 20,
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];
        $servicerequestMasterModel->update($this->request->getVar('serm_id'), $data);
        $hist1 = [
            'srh_serm_id' => $this->request->getVar('serm_id'),
            'srh_status_id' => 20,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']

        ];
        $servicehistoryModel->insert($hist1);
        $serm_data = $servicerequestMasterModel->where('serm_id', $this->request->getVar('serm_id'))->first();



        $us_id = $userModel->where('us_delete_flag', 0)->findAll();
        $ntf_data = [];

        foreach ($us_id as $eachurl) {

            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "New Service Request",
                'content' => "New Request Created " . $serm_data['serm_number'],
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_data['serm_id'],
                'nt_type' => 0,
                'nt_req_number'=>$serm_data['serm_number']
            ];
            array_push($ntf_data, $indata);
        }
        $nt_id = $notificationmasterController->create_us_notification($ntf_data);

        $response = ['ret_data' => "success", 'serm_id' => $this->request->getVar('serm_id')];


        return $this->respond($response, 200);
    }

    public function get_draftlist_cust()

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
            'cust_id' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $servicerequestMasterModel = new ServiceRequestMasterModel();

        $total_req = $servicerequestMasterModel
            ->where('serm_custid', $this->request->getVar('cust_id'))
            ->where('serm_active_flag', 1)
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->orderBy('serm_id', 'desc')->findAll();

        if ($total_req) {
            $response = [
                'ret_data' => 'success',
                'Draft_list' => $total_req
            ];
        } else {
            $response['ret_data'] = 'No Draft Request For this ID';
        }
        return $this->respond($response, 200);
    }

    public function getreq_by_role()
    {
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
            $serequestModel = new ServiceRequestMasterModel();
            $quotemasterModel = new QuoteMasterModel();
            $UsersModel = new UsersModel();
            $vendorMasterModel = new VendorModel(); {

               $result = $serequestModel
               ->select('serm_id, cstm_name, cstm_vendor_flag, cstm_id, custveh_regnumber, custveh_vinnumber, custveh_datacard_url, custveh_veh_id, make_name, model_name, variant_name, sm_name, sm_code, sm_pk_id, serm_status, serm_number, serm_id, serm_updatedon, serm_hold_flag, serm_cost, serm_vendor_flag')
                    ->where('serm_deleteflag', 0)
                    ->where('serm_vendor_flag', 0)
                    ->whereIn('serm_assigne', [$tokendata['uid'],0])
                    ->where('serm_active_flag', 0)
                    ->whereNotIn('serm_status', [34,32])
                    ->join('customer_vehicle', 'custveh_id = serm_vehid', 'left')
                    ->join('cat_vehicle_data', 'id=custveh_veh_id', 'left')
                    ->join('customer_master', 'cstm_id=serm_custid')
                    ->join('status_master', 'sm_id=serm_status')
                    ->join('users', 'us_id=serm_createdby')
                    ->orderBy('serm_id', 'desc')
                    ->findAll();

                if ($result) {
                    $data_res = array();
                    for ($i = 0; $i < sizeof($result); $i++) {

                        $quote_data[$i] = $quotemasterModel
                            ->where('qtm_serm_id', $result[$i]['serm_id'])
                            ->orderBy('qtm_id', 'desc')
                            ->first();
                        if ($quote_data[$i]) {
                            $data_res[$i] = array_merge($result[$i], $quote_data[$i]);
                        } else {
                            $data_res[$i] = $result[$i];
                        }
                    }
                    $response['ret_data'] = "success";
                    $response['result'] = $data_res;
                    return $this->respond($response, 200);
                } else {
                    $response['ret_data'] = "fail";
                    $response['Message'] = 'No Pending service request';
                    return $this->respond($response, 200);
                }
            }
        }
    }

    public function gethist_by_role()
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
        $serequestModel = new ServiceRequestMasterModel();
        $UsersModel = new UsersModel();
        $result = $serequestModel->where('serm_deleteflag', 0)
            ->where('serm_active_flag', 0)
            ->where('serm_vendor_flag', 0)
            ->where('serm_assigne', $tokendata['uid'])
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('status_master', 'sm_id=serm_status')
            ->join('users', 'us_id=serm_createdby')
            ->orderBy('serm_id', 'desc')
            ->findAll();
        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No Pending service request';
            return $this->respond($response, 200);
        }
    }


    public function check_reopen_workcard()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->join('user_roles', 'role_Id=us_role_id')->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }

        $usermastermodel = new UsersModel();
        $servicerequestmasterModel = new ServiceRequestMasterModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $workcardsettingsModel = new WorkCardSettingsModel();

        $reopen_Data = $servicerequestmasterModel
            ->where('serm_deleteflag', 0)
            ->whereIn('serm_status', [20, 30, 31, 32])
            ->findAll();

        // return "error";
        if (sizeof($reopen_Data) > 0) {

            $reopen_days = $workcardsettingsModel->select('ws_rp_days')->where('ws_delete_flag', 0)->first();

            $reopen_flag = [
                'serm_reopen_flag' => 0
            ];
            $reopen_closeflag = [
                'serm_reopen_flag' => 1
            ];

            for ($i = 0; $i < sizeof($reopen_Data); $i++) {
                if ($reopen_Data[$i]['serm_wkc_date']) {

                    $wkc_date = Time::createFromFormat('Y-m-d H:i:s', $reopen_Data[$i]['serm_wkc_date']);


                    $current_date = Time::now();

                    $time_difference_seconds = $current_date->getTimestamp() - $wkc_date->getTimestamp();
                    $time_difference_minutes = floor($time_difference_seconds / 60);
                    $time_difference_hours = floor($time_difference_minutes / 60);
                    $time_difference_days = floor($time_difference_hours / 24);


                    if ($time_difference_days <= $reopen_days['ws_rp_days']) {

                        $servicerequestmasterModel->update($reopen_Data[$i]['serm_id'], $reopen_flag);
                    } else {
                        $servicerequestmasterModel->update($reopen_Data[$i]['serm_id'], $reopen_closeflag);
                    }
                }
            }

            $response = [
                'ret_data' => 'success'
            ];
        } else {

            $response = [
                'ret_data' => 'fail'
            ];
        }
        return $this->respond($response, 200);
    }

    public function accept_request()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->join('user_roles', 'role_Id=us_role_id')->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }

        $usermastermodel = new UsersModel();
        $servicerequestmasterModel = new ServiceRequestMasterModel();
        $servicerequesthistoryModel = new ServiceRequestHistoryModel();
        $notificationmasterController = new UsersNotificationController;
        $date = date("Y-m-d H:i:s");
        $data = [
            'serm_status' => 57,
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];

        $hist_data = [
            'srh_serm_id' => $this->request->getVar('serm_id'),
            'srh_status_id' => 57,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];
        $servicerequestmasterModel->update($this->request->getVar('serm_id'), $data);
        $servicerequesthistoryModel->insert($hist_data);
        $serm_data = $servicerequestmasterModel->where('serm_id', $this->request->getVar('serm_id'))->first();
        $ntf_data = [
            'id' => $serm_data['serm_custid'],
            'headers' => "Request Accepted",
            'content' => "Request Accepted For  " . $serm_data['serm_number'] . ". Tap to see",
            'sourceid' => $tokendata['uid'],
            'destid' => $serm_data['serm_custid'],
            'nt_req_number' => $serm_data['serm_number'],
            'nt_type' => 0,
            'nt_request_type' => 0,
            'nt_type_id' => $this->request->getVar('serm_id'),
            'date' => $date
        ];
        $nt_id = $notificationmasterController->create_cust_notification($ntf_data);

        $response = [
            'ret_data' => 'success',
            'serm_id' => $this->request->getVar('serm_id')
        ];
        return $this->respond($response, 200);
    }

    public function data_card_upload()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->join('user_roles', 'role_Id=us_role_id')->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }

        $rules = [
            'data_card_images' => 'required',
            'custveh_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $customervehicleModel = new CustomerVehicleModel();
        $customerdatacardModel = new CustomerDataCardModel();
        $date = date("Y-m-d H:i:s");
        $infdata = [];
        foreach ($this->request->getVar('data_card_images') as $eachurl) {

            $indata = [
                'cvehcard_custveh_id' => $this->request->getVar('custveh_id'),
                'cvehcard_url' => $eachurl,
                'cvehcard_created_on' => $date,
                'cvehcard_created_by' => $tokendata['uid']
            ];
            array_push($infdata, $indata);
        }
        $custveh = [
            'custveh_datacard_url' => sizeof($this->request->getVar('data_card_images'))
        ];
        $customervehicleModel->update($this->request->getVar('custveh_id'), $custveh);
        $customerdatacardModel->insertBatch($infdata);
        $response['ret_data'] = 'success';
        return $this->respond($response, 200);
    }


    public function fetch_new_request()
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->join('user_roles', 'role_Id=us_role_id')->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }

        $usermastermodel = new UsersModel();
        $servicerequestmasterModel = new ServiceRequestMasterModel();
        $servicerequesthistoryModel = new ServiceRequestHistoryModel();
        $sequencedataModel = new SequenceGeneratorModel();
        $date = date("Y-m-d H:i:s");

        $seq = $sequencedataModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_SR" . $seq[0]['request_sequence']);
    }


    public function datacard_image_cUpload()
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
           helper(['form', 'url']);
           $UserModel = new UsersModel();
           $validModel = new Validation();
           $commonutils = new Commonutils();
           $data_card_image= $this->request->getvar('data_card_image');
            if ($data_card_image != '') {
                $image_parts = explode(";base64,", $data_card_image);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_name = date("d-m-Y") . "-" . time() . "." . $image_type_aux[1];
                $img_data = $commonutils->image_upload(base64_decode($image_parts[1]), $image_name, 'datacards', 'image/' . $image_type_aux[1], false);
                $img_url = $img_data?"datacards/" . $image_name:"";
                $response = [
                    'ret_data'=>"success",
                    'path' => $img_url,
                    'image_data'=>$img_data,
                    'imageurl'=>getenv('AWS_URL')
                ];
                
            } else{
                $response=[
                    'ret_data'=>'fail',
                    'Message'=>'failed to get URL'
                ];
                
            }
            return $this->respond($response,200);
    }

    public function datacard_image_upload()
    {


        helper(['form', 'url']);
        $imageFile = $this->request->getFile('toolimage');
        $imageFile->move(ROOTPATH . 'public/uploads/datacards');
        $data = [
            'img_name' => $imageFile->getName(),
            'file'  => $imageFile->getClientMimeType(),
            'path' => 'uploads/datacards/' . $imageFile->getName(),
            'test' => $this->request->getVar("test_data"),
        ];
        $data['ret_data'] = "success";

        return $this->respond($data, 200);
    }

    public function serv_payment()

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
        $date = date("Y-m-d H:i:s");
        $serequestModel = new ServiceRequestMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $paymenthistModel = new PaymentHistoryModel();
        $paymenttrackerModel = new PaymentTrackermasterModel();
        $paymentmasterController = new PaymentMasterController;
        if ($this->request->getVar("reason"))  //Cat Payment
        {
            // $track_data['rpt_reference'] = $this->request->getVar("reason");
            $track_data['rpt_updated_by'] = $tokendata['uid'];


            $hist1 = [
                'rph_type' => 0,
                'rph_rq_id' => $this->request->getVar("serm_id"),
                'rph_status' => 1,
                'rph_amount' => $this->request->getVar('total_amount'),
                'rph_created_on' => $date,
                'rph_created_by' => $tokendata['uid'],
                'rph_by_type' => 1,
                'rph_transaction_id' => $this->request->getVar('txnid')
            ];
            $hist2 = [
                'rph_type' => 0,
                'rph_rq_id' => $this->request->getVar("serm_id"),
                'rph_status' => 2,
                'rph_amount' => $this->request->getVar('total_amount'),
                'rph_created_on' => $date,
                'rph_created_by' => $tokendata['uid'],
                'rph_by_type' => 1,
                'rph_transaction_id' => $this->request->getVar('txnid')
            ];

            $s_data = $paymenthistModel->insert($hist1);
            $d_data = $paymenthistModel->insert($hist2);

            $data = [
                'serm_status' => 32,
                'serm_updatedon' => $date,
                'serm_updatedby' => $tokendata['uid']
            ];
            $updt_data = [
                'srh_serm_id' => $this->request->getVar("serm_id"),
                'srh_status_id' => 31,
                'srh_created_on' => $date,
                'srh_created_by' => $tokendata['uid']
            ];
            $servicehistoryModel->insert($updt_data);
            $result = $serequestModel->update($this->request->getVar("serm_id"), $data);
            $updt_data = [
                'srh_serm_id' => $this->request->getVar("serm_id"),
                'srh_status_id' => 32,
                'srh_created_on' => $date,
                'srh_created_by' => $tokendata['uid']
            ];
    
            $servicehistoryModel->insert($updt_data);
    
            $track_data = [
                'rpt_status' => 1,
                'rpt_updated_on' => $date,
                'rpt_updated_by' => $tokendata['uid']
            ];
    
            $paymenttrackerModel->update($this->request->getVar('rpt_id'), $track_data);
        } else {
            $t_details = $this->request->getVar('transaction_details');

            if ($t_details->result == 'payment_successfull') {
                $hist1 = [
                    'rph_type' => 0,
                    'rph_rq_id' => $this->request->getVar("serm_id"),
                    'rph_status' => 1,
                    'rph_amount' => $this->request->getVar('total_amount'),
                    'rph_created_on' => $date,
                    'rph_created_by' => $tokendata['uid'],
                    'rph_transaction_id' => $this->request->getVar('txnid')
                ];

                $s_data = $paymenthistModel->insert($hist1);

                $data = $paymentmasterController->serv_balance_amount($t_details->payment_response);
            } else {
                $hist1 = [
                    'rph_type' => 0,
                    'rph_rq_id' => $this->request->getVar("serm_id"),
                    'rph_status' => 1,
                    'rph_amount' => $this->request->getVar('total_amount'),
                    'rph_created_on' => $date,
                    'rph_created_by' => $tokendata['uid'],
                    'rph_transaction_id' => $this->request->getVar('txnid')
                ];

                $s_data = $paymenthistModel->insert($hist1);
                $data = $paymentmasterController->failed_transaction($t_details->payment_response);
            }
        }

        if ($s_data) {
            $response['ret_data'] = "success";
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No details for this service request';
            return $this->respond($response, 200);
        }
    }
    public function success_serv_payment($input)

    {

        $date = date("Y-m-d H:i:s");
        $serequestModel = new ServiceRequestMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $paymenthistModel = new PaymentHistoryModel();
        $paymenttrackerModel = new PaymentTrackermasterModel();
        $payment_details = $paymenttrackerModel->where('rpt_type', 1)->where('rpt_reqt_id', $input->udf2)->first();

        $data = [
            'serm_status' => 32,
            'serm_updatedon' => $date,
            'serm_updatedby' => $input->udf4
        ];
        $updt_data = [
            'srh_serm_id' => $input->udf2,
            'srh_status_id' => 31,
            'srh_created_on' => $date,
            'srh_created_by' => $input->udf4
        ];
        $servicehistoryModel->insert($updt_data);
        $result = $serequestModel->update($input->udf2, $data);
        $updt_data = [
            'srh_serm_id' => $input->udf2,
            'srh_status_id' => 32,
            'srh_created_on' => $date,
            'srh_created_by' => $input->udf4
        ];

        $servicehistoryModel->insert($updt_data);

        $track_data = [
            'rpt_status' => 1,
            'rpt_updated_on' => $date,
            'rpt_updated_by' => $input->udf4
        ];

        $paymenttrackerModel->update($payment_details['rpt_id'], $track_data);
        return $input;
    }

    public function upload_image($image, $serm_id, $cust_id)

    {
        $date = date("Y-m-d H:i:s");
        $serequestmediaModel = new ServiceRequestMediaModel();
        helper(['form', 'url']);
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
        $serequestmediaModel = new ServiceRequestMediaModel();
        $out_data = [];
        $imageFile = $image;
        foreach ($imageFile as $eachfile) {

            $eachfile->move(ROOTPATH . 'public/uploads/Servicerequest_images');
            $eachfile->move(ROOTPATH . 'public/uploads/Servicerequest_images');
            $infdata = [
                'smedia_sereqid'   => $serm_id,
                'smedia_type' => 0,
                'smedia_url' => 'uploads/Servicerequest_images/' . $eachfile->images->getName(),
                'smedia_createdby' => $cust_id,
                'smedia_createdon' => $date,
                'smedia_updatedon' => $date,
                'smedia_updatedby' => $cust_id,
            ];
            array_push($out_data, $infdata);
        }
        if (sizeof($out_data) > 0) {
            $serequestmediaModel->insertBatch($out_data);
        }

        return ($out_data);
    }

    public function fetch_dashb_details()

    {
        $serviceRequstModel = new ServiceRequestMasterModel();

        $service_open = 0;
        $service_closed = 0;
        $serv_pay_pend = 0;
        $total_serm_hold = 0;
        $pending_service = 0;
        $pend_old = 0;
        $pend_count = 0;
        $serm_data = array();
        $open_ticket_service = array();
        $pending_by_user=array();




        $serm_data = $serviceRequstModel
        ->select('serm_number,
                serm_id,
                sm_id,
                sm_name,
                sm_pk_id,
                serm_createdon,
                serm_status,
                us_id,
                us_firstname')
            ->where('serm_deleteflag', 0)
            ->where('serm_status !=', 0)
            ->join('status_master', 'sm_id = serm_status')
            ->join('users', 'us_id = serm_createdby')
            ->findAll();
        $monthly_data=$this->get_tmonthly_updates($serm_data);
        $monthly_cmpl_data=$this->get_cmonthly_updates($serm_data);

        for ($i = 0; $i < sizeof($serm_data); $i++) {
            if ($serm_data[$i]['serm_status'] == 19 || $serm_data[$i]['serm_status'] == 20) {
                $open_ticket_service[$i] = $serm_data[$i];
                $service_open = $service_open + 1;
            } else if ($serm_data[$i]['serm_status'] == 34 || $serm_data[$i]['serm_status'] == 31 || $serm_data[$i]['serm_status'] == 32) {
                $service_closed = $service_closed + 1;
            } else if ($serm_data[$i]['serm_status'] == 30) {
                $serv_pay_pend = $serv_pay_pend + 1;
            } else if ($serm_data[$i]['serm_status'] == 50) {
                $total_serm_hold = $total_serm_hold + 1;
            } else {
                $pending_service = $pending_service + 1;
                $pending_by_user[$i] = $serm_data[$i];
                if ($pending_by_user[$i]) {
                    $pend_new = $pending_by_user[$i]['us_id'];
                    if ($pend_new == $pend_old) {
                        $pend_user[$i] = [
                            'us_firstname' => $pending_by_user[$i]['us_firstname'],
                            'count' => $pend_count = $pend_count + 1
                        ];
                    } else {
                        $pend_user[$i] = [
                            'us_firstname' => $pending_by_user[$i]['us_firstname'],
                            'count' => $pend_count = $pend_count + 1
                        ];
                    }
                    $pend_old = $pend_new;
                }
            }
        }


        $data = [
            'service_open' => $service_open?$service_open:0,
            'open_ticket_service' => $open_ticket_service?$open_ticket_service:[],
            'service_closed' => $service_closed?$service_closed:0,
            'serm_data' => $serm_data?$serm_data:[],
            'serv_pay_pend' => $serv_pay_pend?$serv_pay_pend:0,
            'total_serm_hold' => $total_serm_hold?$total_serm_hold:0,
            'inprogress_service' => $pending_service?$pending_service:0,
            'pend_old' => $pend_old?$pend_old:0,
            'pend_count' => $pend_count?$pend_count:0,
            'pending_by_user' => $pending_by_user?$pending_by_user:[],
            'monthly_data'=>$monthly_data,
            'monthly_cmpl_data'=>$monthly_cmpl_data
        ];
        return $data;
    }

    public function get_tmonthly_updates($serm_data)
    {
        
        $week_counts = array_fill(0, 7, 0);
        $current_date = new DateTime();
        $current_date->setTime(0, 0);
        $current_week_start = clone $current_date;
        $current_week_start->modify('last Monday');
        for ($i = 0; $i < 7; $i++) {
            $week_start = clone $current_week_start;
            $week_start->modify("-$i week");
            $week_end = clone $week_start;
            $week_end->modify('+6 days');
            foreach ($serm_data as $data) {
                $created_date = new DateTime($data['serm_createdon']);
                if ($created_date >= $week_start && $created_date <= $week_end) {
                    $week_counts[$i]++;
                }
            }
        }
        return $week_counts;
    }
    public function get_cmonthly_updates($sm_data)
    {
        $serm_data=[];
        $j=0;
        for($k=0;$k<sizeof($sm_data);$k++){
            if($sm_data[$k]['serm_status']==32){
                $serm_data[$j]=$sm_data[$k];
                $j++;
            }
        }
        
        $week_counts = array_fill(0, 7, 0);
        $current_date = new DateTime();
        $current_date->setTime(0, 0);
        $current_week_start = clone $current_date;
        $current_week_start->modify('last Monday');
        for ($i = 0; $i < 7; $i++) {
            
            $week_start = clone $current_week_start;
            $week_start->modify("-$i week");
            $week_end = clone $week_start;
            $week_end->modify('+6 days');
            foreach ($serm_data as $data) {
            
                $created_date = new DateTime($data['serm_createdon']);
                if ($created_date >= $week_start && $created_date <= $week_end) {
                    $week_counts[$i]++;
                }
            }
        }
        return $week_counts;
    }
    public function fetch_sr_chathist()

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
        } else if ($tokendata['aud'] == 'user') {
           
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $rules = [
            'serm_id' => 'required',

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $chatdetailsModel = new ServicesChatModel();

        $serm_id = $this->request->getVar("serm_id");
        $c_details = $chatdetailsModel
            ->where('sc_delete_flag', 0)
            ->where('sc_req_id', $serm_id)
            ->join('customer_master', 'cstm_id=sc_customer_id', 'left')
            ->findAll();
            $res=[];
        for($i=0;$i<sizeof($c_details);$i++){
            $det=[];
            if($c_details[$i]['sc_us_type']==3||$c_details[$i]['sc_us_type']==4){
                $det=$custModel->where('cstm_id',$c_details[$i]['sc_staff_id'])->first();
                $c_details[$i]['expert_name']=$det['cstm_name'];
                $c_details[$i]['expert_id']=$det['cstm_id'];
                $c_details[$i]['expert_cash_percent']=$det['cstm_vendor_percent'];
            }else{
                $det=$userModel->where('us_id',$c_details[$i]['sc_staff_id'])->first();
                $c_details[$i]['us_firstname']=$det['us_firstname'];
                $c_details[$i]['us_id']=$det['us_id'];
            }
            
        }

        $response = sizeof($c_details) > 0 ?

            [
                'ret_data' => 'success',
                'chat_details' => $c_details
            ] :
            [
                'ret_data' => 'fail',
                'chat_details' => []
            ];

        return $this->respond($response, 200);
    }
    public function create_service_chat()
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
        } else if ($tokendata['aud'] == 'user') {
            
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $rules = ($tokendata['aud'] == 'user') ?
            [
                'sc_staff_id' => 'required',
                'sc_message_type' => 'required',
                'sc_message' => 'required',
                'sc_customer_id' => 'required',
                'sc_us_type' => 'required',
                'sc_req_id' => 'required',
                'sc_req_type' => 'required'

            ] :
            [
                'sc_customer_id' => 'required',
                'sc_message_type' => 'required',
                'sc_message' => 'required',
                'sc_us_type' => 'required',
                'sc_req_id' => 'required',
                'sc_req_type' => 'required'

            ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());


        $chatdetailsModel = new ServicesChatModel();
        $ChatMasterController = new ChatMasterController;
        $servcierequestModel = new ServiceRequestMasterModel();
        $notificationmasterController = new UsersNotificationController;
        $date = date("Y-m-d H:i:s");
        $c_data = ($tokendata['aud'] == 'user') ?
            [
                'sc_customer_id' =>   $this->request->getVar('sc_customer_id'),
                'sc_staff_id' =>   $this->request->getVar('sc_staff_id'),
                'sc_message_type' =>  $this->request->getVar('sc_message_type'),
                'sc_message' =>  $this->request->getVar('sc_message'),
                'sc_us_type' =>  $this->request->getVar('sc_us_type'),
                'sc_req_type' =>  $this->request->getVar('sc_req_type'),
                'sc_req_id' =>  $this->request->getVar('sc_req_id'),
                'sc_created_on' =>  $date,
                'sc_updated_on' =>  $date,
                'sc_status' =>  0,
            ] :
            [
                'sc_customer_id' =>   $this->request->getVar('sc_customer_id'),
                'sc_message_type' =>  $this->request->getVar('sc_message_type'),
                'sc_message' =>  $this->request->getVar('sc_message'),
                'sc_us_type' =>  $this->request->getVar('sc_us_type'),
                'sc_req_type' =>  $this->request->getVar('sc_req_type'),
                'sc_req_id' =>  $this->request->getVar('sc_req_id'),
                'sc_created_on' =>  $date,
                'sc_updated_on' =>  $date,
                'sc_status' =>  0,
                'sc_staff_id' =>   1,


            ];

        $c_id = $chatdetailsModel->insert($c_data);
        // return $this->respond($c_id);

        $c_hist = $ChatMasterController->get_recent_serv_chat($c_id, $this->request->getVar('sc_req_type'));
        
        $serm_data = $servcierequestModel->where('serm_id', $this->request->getVar('sc_req_id'))->first();
            if ($tokendata['aud'] == 'customer') {
                
                $us_id = $userModel->where('us_delete_flag', 0)->findAll();
                $ntf_data = [];
        
                foreach ($us_id as $eachurl) {
                    $indata = [
                        'id' => $eachurl['us_id'],
                        'headers' => "Service Chat ". $serm_data['serm_number'],
                        'content' => "New Request Created " .$this->request->getVar('sc_message') ,
                        'sourceid' => $tokendata['uid'],
                        'destid' => $eachurl['us_id'],
                        'date' => $date,
                        'nt_request_type' => 0,
                        'nt_type_id' => $serm_data['serm_id'],
                        'nt_type' => 1,
                        'nt_req_number'=>$serm_data['serm_number']
                    ];
                    array_push($ntf_data, $indata);
                }
                $nt_id = $notificationmasterController->create_us_notification($ntf_data);

            }else{
                
                $ntf_data = [
                    'id' => $serm_data['serm_custid'],
                    'headers' => "Service Chat ". $serm_data['serm_number'],
                    'content' => "New Request Created " .$this->request->getVar('sc_message') ,
                    'sourceid' => $tokendata['uid'],
                    'destid' => $serm_data['serm_custid'],
                    'nt_req_number' => $serm_data['serm_number'],
                    'nt_type' => 1,
                    'nt_request_type' => 0,
                    'nt_type_id' => $serm_data['serm_id'],
                    'date' => $date
                ];
                $nt_id = $notificationmasterController->create_cust_notification($ntf_data);
            }

        $response = $c_id ?
            ['ret_data' => 'success', 'chat_data' => $c_hist]
            :
            ['ret_data' => 'fail', 'chat_data' => $c_hist];

        return $this->respond($response);
    }

    public function audio_cUpload($serviceaudio,$base_version) {

        $commonutils = new Commonutils();
        if($base_version==='locale'){
            $serviceaudio->move(ROOTPATH . 'public/uploads/ServiceRequest_audio');
            $audio_url='uploads/ServiceRequest_audio/' . $serviceaudio->getName();
            $data = [
                'ret_data' => "success",
                'path' => $audio_url,
                'audio_data' => $serviceaudio
            ];
        }else{
            if ($serviceaudio) {
                $audio_parts = explode(";base64,", $serviceaudio);
                $audio_type_aux = explode("audio/", $audio_parts[0]);
                $audio_extension = $audio_type_aux[1];
                $audio_name = date("d-m-Y") . "-" . time() . "." . $audio_extension;
                $audio_data = base64_decode($audio_parts[1]);
                $aud_data = $commonutils->image_upload($audio_data, $audio_name, 'Service_request/ServiceRequest_audio', 'audio/' . $audio_extension, false);
                $audio_url = $aud_data ? "Service_request/ServiceRequest_audio/" . $audio_name : "";
                $data = [
                    'ret_data' => "success",
                    'path' => $audio_url,
                    'audio_data' => $aud_data
                ];
                
            }
        }
        
        return $data;
    }

    public function document_cUpload($servicedocuemnt,$base_version)
    {
        $commonutils = new Commonutils();
        if($base_version==='locale'){
            
            $servicedocuemnt->move(ROOTPATH . 'public/uploads/ServiceRequest_Documents');
            $document_url='uploads/ServiceRequest_Documents/' . $servicedocuemnt->getName();
            
        }else{
            if ($servicedocuemnt) {
                $document_parts = explode(";base64,", $servicedocuemnt);
                $mime_type = $document_parts[0];
                $document_type_aux = explode("/", $mime_type);
                $document_extension = $document_type_aux[1];
                $document_name = date("d-m-Y") . "-" . time() . "." . $document_extension;
                $document_data = base64_decode($document_parts[1]);
                $document = $commonutils->image_upload($document_data, $document_name, 'Service_request/ServiceRequest_Documents', $mime_type, false);
                $document_url = $document ? "Service_request/ServiceRequest_Documents/" . $document_name : "";
               
                
               
            }

        }
        $data = [
            'ret_data' => "success",
            'path' => $document_url,
            'document_data' => $document,
            'base_version' => $base_version
        ];
        return $data;
    }
    
}
