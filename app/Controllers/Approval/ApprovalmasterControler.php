<?php

namespace App\Controllers\Approval;

use App\Controllers\Quote\QuoteMasterController;
use App\Models\Approval\ApprovalmasterModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerProducts;
use App\Models\Order\OrderrequesttrackerModel;
use App\Models\Packages\ServiceRequestPackageModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\Products\ProductMasterModel;
use App\Models\Quotation\QuoteItemsModel;
use App\Models\Quotation\QuoteMasterModel;
use App\Models\Sequence\SequenceGeneratorModel;
use App\Models\ServiceRequest\ServiceRequestItemsModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\ServiceRequest\ServiceRequestMediaModel;
use App\Models\StatusMaster\StatusMasterModel;
use App\Models\System\NotificationmasterModel;
use App\Models\System\OrderHistoryModel;
use App\Models\System\OrderMasterModel;
use App\Models\ToolRequest\ToolDetailsModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use App\Models\ToolRequest\ToolRequestTrackerModel;
use App\Models\User\UsersModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use Config\Validation;

class ApprovalmasterControler extends ResourceController
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
        $approvalModel = new ApprovalmasterModel();
        $quotemasterModel = new QuoteMasterModel();
        $tooltrackerModel = new ToolRequestTrackerModel();
        $ordertrackermodel = new OrderrequesttrackerModel();
        $productmasterModel = new ProductMasterModel();


        $result = $approvalModel->where('am_deleteflag', 0)
            ->where('am_type', 0)
            ->Orwhere('am_type', 1)
            ->Orwhere('am_type', 2)
            ->Orwhere('am_type', 3)
            ->Orwhere('am_type', 8)
            ->Orwhere('am_type', 9)
            ->join('tool_request_details', 'tldet_id=am_reqid')
            ->join('tool_details', 'tool_id=tldt_tool_id')
            ->join('request_payment_tracker', 'rpt_reqt_id=tldet_id', 'left')
            ->join('customer_master', 'cstm_id=am_createdby')
            ->orderBy('am_id', 'desc')
            ->findAll();

        for ($i = 0; $i < sizeof($result); $i++) {

            if ($result[$i]['tldt_status'] == 47) {
                $tr_id = $result[$i]['tldet_id'];
                $quote_det = $quotemasterModel->where('qtm_type', 1)->where('qtm_serm_id', $tr_id)->where('qtm_status_id', 47)->first();
                $result[$i]['quote_det'] = $quote_det;
            }
            if ($result[$i]['tldt_status'] == 37) {
                $tr_id = $result[$i]['tldet_id'];
                $tracker_data = $tooltrackerModel->select('trt_url,trt_url_type')->where('trt_rq_id', $tr_id)->findAll();
                if ($tracker_data) {
                    $result[$i]['tracker_data'] = $tracker_data;
                }
            }
        }

        $result1 = $approvalModel->where('am_deleteflag', 0)
            ->where('am_type', 10)
            ->join('order_master', 'order_id=am_reqid')
            ->join('tool_details', 'tool_id=am_referenceid')
            ->join('customer_master', 'cstm_id=am_requestedby')
            ->orderBy('am_updatedon', 'desc')
            ->findAll();

        for ($i = 0; $i < sizeof($result1); $i++) {

            $track = $ordertrackermodel->where('ort_order_id', $result1[$i]['order_id'])->findAll();
            if ($track) {
                $result1[$i]['tracker_data'] = $track;
            }
        }
        $totals = array();
        $total = array();
        $pm_data = array();
        $total = $result + $result1;


        $pm_data = $approvalModel->where('am_type', 11)
            ->where('am_status', 0)
            ->join('product_master', 'pm_id=am_referenceid')
            ->join('customer_master', 'cstm_id=am_requestedby')
            ->join('customer_products', 'cp_cstm_id=am_requestedby', 'left')
            ->orderBy('am_updatedon', 'desc')
            ->findAll();


        if ($pm_data) {
            $totals = array_merge($total, $pm_data);
        } else {
            $totals = $total;
        }


        if ($totals) {
            $data['ret_data'] = "success";
            $data['approval_list'] = $totals;
        } else {
            $data['ret_data'] = "No data found";
        }
        return $this->respond($data, 200);
        // } else {
        //     $data['ret_data'] = "Invalid user";
        //     return $this->fail($data, 400);
        // }
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
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
        $current_date = date('Y-m-d');

        $aprovalmasterModel = new ApprovalmasterModel();
        $statusmsterModel = new StatusMasterModel();
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $date = date("Y-m-d H:i:s");
        $apData = [
            'am_reqid' => $this->request->getVar('req_id'),
            'am_referenceid' => $this->request->getVar('toolid'),
            'am_type' => $this->request->getVar('am_type'),
            'am_requestedby' => $this->request->getVar('cust_id'),
            'am_createdby' => $this->request->getVar('cust_id'),
            'am_status' => $this->request->getVar('am_status'),
            'am_updatedby' => $this->request->getVar('cust_id'),
            'am_updatedon' => $date,
            'am_createdon' => $date,

        ];
        $aprovalmasterModel->insert($apData);
        if ($aprovalmasterModel) {
            $status = [
                'tldt_purchase_flag' => 1,
                'tldt_status' => 37,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],

            ];
            $hist_status = [
                'trqh_status_id' => 37,
                'trqh_tr_id' => $this->request->getVar('req_id'),
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $ToolRequestHistoryModel->insert($hist_status);
            $toolrequestmasterModel->update($this->request->getVar('req_id'), $status);


            $response = [
                'ret_data' => 'success',
            ];
        } else {
            $response['ret_data'] = 'success';
            $response['Message'] = 'insertion problem try again';
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
        $approvalModel = new ApprovalmasterModel();
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $notificationmasterModel = new NotificationmasterModel();
        $tooldetailsModel = new ToolDetailsModel();
        $custModel = new CustomerMasterModel();
        $date = date("Y-m-d H:i:s");
        $update_data = [
            'am_status' => $this->request->getVar("status"),
            'am_updatedby' => $tokendata['uid'],
            'am_updatedon' => $date,
        ];
        $approvalModel->update($this->request->getVar('am_id'), $update_data);

        if ($this->request->getVar("status") == 1) {
            $tr_id = $this->request->getVar('tldet_id');
            $updtdata1 = [
                'tldt_status' => 41,
                'tldt_damaged' => 1,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $insertdata1 =
                [
                    'trqh_status_id' => 40,
                    'trqh_tr_id' => $tr_id,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
            $results = $ToolRequestDetailsModel->update($tr_id, $updtdata1);
            $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
            $insertdata2 =
                [
                    'trqh_status_id' => 41,
                    'trqh_tr_id' => $tr_id,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
            $toolhistid = $ToolRequestHistoryModel->insert($insertdata2);

            $req_Det = $ToolRequestDetailsModel->where('tldet_id', $tr_id)->first();

            $target_cust = $custModel->where('cstm_id', $req_Det['tldt_cstm_id'])->first();
            $player_id = [];
            $custhead = "CATRAMS- Your Request has been Approved";
            $custcontent = "" . $req_Det['tldt_number'] . "-Your Request has been Approved.";
            array_push($player_id, $target_cust['fcm_token_mobile']);
            if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

            if ($ret_res) {
                $notif_data = [
                    'nt_sourceid' => $tokendata['uid'],
                    'nt_destid' => $req_Det['tldt_cstm_id'],
                    'nt_req_number' => $req_Det['tldt_number'],
                    'nt_sourcetype' => 1,
                    'nt_header' => $custhead,
                    'nt_content' => $custcontent,
                    'nt_created_on' => $date
                ];
                $notificationmasterModel->insert($notif_data);
            }
            $response = [
                'ret_data' => 'success'
            ];
            return $this->respond($response, 200);
        } else if ($this->request->getVar("status") == 2) {
            $tr_id = $this->request->getVar('tldet_id');
            $updtdata1 = [
                'tldt_status' => 18, 'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $insertdata1 = [
                'trqh_status_id' => 18,
                'trqh_tr_id' => $tr_id,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],

            ];
            $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
            $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
            $pay_flag = $ToolRequestDetailsModel->select('tldt_paymt_flag')->where('tldet_id', $tr_id)->findAll();
            foreach ($pay_flag as $pay_flag) {
                $payflag = $pay_flag['tldt_paymt_flag'];
            }
            if ($payflag == 1) {
                $updtdata2 = [
                    'tldt_status' => 15, 'tldt_updated_on' => $date,
                    'tldt_updated_by' => $tokendata['uid'],
                ];
                $insertdata2 = [
                    'trqh_status_id' => 15,
                    'trqh_tr_id' => $tr_id,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],

                ];
                $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata2);
                $toolhistid = $ToolRequestHistoryModel->insert($insertdata2);
                $response['ret_data'] = 'success';
            } else {
                $updtdata1 = [
                    'tldt_status' => 11, 'tldt_updated_on' => $date,
                    'tldt_updated_by' => $tokendata['uid'],
                ];
                $insertdata1 = [
                    'trqh_status_id' => 11,
                    'trqh_tr_id' => $tr_id,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],

                ];
                $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                $response['ret_data'] = 'success';
            }

            $tool_det = $ToolRequestDetailsModel->select('tldt_number,tldt_cstm_id')->where('tldet_id', $tr_id)->first();

            $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
            $player_id = [];
            $custhead = "CATRAMS- Your Request has been Rejected";
            $custcontent = "" . $tool_det['tldt_number'] . "-Your Request has been rejected.Appologies for any inconviences.";
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
            return $this->respond($response, 200);
        } else if (($this->request->getVar("status") == 3)) {
            $quotemasterModel = new QuoteMasterModel();
            $toolrequestmasterModel = new ToolRequestDetailsModel();
            $insert = [
                'qtm_type' => 1,
                'qtm_serm_id' => $this->request->getVar("tldet_id"),
                'qtm_cost' => $this->request->getVar("tool_price"),
                'qtm_number' => $this->request->getVar("tldet_id"),
                'qtm_created_by' => $tokendata['uid'],
                'qtm_created_on' => $date,
                'qtm_updated_by' => $tokendata['uid'],
                'qtm_updated_on' => $date,
            ];

            $res = $quotemasterModel->insert($insert);

            // $res=$toolrequestmasterModel->update($this->request->getVar('tldet_id'),$insert);
            if ($res) {

                $admin_approved = [
                    'trqh_status_id' => 40,
                    'trqh_tr_id' => $this->request->getVar('tldet_id'),
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
                $approve_hist = $ToolRequestHistoryModel->insert($admin_approved);
                $quote_created = [
                    'trqh_status_id' => 44,
                    'trqh_tr_id' => $this->request->getVar('tldet_id'),
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
                $quote_c = $ToolRequestHistoryModel->insert($quote_created);
                $quote_Pending = [
                    'trqh_status_id' => 45,
                    'trqh_tr_id' => $this->request->getVar('tldet_id'),
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
                $quote_p = $ToolRequestHistoryModel->insert($quote_Pending);

                $master_update = [
                    'tldt_status' => 45,
                    'tldt_purchase_flag' => 2,
                    'tldt_updated_on' => $date,
                    'tldt_updated_by' => $tokendata['uid'],
                ];
                $res = $toolrequestmasterModel->update($this->request->getVar('tldet_id'), $master_update);
                $response =
                    [
                        'ret_data' => 'success'
                    ];

                $tool_det = $ToolRequestDetailsModel->select('tldt_number,tldt_cstm_id')->where('tldet_id', $this->request->getVar('tldet_id'))->first();
                $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                $player_id = [];
                $custhead = "CATRAMS- Your Purchase Request has been Approved";
                $custcontent =  "Tap to view Quote for the purchase request";
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
            } else {
                $response =
                    [
                        'ret_data' => 'success',
                        'Message' => 'no data'
                    ];
            }
            return $this->respond($response, 200);
        } else if (($this->request->getVar("status") == 4)) {
            $toolrequestmasterModel = new ToolRequestDetailsModel();
            $ToolRequestHistoryModel = new ToolRequestHistoryModel();
            $insert = [
                'tldt_purchase_flag' => 3,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $res = $toolrequestmasterModel->update($this->request->getVar('tldet_id'), $insert);
            $insert_hist = [
                'trqh_tr_id' => $this->request->getVar('tldet_id'),
                'trqh_status_id' => 39,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $ToolRequestHistoryModel->insert($insert_hist);
            if ($res) {
                $pay_flag = $ToolRequestDetailsModel->select('tldt_paymt_flag')->where('tldet_id', $this->request->getVar('tldet_id'))->findAll();
                foreach ($pay_flag as $pay_flag) {
                    $payflag = $pay_flag['tldt_paymt_flag'];
                }
                if ($payflag == 1) {
                    $updtdata2 = [
                        'tldt_status' => 15, 'tldt_updated_on' => $date,
                        'tldt_updated_by' => $tokendata['uid'],
                    ];
                    $insertdata2 = [
                        'trqh_status_id' => 15,
                        'trqh_tr_id' => $this->request->getVar('tldet_id'),
                        'trqh_created_on' => $date,
                        'trqh_created_by' => $tokendata['uid'],
                    ];
                    $results = $ToolRequestDetailsModel->update($this->db->escapeString($this->request->getVar('tldet_id')), $updtdata2);
                    $toolhistid = $ToolRequestHistoryModel->insert($insertdata2);
                    $response['ret_data'] = 'success';
                    return $this->respond($response, 200);
                } else {
                    $updtdata1 = [
                        'tldt_status' => 11, 'tldt_updated_on' => $date,
                        'tldt_updated_by' => $tokendata['uid'],
                    ];
                    $insertdata1 = [
                        'trqh_status_id' => 11,
                        'trqh_tr_id' => $this->request->getVar('tldet_id'),
                        'trqh_created_on' => $date,
                        'trqh_created_by' => $tokendata['uid'],
                    ];
                    $results = $ToolRequestDetailsModel->update($this->db->escapeString($this->request->getVar('tldet_id')), $updtdata1);
                    $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                    $response['ret_data'] = 'success';
                    return $this->respond($response, 200);
                }

                $response =
                    [
                        'ret_data' => 'success'
                    ];
            } else {
                $response =
                    [
                        'ret_data' => 'success',
                        'Message' => 'no data '
                    ];
            }
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
    public function purchasequote_tool()
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
        $date = date("Y-m-d H:i:s");
        $rules = [
            'qtm_id' => 'required',
            'status' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $inData = [
            'qtm_type' => 1,
            'qtm_status_id' => $this->request->getVar("status"),
            'qtm_updated_by' => 1,
            'qtm_updated_by' => $tokendata['uid'],
            'qtm_updated_on' => $date,

        ];
        $result = $quotemodel->update($this->request->getVar("qtm_id"), $inData);
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

    public function update_hold_req()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
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
            'rq_id' => 'required',
            'am_id' => 'required',
            'status' => 'required',
            'type' => 'required'
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $approvalModel = new ApprovalmasterModel();
        $notificationmasterModel = new NotificationmasterModel();
        $custModel = new CustomerMasterModel();
        if ($this->request->getVar("type") == 0) {
            $ToolRequestHistoryModel = new ToolRequestHistoryModel();
            $ToolRequestDetailsModel = new ToolRequestDetailsModel();
            $data = [
                'am_reqid' => $this->request->getVar("rq_id"),
                'am_status' => 2,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
            ];
            $approvalModel->update($this->request->getVar("am_id"), $data);
            $master = [
                'tldt_hold_flag' => 0,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $ToolRequestDetailsModel->update($this->request->getVar("rq_id"), $master);

            $tool_det = $ToolRequestDetailsModel->select('tldt_number,tldt_cstm_id')->where('tldet_id', $this->request->getVar('rq_id'))->first();
            $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
            $player_id = [];
            $custhead = "CATRAMS- Your Hold Request Has been Rejected";
            $custcontent =  "Sorry!!!Please Pay the Due amount";
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

            $response['ret_data'] = "success";
            return $this->respond($response, 200);
        }
    }

    public function approval_for_hold()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
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
            'am_reason' => 'required',
            'serm_id' => 'required'
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $approvalModel = new ApprovalmasterModel();
        $notificationmasterModel = new NotificationmasterModel();
        $custModel = new CustomerMasterModel();
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $hold_criteria = [
            'am_reason' => $this->request->getVar('am_reason'),
            'am_reqid' => $this->request->getVar('serm_id'),
            'am_type' => 4,
            'am_requestedby' => $tokendata['uid'],
            'am_status' => 0,
            'am_updatedby' => $tokendata['uid'],
            'am_updatedon' => $date,
            'am_createdby' => $tokendata['uid'],
            'am_createdon' => $date,
        ];
        $am_id = $approvalModel->insert($hold_criteria);
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

    public function app_service()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }


        $approvalModel = new ApprovalmasterModel();
        $resultworks = $approvalModel->where('am_deleteflag', 0)
            ->where('am_type', 4)
            ->Orwhere('am_type', 6)
            ->join('servicerequest_master', 'serm_id=am_reqid')
            ->join('customer_master', 'cstm_id=am_requestedby', 'left')
            ->join('users', 'us_id=am_createdby', 'left')
            ->orderBy('am_id', 'desc')
            ->findAll();



        $resultjobs = $approvalModel->where('am_deleteflag', 0)
            ->where('am_type', 5)
            ->Orwhere('am_type', 7)
            ->join('servicerequest_items', 'sitem_id=am_reqid')
            ->join('servicerequest_master', 'serm_id=sitem_serid')
            ->join('service_request_package', 'servpack_id=am_referenceid')
            ->join('customer_master', 'cstm_id=am_requestedby', 'left')
            ->join('users', 'us_id=am_createdby', 'left')
            ->orderBy('am_id', 'desc')
            ->findAll();
        if ($resultjobs) {
            foreach ($resultjobs as $jobs) {
                array_push($resultworks, $jobs);
            }
        }




        if ($resultworks) {
            $data['ret_data'] = "success";
            $data['approval_list'] = $resultworks;
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "No data found";
            return $this->respond($data, 200);
        }
    }

    public function request_rejected_hold()
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $approvalModel = new ApprovalmasterModel();
        $data = [
            'am_status' => 3
        ];
        $approvalModel->update($this->request->getVar('am_id'), $data);

        $response['ret_data'] = 'success';
        return $this->respond($response, 200);
    }

    public function saledamaged_update()
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
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $paymentmasterModel = new PaymentTrackermasterModel();
        $ordermasterModel = new OrderMasterModel();
        $orderhistoryModel = new OrderHistoryModel();
        $date = date("Y-m-d H:i:s"); {

            if ($this->request->getVar('type') == 0) {

                $hist1 = [
                    'ohist_order_id' => $this->request->getVar('order_id'),
                    'ohist_order_status' => 40,
                    'ohist_created_by' => $tokendata['uid'],
                    'ohist_created_on' => $date,
                    'ohist_updated_on' => $date,
                    'ohist_updated_by' =>  $tokendata['uid'],

                ];

                $orderhistoryModel->insert($hist1);

                $data = [
                    'am_status' => 1,
                    'am_updatedby' => $tokendata['uid'],
                    'am_updatedon' => $date,
                ];
                $app_data = $approvalMasterModel->update($this->request->getVar('am_id'), $data);
                $master = [
                    'order_status' => 41,
                    'order_updated_on' => $date,
                    'order_updated_by' => $tokendata['uid'],
                ];
                $hist2 =  [
                    'ohist_order_id' => $this->request->getVar('order_id'),
                    'ohist_order_status' => 41,
                    'ohist_created_by' => $tokendata['uid'],
                    'ohist_created_on' => $date,
                    'ohist_updated_on' => $date,
                    'ohist_updated_by' =>  $tokendata['uid'],
                ];

                $ordermasterModel->update($this->request->getVar('order_id'), $master);
                $orderhistoryModel->insert($hist2);
            } else if ($this->request->getVar('type') == 1) {
                $data = [
                    'am_status' => 2,
                    'am_updatedby' => $tokendata['uid'],
                    'am_updatedon' => $date,
                ];
                $app_data = $approvalMasterModel->update($this->request->getVar('am_id'), $data);

                $hist1 =  [
                    'ohist_order_id' => $this->request->getVar('order_id'),
                    'ohist_order_status' => 39,
                    'ohist_created_by' => $tokendata['uid'],
                    'ohist_created_on' => $date,
                    'ohist_updated_on' => $date,
                    'ohist_updated_by' =>  $tokendata['uid'],
                ];

                $orderhistoryModel->insert($hist1);

                $master = [
                    'order_status' => 39,
                    'order_updated_on' => $date,
                    'order_updated_by' => $tokendata['uid'],
                ];
                $ordermasterModel->update($this->request->getVar('order_id'), $master);
            }

            $response['ret_data'] = 'success';
            return $this->respond($response, 200);
        }
    }


    public function premium_approval()
    {   $userModel = new UsersModel();
        $custModel = new CustomerMasterModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
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
            'pm_id' => 'required',
            'cstm_id' => 'required',
            'am_id' => 'required',
            'cp_id' => 'required',
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $approalmasterModel = new ApprovalmasterModel();
        $customerproductsModel = new CustomerProducts();
        $productMaster = new ProductMasterModel();
        if ($this->request->getVar('flag') == 1) {

            $am_data = [
                'am_status' => 2,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
            ];
            $cust_data = [
                'cp_status' => 2,
                'cp_updated_by' => $tokendata['uid'],
                'cp_updated_on' => $date,
            ];
        } else {
            $am_data = [
                'am_status' => 1,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
            ];
            $cust_data = [
                'cp_status' => 1,
                'cp_updated_by' => $tokendata['uid'],
                'cp_updated_on' => $date,
            ];
            $pm_data = [
                'pm_delete_flag' => 1,
                'pm_updated_by' => $tokendata['uid'],
                'pm_updated_on' => $date,
            ];
            $cust_master=[
                'cstm_type'=>1
            ];
            $custModel->update($this->request->getVar('cstm_id'),$cust_master);
            $productMaster->update($this->request->getVar('pm_id'), $pm_data);
        }

        $approalmasterModel->update($this->request->getVar('am_id'), $am_data);
        $customerproductsModel->update($this->request->getVar('cp_id'), $cust_data);
        $response['ret_data'] = 'success';
        return $this->respond($response, 200);
    }

     
}
