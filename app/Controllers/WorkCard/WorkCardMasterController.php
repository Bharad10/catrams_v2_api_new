<?php

namespace App\Controllers\WorkCard;

use App\Controllers\Quote\QuoteMasterController;
use App\Controllers\ServiceRequest\ServiceRequestMasterController;
use App\Controllers\System\UsersNotificationController;
use App\Models\Approval\ApprovalmasterModel;
use App\Models\Coupon\CouponTrackerModel;
use CodeIgniter\RESTful\ResourceController;
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
use App\Models\System\ExpenseTrackerModel;
use App\Models\System\NotificationmasterModel;
use App\Models\System\ToolTrackerModel;
use App\Models\ToolRequest\ToolDetailsModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use App\Models\User\UsersModel;
use App\Models\Vendor\VendorModel;
use CodeIgniter\Validation\Validation as ValidationValidation;
use Config\Commonutils;
use Config\Validation;


class WorkCardMasterController extends ResourceController
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
        $CustomerMasterModel = new CustomerMasterModel();
        $serequestModel = new ServiceRequestMasterModel();
        $UsersModel = new UsersModel();
        $result = $serequestModel
            ->select('serm_id
                ,serm_number,
                serm_reopen_flag,
                serm_status,
                sm_name,
                sm_id,
                sm_code,
                us_firstname,
                serm_updatedon,
                cstm_name,
                cstm_vendor_flag,
                cstm_vendor_percent,
                cstm_id,
                custveh_regnumber,
                custveh_vinnumber,
                serm_hold_flag')
            ->where('serm_vendor_flag', 0)
            ->whereIn('serm_status', [25, 26, 27, 28, 29, 50, 56])
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('customer_vehicle', 'custveh_id = serm_vehid')
            ->join('cat_vehicle_data', 'id=custveh_veh_id')
            ->join('users', 'us_id=serm_assigne')
            ->join('status_master', 'sm_id=serm_status')
            ->orderBy('serm_id', 'desc')
            ->findAll();

        $total_op_work = 0;

        foreach ($result as $eachwork) {

            $total_op_work = (($eachwork['sm_id']) == 25) ? $total_op_work + 1 : $total_op_work;
        }

        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            $response['open_works'] = $total_op_work;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No Pending service request';
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
        $servicerequestModel = new ServiceRequestMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $quotemasterModel = new QuoteMasterModel();
        $servicepackageModel = new ServiceRequestPackageModel();
        $workcardItemsModel = new ServiceRequestItemsModel();
        $id = $this->request->getVar('serm_id');
        $next_status = 25;
        $updtdata = [
            'serm_status' => $next_status
        ];
        $insertdata = [
            'srh_status_id' => $next_status,
            'srh_serm_id' => $this->request->getVar('serm_id')
        ];
        $results1 = $servicerequestModel->update($this->db->escapeString($id), $updtdata);
        $toolhistid1 = $servicehistoryModel->insert($insertdata); {
            $result = $servicerequestModel->where('serm_deleteflag', 0)->where('serm_id', base64_decode($id))->join('quote_master', 'qtm_serm_id=serm_id', 'left')
                ->join('customer_master', 'cstm_id=serm_custid')
                ->join('customer_vehicle', 'custveh_id =serm_vehid')
                ->join('cat_vehicle_data', 'id=customer_vehicle.custveh_veh_id')
                ->first();

            $services = $workcardItemsModel->where('sitem_deleteflag', 0)
                ->where('sitem_serid', base64_decode($id))
                ->join('service_request_package', 'servpack_id=sitem_itemid')
                ->findAll();
            $packages = $servicepackageModel->where('servpack_delete_flag', 0)->findAll();
            if ($result) {
                $response['ret_data'] = "success";
                $response['result'] = $result;
                $response['services'] = $services;
                $response['Packages'] = $packages;
                return $this->respond($response, 200);
            } else {
                $response['ret_data'] = "fail";
                $response['Message'] = 'No details for this service request';
                return $this->respond($response, 200);
            }
        }
    }


    public function getworkcard_Details()
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
        $servicerequestModel = new ServiceRequestMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $quotemasterModel = new QuoteMasterModel();
        $workcardItemsModel = new ServiceRequestItemsModel();
        $servicepackageModel = new ServiceRequestPackageModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $vendorMasterModel = new VendorModel();
        $approvalmasterModel = new ApprovalmasterModel();
        $rules = [
            'serm_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());;
        $result = $servicerequestModel
            ->where('serm_deleteflag', 0)
            ->where('serm_id', base64_decode($this->request->getVar('serm_id')))
            ->join('quote_master', 'qtm_serm_id=serm_id', 'left')
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=customer_vehicle.custveh_veh_id')
            ->first();

        $services = [];
        if ($result['serm_vendor_flag'] == 1) {
            $services = $workcardItemsModel->where('sitem_serid', $result['serm_id'])
                ->where('sitem_deleteflag', 0)
                ->findAll();
            $vend_Det = $vendorMasterModel->where('vm_serm_id', $result['serm_id'])
                ->orderBy('vm_id', 'desc')
                ->first();

            $outdata = $services;
        } else {
            $services = $workcardItemsModel->where('sitem_serid', $result['serm_id'])
                ->where('sitem_deleteflag', 0)
                ->findAll();
            $outdata = [];
            for ($i = 0; $i < sizeof($services); $i++) {
                if ($services[$i]['sitem_type'] == 0) {
                    $data = $servicepackageModel->where('servpack_id', $services[$i]['sitem_itemid'])->first();
                } else {
                    $data = $ToolDetailsModel->where('tool_id', $services[$i]['sitem_itemid'])->first();
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



        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            $response['services'] = $outdata;
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
        $UserModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $workcardModel = new ServiceRequestMasterModel();
        $workcardItemsModel = new ServiceRequestItemsModel();
        $services = $this->request->getVar("services");
        $heddata = $this->request->headers();
        $date = date("Y-m-d H:i:s");
        $inData = [
            'serm_cost' => $this->request->getVar('cost'),
            'serm_createdby' => 1,
            'serm_status' => 19,
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid']
        ];

        $result = $workcardModel->update($this->request->getVar("services"), $inData);
        $hist = [
            'srh_serm_id' => $result,
            'srh_status_id' => 19
        ];
        $result_hist1 = $servicehistoryModel->insert($hist);
        $inser_hist = [
            'srh_status_id' => 20,
            'srh_serm_id' => $result,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];
        $result_hist2 = $servicehistoryModel->insert($inser_hist);
        if ($result) {
            if (count($services) > 0) {

                $in_data = array();
                for ($i = 0; $i < count($services); $i++) {

                    $infdata = [
                        'sitem_itemid'   => $services[$i]->ser_id,
                        'sitem_serid'   => $result,
                        'sitem_type' => 0,
                        'sitem_cost' => $services[$i]->cost,
                        'sitem_updatedby' =>  $tokendata['uid'],
                        'sitem_updatedon' => $date
                    ];
                    array_push($in_data, $infdata);
                }
                $ret = $workcardItemsModel->insertBatch($in_data);
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


    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }

    public function servicestatus_update()
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
        $heddata = $this->request->headers();
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
            $target_cust = $custmodel->where('cstm_id', $serm_det['serm_custid'])->first();
            $player_id = [];
            $custhead = "Work Card Updated!!!";
            $custcontent = "Work card has been updated for - " . $serm_det['serm_number'] . ". Tap to see";

            array_push($player_id, $target_cust['fcm_token_mobile']);
            if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);
            if ($ret_res) {
                $notif_data = [
                    'nt_sourceid' => $tokendata['uid'],
                    'nt_destid' => $serm_det['serm_custid'],
                    'nt_req_number' => $serm_det['serm_number'],
                    'nt_request_type' => 0,
                    'nt_sourcetype' => 1,
                    'nt_type' => 0,
                    'nt_type_id' => $serm_det['serm_id'],
                    'nt_header' => $custhead,
                    'nt_content' => $custcontent,
                    'nt_created_on' => $date
                ];
                $notificationmasterModel->insert($notif_data);
            }
        } else if ($this->request->getVar("type") == '1') {

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

                $inData = [
                    'serm_id' => $this->request->getVar("serm_id"),
                    'serm_status'   => 28,
                    'serm_updatedon' => $date,
                    'serm_updatedby' => $tokendata['uid']

                ];
                $inser_hist = [
                    'srh_status_id' => 28,
                    'srh_serm_id' => $this->request->getVar("serm_id"),
                    'srh_created_on' => $date,
                    'srh_created_by' => $tokendata['uid']
                ];
                $items = $workcardItemsModel->where('sitem_serid', $this->request->getVar("serm_id"))->findAll();
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
                $result = $workcardModel->update($this->request->getVar("serm_id"), $inData);

                $serm_det = $workcardModel->select('serm_custid,serm_number')->where('serm_id', $this->request->getVar("serm_id"))->first();
                $target_cust = $custmodel->where('cstm_id', $serm_det['serm_custid'])->first();
                $player_id = [];
                $custhead = "Work Started";
                $custcontent = "Work Started against " . $serm_det['serm_number'] . ". Tap to see";

                array_push($player_id, $target_cust['fcm_token_mobile']);
                if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);
            }
        }
        if ($result) {
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

    public function hold_workcard()
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
        $approvalMasterModel = new ApprovalmasterModel();
        $notificationmasterModel = new NotificationmasterModel();
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $notificationmastercontroller = new UsersNotificationController;
        $date = date("Y-m-d H:i:s");

        if ($this->request->getVar("service")) {
            $s_data = [
                'sitem_hold_flag' => 1,
                'sitem_updatedby' =>  $tokendata['uid'],
                'sitem_updatedon' => $date
            ];

            if ($this->request->getVar("sitem_hold_reason")) {
                $s_data['sitem_hold_reason'] = $this->request->getVar("sitem_hold_reason");
            }
            $wk_Data = $workcardItemsModel->update($this->request->getVar("service"), $s_data);
            if ($this->request->getVar("am_id")) {
                $app_data = [
                    'am_status' => 1,
                    'am_updatedby' => $tokendata['uid'],
                    'am_updatedon' => $date,
                ];
                $a_data = $approvalMasterModel->update($this->request->getVar("am_id"), $app_data);
            } else {
                $app_data = [
                    'am_reqid' => $this->request->getVar("service"),
                    'am_status' => 1,
                    'am_type' => 5,
                    'am_updatedby' => $tokendata['uid'],
                    'am_createdon' => $date,
                    'am_updatedon' => $date,
                    'am_createdby' => $tokendata['uid']
                ];
                $a_data = $approvalMasterModel->insert($app_data);
            }
        } else {
            if ($this->request->getVar("am_id")) {
                $app_data = [
                    'am_status' => 1,
                    'am_updatedby' => $tokendata['uid'],
                    'am_updatedon' => $date,
                ];
                $a_data = $approvalMasterModel->update($this->request->getVar("am_id"), $app_data);
            } else {
                $app_data = [
                    'am_reqid' => $this->request->getVar("serm_id"),
                    'am_status' => 1,
                    'am_type' => 4,
                    'am_updatedby' => $tokendata['uid'],
                    'am_createdon' => $date,
                    'am_updatedon' => $date,
                    'am_createdby' => $tokendata['uid']
                ];
                $a_data = $approvalMasterModel->insert($app_data);
            }
            $serm_id = $this->request->getVar("serm_id");
            $data = [
                'serm_hold_flag' => 1,
                'serm_status' => 50,
                'serm_hold_reason' => $this->request->getVar("sitem_hold_reason"),
                'serm_updatedby' => $tokendata['uid'],
                'serm_updatedon' => $date

            ];
            $wk_Data = $workcardModel->update($serm_id, $data);
            $hist = [
                'srh_serm_id' => $serm_id,
                'srh_status_id' => 50,
                'srh_created_on' => $date,
                'srh_created_by' => $tokendata['uid']
            ];


            $serv_hist = $servicehistoryModel->insert($hist);
        }


        $serm_data = $workcardModel->where('serm_id', $this->request->getVar('serm_id'))->first();
        $ntf_data = [];
        $us_id = $userModel
            ->where('us_delete_flag', 0)
            ->findAll();
        foreach ($us_id as $eachurl) {

            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "Hold Request Accepted",
                'content' => "Hold Request raised for  " . $serm_data['serm_number'] . "has been accepted!!",
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_data['serm_id'],
                'nt_type' => 0,
                'nt_req_number' => $this->request->getVar('serm_id')
            ];

            array_push($ntf_data, $indata);
        }

        $nt_id = $notificationmastercontroller->create_us_notification($ntf_data);

        $ntc_data = [
            'id' => $serm_data['serm_custid'],
            'headers' => "Work Card Holded",
            'content' => "Request  Holded For" . $serm_data['serm_number'],
            'sourceid' => $tokendata['uid'],
            'destid' => $serm_data['serm_custid'],
            'date' => $date,
            'nt_type' => 0,
            'nt_request_type' => 0,
            'nt_type_id' => $serm_data['serm_id'],
            'nt_req_number' => $serm_data['serm_number']
        ];
        $nt_id = $notificationmastercontroller->create_cust_notification($ntc_data);

        if ($wk_Data) {
            $response = [
                'ret_data' => 'success'
            ];
        }
        return $this->respond($response, 200);
    }

    public function workcard_unhold()
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
        $workcardModel = new ServiceRequestMasterModel();
        $workcardItemsModel = new ServiceRequestItemsModel();
        $notificationmasterModel = new NotificationmasterModel();
        $approvalMasterModel = new ApprovalmasterModel();
        $serm_id = $this->request->getVar("serm_id");
        $date = date("Y-m-d H:i:s");
        if ($this->request->getVar("service")) {

            $s_data = [
                'sitem_hold_flag' => 0,
                'sitem_updatedby' =>  $tokendata['uid'],
                'sitem_updatedon' => $date
            ];
            $app_data = [
                'am_status' => 1,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
            ];
            $a_data = $approvalMasterModel->update($this->request->getVar("am_id"), $app_data);

            $up_data = $workcardItemsModel->update($this->request->getVar("service"), $s_data);
            $wkitems_data = $workcardItemsModel->where('sitem_serid', $serm_id)->findAll();
            for ($i = 0; $i < sizeof($wkitems_data); $i++) {
                if ($wkitems_data[$i]['sitem_type'] == 0) {
                    if ($wkitems_data[$i]['sitem_hold_flag'] == 1) {
                        $data = [
                            'serm_hold_flag' => 1,
                            'serm_updatedon' => $date,
                            'serm_updatedby' => $tokendata['uid']
                        ];
                        $wk_Data = $workcardModel->update($serm_id, $data);
                        $response = [
                            'ret_data' => 'success'
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $data = [
                            'serm_hold_flag' => 0,
                            'serm_updatedon' => $date,
                            'serm_updatedby' => $tokendata['uid']
                        ];
                        $wk_Data = $workcardModel->update($serm_id, $data);
                    }
                }
            }
        } else if ($this->request->getVar("flag")) {
            $count = 0;
            $items_data = $workcardItemsModel->where('sitem_serid', $serm_id)->findAll();

            $s_data = [
                'sitem_hold_flag' => 0,
                'sitem_updatedby' =>  $tokendata['uid'],
                'sitem_updatedon' => $date
            ];


            for ($i = 0; $i < sizeof($items_data); $i++) {
                if ($items_data[$i]['sitem_type'] == 0) {
                    $workcardItemsModel->update($items_data[$i]['sitem_serid'], $s_data);

                    if ($items_data[$i]['sitem_active_flag'] != 0) {
                        $count = $count + 1;
                    }
                }
            }
            if ($count > 0) {

                $data = [
                    'serm_hold_flag' => 0,
                    'serm_status' => 28,
                    'serm_updatedon' => $date,
                    'serm_updatedby' => $tokendata['uid']
                ];
                $hist = [
                    'srh_serm_id' => $serm_id,
                    'srh_status_id' => 28,
                    'srh_created_on' => $date,
                    'srh_created_by' => $tokendata['uid']
                ];


                $serv_hist = $servicehistoryModel->insert($hist);
            } else {
                $data = [
                    'serm_hold_flag' => 0,
                    'serm_updatedon' => $date,
                    'serm_updatedby' => $tokendata['uid']
                ];
            }
            $wk_Data = $workcardModel->update($serm_id, $data);
            if ($this->request->getVar("am_id")) {
                $app_data = [
                    'am_status' => 1,
                    'am_updatedby' => $tokendata['uid'],
                    'am_updatedby' => $tokendata['uid'],
                    'am_updatedon' => $date,
                ];
                $a_data = $approvalMasterModel->update($this->request->getVar("am_id"), $app_data);
            }
        }

        $response = [
            'ret_data' => 'success'
        ];

        return $this->respond($response, 200);
    }


    public function delete_service()
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
        $workcardModel = new ServiceRequestMasterModel();
        $workcardItemsModel = new ServiceRequestItemsModel();
        $notificationmasterModel = new NotificationmasterModel();
        $date = date("Y-m-d H:i:s");
        $data = [
            'sitem_deleteflag' => 1,
            'sitem_updatedby' =>  $tokendata['uid'],
            'sitem_updatedon' => $date
        ];
        $workcardItemsModel->update($this->request->getVar("sitem_id"), $data);

        $response = [
            'ret_data' => 'success'
        ];
        return $this->respond($response, 200);
    }

    public function add_service()

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


        $serequestitemsModel = new ServiceRequestItemsModel();
        $quotemasterModel = new QuoteMasterModel();
        $quoteitemsModel = new QuoteItemsModel();
        $seqModel = new SequenceGeneratorModel();
        $sr_id = $this->request->getVar("serm_id");
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $servicequestModel = new ServiceRequestMasterModel();
        $notificationmasterModel = new NotificationmasterModel();
        $custModel = new CustomerMasterModel();
        $services = $this->request->getVar("services");
        $date = date("Y-m-d H:i:s");

        if (count($services) > 0) {
            $in_data = array();
            for ($i = 0; $i < count($services); $i++) {
                $infdata = [
                    'sitem_itemid'   => $services[$i]->servpack_id,
                    'sitem_serid'   => $this->request->getVar("serm_id"),
                    'sitem_active_flag' => 2,
                    'sitem_status_flag' => 0,
                    'sitem_cost' => $services[$i]->servpack_cost,
                    'sitem_createdon' => $date,
                    'sitem_createdby' =>  $tokendata['uid'],
                    'sitem_updatedby' =>  $tokendata['uid'],
                    'sitem_updatedon' => $date

                ];
                array_push($in_data, $infdata);
            }
            $ret = $serequestitemsModel->insertBatch($in_data);
        }

        $serm_det = $servicequestModel->where('serm_id', $this->request->getVar("serm_id"))->first();
        $target_cust = $custModel->where('cstm_id', $serm_det['serm_custid'])->first();
        $player_id = [];
        $custhead = "New Service Created";
        $custcontent = "New Service created . Tap to see";

        array_push($player_id, $target_cust['fcm_token_mobile']);
        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

        if ($ret_res) {
            $notif_data = [
                'nt_sourceid' => $tokendata['uid'],
                'nt_destid' => $serm_det['serm_custid'],
                'nt_sourcetype' => 1,
                'nt_header' => $custhead,
                'nt_content' => $custcontent,
                'nt_created_on' => $date
            ];
            $notificationmasterModel->insert($notif_data);
        }
        if ($ret) {

            $response['ret_data'] = "success";
            $response['result'] = $ret;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'Cannot Update';
            return $this->respond($response, 200);
        }
    }

    public function holdreq_by_cust()
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
            'serm_id' => 'required',
            'am_reason' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");

        $paymentTrackerModel = new PaymentTrackermasterModel();
        $approvalMasterModel = new ApprovalmasterModel();
        $servicerequestModel = new ServiceRequestMasterModel();
        $notificationmastercontroller = new UsersNotificationController;
        $servicerequestMasterController = new ServiceRequestMasterController;
        $hold_criteria = [];
        if ($this->request->getVar('am_id')) {

            $base_version = $this->request->getVar('base_version');
            $aud_data = false;
            if ($this->request->getFile('audio') || $this->request->getvar('audio')) {
                $aud_data = $base_version === 'local' ?
                    $servicerequestMasterController->audio_cUpload($this->request->getFile('audio'), $base_version) :
                    $servicerequestMasterController->audio_cUpload($this->request->getvar('audio'), $base_version);
                $hold_criteria = ['am_url' => $aud_data['path']];
            }

            $hold_criteria = [

                'am_reason' => $this->request->getVar('am_reason'),
                'am_reqid' => $this->request->getVar('serm_id'),
                'am_type' => 4,
                'am_requestedby' => $tokendata['uid'],
                'am_status' => 0,
                'am_createdby' => 0,
                'am_updatedon' => $date,
            ];

            $am_id = $approvalMasterModel->update($this->request->getVar('am_id'), $hold_criteria);
        } else {
            $base_version = $this->request->getVar('base_version');
            if ($this->request->getFile('audio') || $this->request->getvar('audio')) {
                $aud_data = $base_version === 'local' ?
                    $servicerequestMasterController->audio_cUpload($this->request->getFile('audio'), $base_version) :
                    $servicerequestMasterController->audio_cUpload($this->request->getvar('audio'), $base_version);
                $hold_criteria = ['am_url' => $aud_data['path']];
            }
            $hold_criteria = [
                'am_url' => $aud_data['path'],
                'am_reason' => $this->request->getVar('am_reason'),
                'am_reqid' => $this->request->getVar('serm_id'),
                'am_type' => 4,
                'am_requestedby' => $tokendata['uid'],
                'am_status' => 0,
                'am_updatedon' => $date,
                'am_createdon' => $date,
            ];

            $am_id = $approvalMasterModel->insert($hold_criteria);
        }



        $serm_data = $servicerequestModel->where('serm_id', $this->request->getVar('serm_id'))->first();
        $ntf_data = [];
        $us_id = $userModel
            ->where('us_delete_flag', 0)
            ->findAll();

        foreach ($us_id as $eachurl) {

            $indata =
                [
                    'id' => $eachurl['us_id'],
                    'headers' => "Workcard Hold Request ",
                    'content' => "Hold Request raised by Customer for  " . $serm_data['serm_number'],
                    'sourceid' => $tokendata['uid'],
                    'destid' => $eachurl['us_id'],
                    'date' => $date,
                    'nt_type' => 0,
                    'nt_request_type' => 0,
                    'nt_type_id' => $serm_data['serm_id'],
                    'nt_req_number' => $serm_data['serm_number']
                ];

            array_push($ntf_data, $indata);
        }

        $nt_id = $notificationmastercontroller->create_us_notification($ntf_data);

        if ($am_id) {
            $response = [
                'ret_data' => 'success',
                'am_id' => $am_id
            ];
        } else {
            $response = [
                'ret_data' => 'fail'
            ];
        }
        return $this->respond($response, 200);
    }

    public function holdjob_by_cust()
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
        } else if ($tokendata['aud'] == 'user') {
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $rules = [
            'serm_id' => 'required',
            'services' => 'required'

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $paymentTrackerModel = new PaymentTrackermasterModel();
        $notificationmastercontroller = new UsersNotificationController;
        $approvalMasterModel = new ApprovalmasterModel();
        $workcardModel = new ServiceRequestMasterModel();
        $date = date("Y-m-d H:i:s");
        $services = $this->request->getVar('services');
        $infdata = [
            'am_referenceid'   => $services->servpack_id,
            'am_reqid'   => $services->sitem_id,
            'am_type' => 6,
            'am_reason' => $services->am_reason,
            'am_requestedby' => $tokendata['uid'],
            'am_status' => 0,
            'am_updatedon' => $date,
            'am_createdon' => $date,
        ];
        array_push($in_data, $infdata);

        $ret = $approvalMasterModel->insert($in_data);

        $serm_data = $workcardModel->where('serm_id', $this->request->getVar('serm_id'))->first();
        $ntf_data = [];
        $us_id = $userModel
            ->whereIn('us_role_id', [1, 4])
            ->findAll();
        foreach ($us_id as $eachurl) {

            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "Service Hold Request",
                'content' => "Hold Request has been raised for  " . $serm_data['serm_number'] . "by Customer",
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_type' => 0,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_data['serm_id'],
                'nt_req_number' => $serm_data['serm_number']
            ];

            array_push($ntf_data, $indata);
        }

        $nt_id = $notificationmastercontroller->create_us_notification($ntf_data);


        if ($ret) {
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

    public function unholdreq_by_cust()
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
        $rules = [
            'serm_id' => 'required',

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());


        $paymentTrackerModel = new PaymentTrackermasterModel();
        $servicerequestModel = new ServiceRequestMasterModel();
        $notificationmastercontroller = new UsersNotificationController;
        $date = date("Y-m-d H:i:s");

        $approvalMasterModel = new ApprovalmasterModel();
        if ($this->request->getVar("am_id")) {
            $hold_criteria = [
                'am_type' => 6,
                'am_requestedby' => $tokendata['uid'],
                'am_status' => 0,
                'am_updatedon' => $date,
            ];
            $am_id = $approvalMasterModel->update($this->request->getVar('am_id'), $hold_criteria);
        } else {
            $hold_criteria = [
                'am_type' => 6,
                'am_requestedby' => $tokendata['uid'],
                'am_status' => 0,
                'am_createdon' => $date,
                'am_updatedon' => $date,
            ];
            $am_id = $approvalMasterModel->insert($hold_criteria);
        }
        $serm_data = $servicerequestModel->where('serm_id', $this->request->getVar('serm_id'))->first();
        $ntf_data = [];
        $us_id = $userModel
            ->where('us_delete_flag', 0)
            ->findAll();

        foreach ($us_id as $eachurl) {

            $indata =
                [
                    'id' => $eachurl['us_id'],
                    'headers' => "Workcard Un-hold Request ",
                    'content' => "Un-Hold Request raised by Customer for  " . $serm_data['serm_number'],
                    'sourceid' => $tokendata['uid'],
                    'destid' => $eachurl['us_id'],
                    'date' => $date,
                    'nt_type' => 0,
                    'nt_request_type' => 0,
                    'nt_type_id' => $serm_data['serm_id'],
                    'nt_req_number' => $serm_data['serm_number']
                ];

            array_push($ntf_data, $indata);
        }

        $nt_id = $notificationmastercontroller->create_us_notification($ntf_data);
        if ($am_id) {
            $response = [
                'ret_data' => 'success',
                'am_id' => $am_id
            ];
        } else {
            $response = [
                'ret_data' => 'fail'
            ];
        }
        return $this->respond($response, 200);
    }

    public function unholdjob_by_cust()
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
            'services' => 'required'

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $notificationmastercontroller = new UsersNotificationController;
        $approvalMasterModel = new ApprovalmasterModel();
        $workcardModel = new ServiceRequestMasterModel();
        $services = $this->request->getVar('services');
        if (count($services) > 0) {
            // $in_data = array();
            for ($i = 0; $i < count($services); $i++) {
                $infdata[$i] = [
                    'am_type' => 7,
                    'am_requestedby' => $tokendata['uid'],
                    'am_status' => 0,
                    'am_updatedon' => $date,
                ];
                $ret = $approvalMasterModel->update($services[$i]->am_id, $infdata[$i]);
            }
        }

        $serm_data = $workcardModel->where('serm_id', $this->request->getVar('serm_id'))->first();
        $ntf_data = [];
        $us_id = $userModel
            ->whereIn('us_role_id', [1, 4])
            ->findAll();
        foreach ($us_id as $eachurl) {

            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "Service Un-hold Request",
                'content' => "Un-hold Request has been raised for  " . $serm_data['serm_number'] . "by Customer",
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_type' => 0,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_data['serm_id'],
                'nt_req_number' => $serm_data['serm_number']
            ];

            array_push($ntf_data, $indata);
        }

        $nt_id = $notificationmastercontroller->create_us_notification($ntf_data);
        if ($ret) {
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

    public function getwork_by_role()
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
            ->where('serm_vendor_flag', 0)
            ->where('serm_assigne', $tokendata['uid'])
            ->whereIn('serm_status', [25, 26, 27, 28, 29, 50])
            ->join('customer_master', 'cstm_id=serm_custid', 'left')
            ->join('users', 'us_id=serm_createdby', 'left')
            ->join('status_master', 'sm_id=serm_status', 'left')
            ->orderBy('serm_id', 'desc')
            ->findAll();

        $total_op_work = 0;

        foreach ($result as $eachwork) {

            $total_op_work = (($eachwork['sm_id']) == 25) ? $total_op_work + 1 : $total_op_work;
        }

        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            $response['open_works'] = $total_op_work;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No Pending service request';
            return $this->respond($response, 200);
        }
    }

    public function holdreq_by_user()
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

        $rules = [
            'serm_id' => 'required',
            'am_reason' => 'required'
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $approvalMasterModel = new ApprovalmasterModel();
        if ($this->request->getVar('am_id')) {
            $hold_criteria = [
                'am_reason' => $this->request->getVar('am_reason'),
                'am_reqid' => $this->request->getVar('serm_id'),
                'am_type' => 4,
                'am_createdby' => $tokendata['uid'],
                'am_status' => 0,
                'am_requestedby' => 0,
                'am_updatedon' => $date,
                'am_updatedby' => $tokendata['uid'],
            ];
            $am_id = $approvalMasterModel->update($this->request->getVar('am_id'), $hold_criteria);
        } else {
            $hold_criteria = [
                'am_reason' => $this->request->getVar('am_reason'),
                'am_reqid' => $this->request->getVar('serm_id'),
                'am_type' => 4,
                'am_createdby' => $tokendata['uid'],
                'am_status' => 0,
                'am_createdon' => $date,
                'am_updatedon' => $date,
                'am_updatedby' => $tokendata['uid'],
            ];
            $am_id = $approvalMasterModel->insert($hold_criteria);
        }

        if ($am_id) {
            $response = [
                'ret_data' => 'success',
                'am_id' => $am_id
            ];
        } else {
            $response = [
                'ret_data' => 'fail'
            ];
        }
        return $this->respond($response, 200);
    }

    public function unholdreq_by_user()
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

        $date = date("Y-m-d H:i:s");
        $paymentTrackerModel = new PaymentTrackermasterModel();


        $approvalMasterModel = new ApprovalmasterModel();
        if ($this->request->getVar("am_id")) {
            $hold_criteria = [
                'am_type' => 6,
                'am_createdby' => $tokendata['uid'],
                'am_status' => 0,
                'am_updatedon' => $date,
                'am_updatedby' => $tokendata['uid'],
            ];
            $am_id = $approvalMasterModel->update($this->request->getVar('am_id'), $hold_criteria);
        } else {
            $hold_criteria = [
                'am_type' => 6,
                'am_createdby' => $tokendata['uid'],
                'am_status' => 0,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
                'am_createdon' => $date,
            ];
            $am_id = $approvalMasterModel->insert($hold_criteria);
        }

        if ($am_id) {
            $response = [
                'ret_data' => 'success',
                'am_id' => $am_id
            ];
        } else {
            $response = [
                'ret_data' => 'fail'
            ];
        }
        return $this->respond($response, 200);
    }

    public function holdjob_by_user()
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
            'services' => 'required'

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $approvalMasterModel = new ApprovalmasterModel();
        $servicepackageModel = new ServiceRequestPackageModel();
        $notificationmastercontroller = new UsersNotificationController;
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $services = $this->request->getVar('services');
        if ($this->request->getVar('am_id')) {
            $infdata = [
                'am_referenceid'   => $services->servpack_id,
                'am_reqid'   => $services->sitem_id,
                'am_type' => 5,
                'am_reason' => $services->am_reason,
                'am_requestedby' => 0,
                'am_status' => 0,
                'am_createdby' =>  $tokendata['uid'],
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
            ];
            $ret = $approvalMasterModel->update($this->request->getVar('am_id'), $infdata);
        } else {
            $infdata = [
                'am_referenceid'   => $services->servpack_id,
                'am_reqid'   => $services->sitem_id,
                'am_type' => 5,
                'am_reason' => $services->am_reason,
                'am_requestedby' => 0,
                'am_status' => 0,
                'am_createdby' =>  $tokendata['uid'],
                'am_createdon' => $date,
                'am_updatedon' => $date,
                'am_updatedby' => $tokendata['uid'],
            ];
            $ret = $approvalMasterModel->insert($infdata);
        }
        $serm_data = $servicerequestMasterModel->where('serm_id', $this->request->getVar('serm_id'))->first();



        $us_id = $userModel
            ->where('us_delete_flag', 0)
            ->whereIn('us_role_id', [1, 4])
            ->findAll();
        $ntf_data = [];

        foreach ($us_id as $eachurl) {

            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "Service Hold Request",
                'content' => "Hold Request has been raised for  " . $serm_data['serm_number'],
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_type' => 0,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_data['serm_id'],
                'nt_req_number' => $serm_data['serm_number']
            ];

            array_push($ntf_data, $indata);
        }

        $nt_id = $notificationmastercontroller->create_us_notification($ntf_data);
        if ($ret) {
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

    public function unholdjob_by_user()
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
        $rules = [
            'serm_id' => 'required',
            'am_id'   =>  'required'

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $approvalMasterModel = new ApprovalmasterModel();
        $servicepackageModel = new ServiceRequestPackageModel();
        $notificationmastercontroller = new UsersNotificationController;
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $infdata = [
            'am_type' => 7,
            'am_status' => 0,
            'am_updatedby' => $tokendata['uid'],
            'am_updatedon' => $date,
        ];
        $ret = $approvalMasterModel->update($this->request->getVar('am_id'), $infdata);

        $serm_data = $servicerequestMasterModel->where('serm_id', $this->request->getVar('serm_id'))->first();
        $us_id = $userModel
            ->where('us_delete_flag', 0)
            ->whereIn('us_role_id', [1, 4])
            ->findAll();
        $ntf_data = [];

        foreach ($us_id as $eachurl) {

            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "Service Hold Request",
                'content' => "Hold Request has been raised for  " . $serm_data['serm_number'],
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_type' => 0,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_data['serm_id'],
                'nt_req_number' => $serm_data['serm_number']
            ];

            array_push($ntf_data, $indata);
        }

        $nt_id = $notificationmastercontroller->create_us_notification($ntf_data);
        if ($ret) {
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

    public function newservice_bycust()
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
        $serequestitemsModel = new ServiceRequestItemsModel();
        $quotemasterModel = new QuoteMasterModel();
        $quoteitemsModel = new QuoteItemsModel();
        $seqModel = new SequenceGeneratorModel();
        $sr_id = $this->request->getVar("serm_id");
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $servicequestModel = new ServiceRequestMasterModel();
        $notificationmasterModel = new NotificationmasterModel();
        $custModel = new CustomerMasterModel();
        $paymenthistModel = new PaymentHistoryModel();
        $paymenttrackerModel = new PaymentTrackermasterModel();
        $notificationmastercontroller = new UsersNotificationController;
        $services = $this->request->getVar("services");
        $this->request->getVar("serm_id");
        $date = date("Y-m-d H:i:s");

        if ($this->request->getVar("flag")) {
            $infdata = [
                'sitem_active_flag' => 3,
                'sitem_updatedby' => $tokendata['uid'],
                'sitem_updatedon' => $date,
            ];
        } else {
            $infdata = [
                'sitem_active_flag' => 1,
                'sitem_updatedby' => $tokendata['uid'],
                'sitem_updatedon' => $date,
            ];
            $mast_d = [
                'serm_cost' => $this->request->getVar("serm_cost"),
                'serm_updatedon' => $date,
                'serm_updatedby' => $tokendata['uid']
            ];

            $servicequestModel->update($this->request->getVar("serm_id"), $mast_d);
            $track_data = [
                'rpt_amount' => $this->request->getVar('serm_cost'),
                'rpt_updated_on' => $date,
                'rpt_updated_by' => $tokendata['uid'],
            ];
            $hist = [
                'rph_type' => 0,
                'rph_rq_id' => $this->request->getVar("serm_id"),
                'rph_status' => 0,
                'rph_amount' => $this->request->getVar('serm_cost'),
                'rph_created_on' => $date,
                'rph_created_by' => $tokendata['uid'],
            ];
            $paymenttrackerModel->insert($track_data);
            $paymenthistModel->insert($hist);
        }

        $serequestitemsModel->update($this->request->getVar("sitem_id"), $infdata);
        $sitem_data = $serequestitemsModel->where('sitem_id', $this->request->getVar('sitem_id'))->first();
        $serm_data = $servicequestModel->where('serm_id', $sitem_data['sitem_serid'])->first();
        $ntf_data = [];
        $us_id = $userModel
            ->where('us_delete_flag', 0)
            ->findAll();

        foreach ($us_id as $eachurl) {

            $indata = $this->request->getVar("flag") ? [
                'id' => $eachurl['us_id'],
                'headers' => "Job Request Rejected",
                'content' => "Job Request raised for  " . $serm_data['serm_number'] . "has been Rejected!!",
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_request_type' => 0,
                'nt_type_id' => $serm_data['serm_id'],
                'nt_type' => 0,
                'nt_req_number' => $serm_data['serm_number']
            ] :
                [
                    'id' => $eachurl['us_id'],
                    'headers' => "Job Request Accepted",
                    'content' => "Job Request raised for  " . $serm_data['serm_number'] . "has been accepted!!",
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

        $nt_id = $notificationmastercontroller->create_us_notification($ntf_data);


        $response['ret_data'] = 'success';
        return $this->respond($response, 200);
    }

    public function toolrecomendation()
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
        $serviceitemsmodel = new ServiceRequestItemsModel();

        $data = [
            'sitem_serid' => $this->request->getVar("sitem_serid"),
            'sitem_itemid' => $this->request->getVar("sitem_itemid"),
            'sitem_type' => 1,
            'sitem_cost' => $this->request->getVar("sitem_cost"),
            'sitem_reference' => $this->request->getVar("days"),
            'sitem_active_flag' => 0,
            'sitem_status_flag' => 3,
            'sitem_createdby' => $tokendata['uid'],
            'sitem_createdon' => $date,
            'sitem_updatedby' =>  $tokendata['uid'],
            'sitem_updatedon' => $date,

        ];

        $data = $serviceitemsmodel->insert($data);

        $response = $data ?

            [
                'ret_data' => 'success',
                'id' => $data
            ]
            :
            [
                'ret_data' => 'fail'
            ];

        return $this->respond($response, 200);
    }

    public function reopen_workcard()
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


        $workcardModel = new ServiceRequestMasterModel();
        $workcardhistoryModel = new ServiceRequestHistoryModel();
        $date = date("Y-m-d H:i:s");
        $id = $tokendata['aud'] == 'customer' ? 1 : 0;
        $data = [
            'serm_status' => 56,
            'serm_updatedby' => $tokendata['uid'],
            'serm_reopen_by' => $id,
            'serm_updatedon' => $date,
            'serm_reopen_desc' => $this->request->getVar("serm_reopen_desc")
        ];
        $hist_Data = [
            'srh_serm_id' => $this->request->getVar("serm_id"),
            'srh_status_id' => 56,
            'srh_created_by' => $tokendata['uid'],
            'srh_created_on' => $date,
        ];
        $workcardModel->update($this->request->getVar("serm_id"), $data);
        $workcardhistoryModel->insert($hist_Data);
        $response = [
            'ret_data' => 'success',
            'serm_id' => $this->request->getVar("serm_id")
        ];

        return $this->respond($response, 200);
    }

    public function job_assign_expert()


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
        $workcardModel = new ServiceRequestMasterModel();
        $workcardhistoryModel = new ServiceRequestHistoryModel();
        $workcardItemsModel = new ServiceRequestItemsModel();
        $rules = [
            'sitem_id' => 'required',
            'cstm_id' => 'required',

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $data = [
            'sitem_assignee_type' => 1,
            'sitem_assignee' => $this->request->getVar('cstm_id'),
            'sitem_status_flag' => -1,
            'sitem_updatedon' => $date,
            'sitem_updatedby' =>  $tokendata['uid'],

        ];

        $workcardItemsModel->update($this->request->getVar('sitem_id'), $data);

        $response = [
            'ret_data' => 'success'
        ];
        return $this->respond($response, 200);
    }


    public function recommended_tool_Det()
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
            'sitem_id' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $serviceitemsmodel = new ServiceRequestItemsModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $toolpackageModel = new ToolDetailsModel();

        $data = $serviceitemsmodel
            ->where('sitem_id', $this->request->getVar("sitem_id"))
            ->join('tool_details', 'tool_id=sitem_itemid')
            ->first();

        if ($data['tool_adv_payment'] != 0) {


            $data['rent_cost_total'] = ($data['tool_rent_cost'] * $data['sitem_reference']);
            $data['rent_advance_amount'] = $data['rent_cost_total'] > 0 ? ($data['rent_cost_total'] * $data['tool_adv_price']) / 100 : 0;
            $data['rent_cost_after_adv'] = $data['rent_advance_amount'] > 0 ? ($data['rent_cost_total'] - $data['rent_advance_amount']) : 0;
            $data['advp_id'] = 1;
        } else if ($data['tool_deposit_id'] != 0) {


            $data['rent_cost_total'] = ($data['tool_rent_cost'] * $data['sitem_reference']);
            $data['rent_deposit_amount'] = $data['rent_cost_total'] > 0 ? ($data['rent_cost_total'] * $data['tool_deposit_price']) / 100 : 0;
            $data['rent_cost_after_dep'] = $data['rent_deposit_amount'] > 0 ? ($data['rent_cost_total'] - $data['rent_deposit_amount']) : 0;
            $data['advp_id'] = 1;
        } else if ($data['tool_adv_payment'] == 0 && $data['tool_deposit_id'] == 0) {


            $data['rent_cost_total'] = $data['tool_rent_cost'] > 0 ?
                ($data['tool_rent_cost'] * $data['sitem_reference'])
                : 0;
            $data['advp_id'] = 0;
        }

        $response = [
            'ret_data' => 'success',
            'request_Details' => $data
        ];

        return $this->respond($response, 200);
    }

    public function recommended_tool_confirm()

    {

        $servicerequestitemsModel = new ServiceRequestItemsModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $expensetrackerModel = new ExpenseTrackerModel();
        $coupontrackerModel = new CouponTrackerModel();
        $paymenthistoryModel = new PaymentHistoryModel();
        $seqModel = new SequenceGeneratorModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $tooltrackerModel = new ToolTrackerModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $notificationmasterModel = new NotificationmasterModel();
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
            'request_Details' => 'required',
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $date = date("Y-m-d H:i:s");
        $req_data = $this->request->getVar('request_Details');

        $seq = $seqModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_TR" . $seq[0]['toolreq_sequence']);

        if ($this->request->getVar('flag')) {
            if ($req_data->sitem_id) {

                $sitem_data = [
                    'sitem_status_flag' => 5,
                    'sitem_updatedby' =>  $tokendata['uid'],
                    'sitem_updatedon' => $date,

                ];

                $servicerequestitemsModel->update($req_data->sitem_id, $sitem_data);
            }
        } else {

            $insert_tool_data = [
                'tldt_cstm_id' =>  $tokendata['uid'],
                'tldt_tool_id' => $req_data->tool_id,
                'tldt_number' => $nextval,
                'tldt_tool_quant' => 1,
                'tldt_tool_duration' => $req_data->sitem_reference,
                'tldt_cost' => $req_data->rent_cost_total,
                'tldt_delivery_address' => $this->request->getVar('cust_address'),
                'tldt_status' => 17,
                'tldt_active_flag' => 0,
                'tldt_advpaymt_flag' => $req_data->advp_id,
                'tldt_paymt_flag' => 1,
                'tldt_created_on' => $date,
                'tldt_created_by' => $tokendata['uid'],
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
                'tldt_reference' => $req_data->sitem_serid,
            ];

            $toolid = $ToolRequestDetailsModel->insert($insert_tool_data);


            $trks_data = $ToolDetailsModel->where('tool_id', $req_data->tool_id)->first();
            $revised_master = [
                'tool_rent_quantity' => ($trks_data['tool_rent_quantity']) - 1,
                'tool_total_quantity' => ($trks_data['tool_total_quantity']) - 1,
                'tool_updated_on' => $date,
            ];

            $revised_data = [
                'trk_tool_id' => $req_data->tool_id,
                'trk_type' => 1,
                'trk_status' => 2,
                'trk_rq_id' => $toolid,
                'trk_created_by' => $tokendata['uid'],
                'trk_quant' => 1,
                'trk_created_on' => $date,
                'trk_updated_by' => $tokendata['uid'],
                'trk_updated_on' => $date,

            ];
            $ToolDetailsModel->update(($req_data->tool_id), $revised_master);
            $trk_id = $tooltrackerModel->insert($revised_data);
            if ($toolid) {

                if ($req_data->sitem_id) {

                    $sitem_data = [
                        'sitem_reference' => $toolid,
                        'sitem_status_flag' => 4,
                        'sitem_updatedby' =>  $tokendata['uid'],
                        'sitem_updatedon' => $date,
                    ];

                    $servicerequestitemsModel->update($req_data->sitem_id, $sitem_data);
                }


                $seq = (intval($seq[0]['toolreq_sequence']) + 1);
                $seq_data = ['toolreq_sequence' => $seq];
                $seqModel->update(1, $seq_data);
                $histdata = [
                    'trqh_tr_id' => $toolid,
                    'trqh_status_id' => 17,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
                $toolhistid = $ToolRequestHistoryModel->insert($histdata);


                if ($req_data->tool_adv_payment == 1 || $req_data->tool_deposit_id == 1) {
                    $data1 = [
                        'tldt_status' => 5,
                        'tldt_active_flag' => 0,
                    ];
                    $histdata1 = [
                        'trqh_tr_id' => $toolid,
                        'trqh_status_id' => 5,
                        'trqh_created_on' => $date,
                        'tldt_created_by' => $tokendata['uid'],
                        'tldt_updated_on' => $date,
                        'tldt_updated_by' => $tokendata['uid'],
                    ];
                    $results1 = $ToolRequestDetailsModel->update($toolid, $data1);
                    $toolhistid1 = $ToolRequestHistoryModel->insert($histdata1);
                    if ($req_data->tool_deposit_id == 1) {
                        $payMast = [
                            'rpt_reqt_id' => $toolid,
                            'rpt_type' => 2,
                            'rpt_amount' => $req_data->rent_deposit_amount,
                            'rpt_status' => 0,
                            'rpt_cust_id' => $tokendata['uid'],
                            'rpt_created_on' => $date,
                            'rpt_created_by' => $tokendata['uid'],
                            'rpt_updated_on' => $date,
                            'rpt_updated_by' => $tokendata['uid'],

                        ];
                        $pay_d = [
                            'rph_type' => 1,
                            'rph_rq_id' => $toolid,
                            'rph_status' => 0,
                            'rph_amount' => $req_data->rent_deposit_amount,
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],


                        ];
                    } else {
                        $payMast = [
                            'rpt_reqt_id' => $toolid,
                            'rpt_type' => 2,
                            'rpt_amount' => $req_data->rent_advance_amount,
                            'rpt_status' => 0,
                            'rpt_cust_id' => $tokendata['uid'],
                            'rpt_created_by' => $tokendata['uid'],
                            'rpt_updated_on' => $date,
                            'rpt_updated_by' => $tokendata['uid'],
                        ];
                        $pay_d = [
                            'rph_type' => 1,
                            'rph_rq_id' => $toolid,
                            'rph_status' => 0,
                            'rph_amount' => $req_data->rent_advance_amount,
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                        ];
                    }
                    $custModel = new CustomerMasterModel();
                    $paymentTrackerModel->insert($payMast);
                    $paymenthistoryModel->insert($pay_d);
                    $tool_det = $ToolRequestDetailsModel->select('tldt_number,tldt_cstm_id')->where('tldet_id', $toolid)->first();
                    $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();

                    $player_id = [];
                    $custhead = "CATRAMS Tool Request Accepted!!!";
                    $custcontent = "" . $tool_det['tldt_number'] . "-Your Request for Tool has been accepted.Tap to pay.";
                    array_push($player_id, $target_cust['fcm_token_mobile']);
                    if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                    if ($ret_res) {
                        $notif_data = [
                            'nt_sourceid' => $tokendata['uid'],
                            'nt_destid' => $tool_det['tldt_cstm_id'],
                            'nt_req_number' => $tool_det['tldt_number'],

                            'nt_sourcetype' => 1,
                            'nt_header' => $custhead,
                            'nt_content' => $custcontent,
                            'nt_created_on' => $date
                        ];
                        $notificationmasterModel->insert($notif_data);
                    }

                    $response = [
                        'ret_data' => 'success',
                        'Message' => 'Request Proceeded for Advance Payment'
                    ];
                } else {

                    $histdata1 = [
                        'trqh_tr_id' => $toolid,
                        'trqh_status_id' => 1,
                        'trqh_created_on' => $date,
                        'trqh_created_by' => $tokendata['uid'],

                    ];
                    $data2 = [
                        'tldt_status' => 5,
                        'tldt_active_flag' => 1,
                        'tldt_created_on' => $date,
                        'tldt_created_by' => $tokendata['uid'],
                        'tldt_updated_on' => $date,
                        'tldt_updated_by' => $tokendata['uid'],
                    ];
                    $histdata2 = [
                        'trqh_tr_id' => $toolid,
                        'trqh_status_id' => 5,
                        'trqh_created_on' => $date,
                    ];

                    $toolhistid1 = $ToolRequestHistoryModel->insert($histdata1);
                    $results1 = $ToolRequestDetailsModel->update($toolid, $data2);
                    $toolhistid2 = $ToolRequestHistoryModel->insert($histdata2);

                    $payMast = [
                        'rpt_reqt_id' => $toolid,
                        'rpt_type' => 2,
                        'rpt_amount' => $req_data->rent_cost_total,
                        'rpt_status' => 0,
                        'rpt_cust_id' => $tokendata['uid'],
                        'rpt_created_on' => $date,
                        'rpt_created_by' => $tokendata['uid'],
                        'rpt_updated_on' => $date,
                        'rpt_updated_by' => $tokendata['uid'],
                    ];
                    $paymentTrackerModel->insert($payMast);
                    $pay_d = [
                        'rph_type' => 1,
                        'rph_rq_id' => $toolid,
                        'rph_status' => 0,
                        'rph_amount' => $req_data->rent_cost_total,
                        'rph_created_on' => $date,
                        'rph_created_by' => $tokendata['uid'],
                    ];

                    $paymenthistoryModel->insert($pay_d);


                    $player_id = [];
                    $custhead = "CATRAMS Tool Request Accepted!!!";
                    $tool_det = $ToolRequestDetailsModel->select('tldt_number,tldt_cstm_id')->where('tldet_id', $toolid)->first();
                    $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                    $custcontent = "" . $tool_det['tldt_number'] . "-Your Request for Tool has been accepted.Tap to pay or pay later.";
                    array_push($player_id, $target_cust['fcm_token_mobile']);
                    if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                    if ($ret_res) {
                        $notif_data = [
                            'nt_sourceid' => $tokendata['uid'],
                            'nt_destid' => $tool_det['tldt_cstm_id'],
                            'nt_req_number' => $tool_det['tldt_number'],

                            'nt_sourcetype' => 1,
                            'nt_header' => $custhead,
                            'nt_content' => $custcontent,
                            'nt_created_on' => $date
                        ];
                        $notificationmasterModel->insert($notif_data);
                    }
                    $response = [
                        'ret_data' => 'success',
                        'Message' => 'Request Accepted'
                    ];
                }
            }

            $response = [
                'ret_data' => 'success',
            ];

            return $this->respond($response, 200);
        }
    }

    public function work_card_activity_details()

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
        $workcardItemsModel = new ServiceRequestItemsModel();
        $result = $serequestModel
            ->select('serm_id,serm_status,serm_number,serm_vendor_flag')
            ->where('serm_deleteflag', 0)
            ->where('serm_active_flag', 0)
            ->whereIn('serm_status', [27, 28])
            ->where('serm_custid', $tokendata['uid'])
            ->join('status_master', 'sm_id=serm_status')
            ->orderBy('serm_id', 'desc')
            ->findAll();
        $requestdata = [];
        if (!empty($result)) {
            foreach ($result as $eachdata) {
                $total_jobs = $workcardItemsModel
                    ->where('sitem_serid', $eachdata['serm_id'])
                    ->where('sitem_active_flag', 1)
                    ->where('sitem_deleteflag', 0)
                    ->findAll();
                $isNewServicePresent = count(array_filter($total_jobs, function ($job) {
                    return $job['sitem_active_flag'] == 2;
                }));
                $jobs = array_filter($total_jobs, function ($job) {
                    return $job['sitem_active_flag'] == 1;
                });
                $jobCounts = $this->getJobCounts($jobs);
                $totalJobs = count($jobs);
                $completionPercentage = ($totalJobs > 0) ? (($jobCounts['inProgressJobs'] * 0.5 + $jobCounts['completedJobs']) / $totalJobs * 100) : 0;
                $eachdata['total_services_count'] = count($total_jobs);
                $eachdata['completed_services_count'] = $jobCounts['completedJobs'];
                $eachdata['work_progress_percentage'] = $completionPercentage;
                $eachdata['new_service_present'] = $isNewServicePresent;
                $eachdata['pending_services_count'] = $jobCounts['pendingJobs'];
                $requestdata[] = $eachdata;
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

    public function getJobCounts($jobs)
    {
        return array_reduce($jobs, function ($counts, $job) {
            switch ($job['sitem_status_flag']) {
                case 0:
                    $counts['pendingJobs']++;
                    break;
                case 1:
                    $counts['inProgressJobs']++;
                    break;
                case 2:
                    $counts['completedJobs']++;
                    break;
            }
            return $counts;
        }, ['pendingJobs' => 0, 'inProgressJobs' => 0, 'completedJobs' => 0]);
    }

    public function workcard_reject()
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


        $approvalMasterModel = new ApprovalmasterModel();
        $date = date("Y-m-d H:i:s");

        if ($this->request->getVar("am_id")) {
            $app_data = [
                'am_status' => 2,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
            ];
            $a_data = $approvalMasterModel->update($this->request->getVar("am_id"), $app_data);
        }

        if ($a_data) {

            $response['ret_data'] = "success";
            $response['result'] = $a_data;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'Cannot Update';
            return $this->respond($response, 200);
        }
    }
}
