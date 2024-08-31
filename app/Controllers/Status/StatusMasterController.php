<?php

namespace App\Controllers\Status;

use App\Controllers\Payment\PaymentMasterController;
use App\Controllers\System\UsersNotificationController;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Payment\PaymentHistoryModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\Quotation\QuoteMasterModel;
use App\Models\ServiceRequest\ServiceRequestHistoryModel;
use App\Models\ServiceRequest\ServiceRequestItemsModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use CodeIgniter\API\ResponseTrait;
use App\Models\StatusMaster\StatusMasterModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use App\Models\User\UsersModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use Config\Validation;

class StatusMasterController extends ResourceController
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
        //
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

    public function status_master()
    {
        $userModel = new UsersModel();
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
        $response['ret_data'] = 'success';
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $statusmaster = new StatusMasterModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $servicerequestModel = new ServiceRequestMasterModel();
        $servicehistoryModel = new ServiceRequestHistoryModel();
        $quotemasterModel = new QuoteMasterModel();
        $tr_id = $this->request->getVar('tr_id');
        $status_id = $this->request->getVar('status_id');
        $flag_id = $this->request->getVar('flag_id');
        $qtm_id = $this->request->getVar('qtm_id');
        $services = $this->request->getVar("qt_details");
        $serequestitemsModel = new ServiceRequestItemsModel();
        $notificationmasterController= new UsersNotificationController;
        $date = date("Y-m-d H:i:s");
        $paymentmasterController= new PaymentMasterController;

        // $attachments = $this->request->getVar("attachme

        // tool request
        //Status id for payment completed (id=6):no flag=>request completed,flag =1 =>tool ready to deliver ,flag=2=>.tool reached workshop 
        //status id for request acceptes(id=1):flag=1=>payment pending,no flag=>tool ready to deliver.
        //status id for return tool(id=11):no flag=>tool reached workshop,flag=1=>payment pending.
        // Status id for inspection in progress(id=12):no flag=>Inspection Completed,flag=1=>Inspection Tool Damaged.
        //Status id for Payment Pending(id=5):no flag =>Payment Completed,flag = 1 =>Payment Due,flag=2=>tool ready to deliver.
        // service request

        if ($this->request->getVar('sm_pk_id')) {

            $sr_id = $this->request->getVar('serm_id');

            if ($sr_id) {
                if ($status_id == 20) {
                    $next_status = 21;
                } else if ($status_id == 21) {
                    if ($flag_id == 1) {
                        $next_status = 22;
                    }
                    $next_status = 23;
                } else if ($status_id == 23) {
                    $next_status = 24;
                } else if ($status_id == 25) {
                    $next_status = 26;
                } else if ($status_id == 26) {
                    if ($flag_id = 1) {
                        $next_status = 21;
                    } else {
                        $next_status = 35;
                    }
                } else if ($status_id == 35) {
                    $next_status = 27;
                } else if ($next_status = 28) {
                    if ($flag_id == 1) {
                        $next_status = 21;
                    } else if ($flag_id == 2) {
                        $next_status = 26;
                    } else {
                        $next_status = 29;
                    }
                } else if ($status_id == 29) {
                    if ($flag_id == 1) {
                        $next_status = 21;
                    } else {
                        $next_status = 30;
                    }
                } else if ($status_id == 31) {
                    $next_status = 32;
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
                $results1 = $servicerequestModel->update($this->db->escapeString($sr_id), $updtdata);
                $toolhistid1 = $servicehistoryModel->insert($insertdata);
                $quoteinsert = $quotemasterModel->update($this->db->escapeString($qtm_id), $insertquote);

                if ($next_status == 24) {
                    $updtdata = [
                        'serm_status' => 25,
                        'serm_updatedon' => $date,
                        'serm_updatedby' => $tokendata['uid']


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

                    $results1 = $servicerequestModel->update($this->db->escapeString($sr_id), $updtdata);
                    $toolhistid1 = $servicehistoryModel->insert($insertdata);
                    $quoteinsert = $quotemasterModel->update($this->db->escapeString($qtm_id), $insertquote);
                    $response = [
                        'ret_data' => 'success',
                        'sr_id' => $sr_id,
                        'status' => $servq_req_status,
                        'status_code' => $servq_req_status_code
                    ];

                    return $this->respond($response, 200);
                } else if ($next_status == 23) {

                    
                   


                    $paymenthistModel = new PaymentHistoryModel();
                    $paymenttrackerModel = new PaymentTrackermasterModel();
                    $updtdata = [
                        'serm_status' => 25,
                        'serm_cost' => $this->request->getVar('total_amount'),
                        'serm_ad_type' => $this->request->getVar('serm_ad_type'),
                        'serm_ad_charge' => $this->request->getVar('serm_ad_charge'),
                        'serm_ad_charge_cost' => $this->request->getVar('serm_ad_charge_cost'),
                        'serm_custpay_amount'=> $this->request->getVar('paid_amount'),
                        'serm_updatedon' => $date,
                        'serm_updatedby' => $tokendata['uid'],
                        'serm_discount_amount'=>$this->request->getVar('amount_after_discount')
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
                   

                    if ($paid_amount>0) {
                        $t_details=$this->request->getVar('transaction_details');
                        $hist1 = [
                            'rph_type' => 0,
                            'rph_rq_id' => $this->db->escapeString($sr_id),
                            'rph_status' => 0,
                            'rph_amount' => $paid_amount,
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                        ];
                        $paymenthistModel->insert($hist1);

                   
                        $balance_amount =( $this->request->getVar('amount_after_discount')>0)? $this->request->getVar('amount_after_discount') - $paid_amount:
                        $this->request->getVar('total_amount') - $paid_amount;

                        

                        if ($balance_amount == 0) {
                            $track_data = [
                                'rpt_type' => 1,
                                'rpt_reqt_id' => $this->db->escapeString($sr_id),
                                'rpt_amount' => $balance_amount,
                                'rpt_cust_id' => $tokendata['uid'],
                                'rpt_status' => 0,
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
                                        'sitem_itemid'   => $services[$i]->servpack_id,
                                        'sitem_serid'   => $this->db->escapeString($sr_id),
                                        'sitem_type' => 0,
                                        'sitem_cost' => $services[$i]->qti_cost,
                                        'sitem_createdon' => $date,
                                        'sitem_createdby' =>  $tokendata['uid'],
                                        'sitem_updatedby' =>  $tokendata['uid'],
                                        'sitem_updatedon' => $date,
                                        'sitem_paid_status'=>2
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
                                        'sitem_itemid'   => $services[$i]->servpack_id,
                                        'sitem_serid'   => $this->db->escapeString($sr_id),
                                        'sitem_type' => 0,
                                        'sitem_cost' => $services[$i]->qti_cost,
                                        'sitem_createdon' => $date,
                                        'sitem_createdby' =>  $tokendata['uid'],
                                        'sitem_updatedby' =>  $tokendata['uid'],
                                        'sitem_updatedon' => $date,
                                        'sitem_paid_status'=>0
                                    ];
                                    array_push($in_data, $infdata);
                                }
                                $ret = $serequestitemsModel->insertBatch($in_data);
                            }
                            $track_data = [
                                'rpt_type' => 1,
                                'rpt_reqt_id' => $this->db->escapeString($sr_id),
                                'rpt_amount' => $balance_amount,
                                'rpt_cust_id' => $tokendata['uid'],
                                'rpt_status' => 0,
                                'rpt_created_on' => $date,
                                'rpt_created_by' => $tokendata['uid'],
                                'rpt_updated_on' => $date,
                                'rpt_updated_by' => $tokendata['uid'],
                                'rpt_transaction_id' => $this->request->getVar('txnid'),
                            ];
                        }
                        $paymenttrackerModel->insert($track_data);
                        $results1 = $servicerequestModel->update($this->db->escapeString($sr_id), $updtdata);
                        $toolhistid1 = $servicehistoryModel->insert($insertdata);
                        $quoteinsert = $quotemasterModel->update($this->db->escapeString($qtm_id), $insertquote);
                      


                        if($t_details->result=='payment_successfull'){
                            $hist2 = [
                                'rph_type' => 0,
                                'rph_rq_id' => $this->db->escapeString($sr_id),
                                'rph_status' => 1,
                                'rph_amount' => $paid_amount,
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id' => $this->request->getVar('txnid')
                            ];
                            $paymenthistModel->insert($hist2);
                           
                            $data= $paymentmasterController->serv_balance_amount( $t_details->payment_response);
                        }else{
                            $hist2 = [
                                'rph_type' => 0,
                                'rph_rq_id' => $this->db->escapeString($sr_id),
                                'rph_status' => 1,
                                'rph_amount' => $paid_amount,
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id' => $this->request->getVar('txnid')
                            ];
                            $paymenthistModel->insert($hist2);
                            $data= $paymentmasterController->failed_transaction( $t_details->payment_response);
                        }
                    } else {

                        if (count($services) > 0) {
                            $in_data = array();
                            for ($i = 0; $i < count($services); $i++) {
                                $infdata = [
                                    'sitem_itemid'   => $services[$i]->servpack_id,
                                    'sitem_serid'   => $this->db->escapeString($sr_id),
                                    'sitem_type' => 0,
                                    'sitem_cost' => $services[$i]->qti_cost,
                                    'sitem_createdon' => $date,
                                    'sitem_createdby' =>  $tokendata['uid'],
                                    'sitem_updatedby' =>  $tokendata['uid'],
                                    'sitem_updatedon' => $date,
                                    'sitem_paid_status'=>0
                                ];
                                array_push($in_data, $infdata);
                            }
                            $ret = $serequestitemsModel->insertBatch($in_data);
                        }

                        $hist5 = [
                            'rph_type' => 0,
                            'rph_rq_id' => $this->db->escapeString($sr_id),
                            'rph_status' => 0,
                            'rph_amount' => $this->request->getVar('amount_after_discount'),
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                        ];
                        $paymenthistModel->insert($hist5);

                        $track_data = [
                            'rpt_type' => 1,
                            'rpt_reqt_id' => $this->db->escapeString($sr_id),
                            'rpt_amount' => $this->request->getVar('amount_after_discount'),
                            'rpt_cust_id' => $tokendata['uid'],
                            'rpt_status' => 0,
                            'rpt_created_on' => $date,
                            'rpt_created_by' => $tokendata['uid'],
                            'rpt_updated_on' => $date,
                            'rpt_updated_by' => $tokendata['uid'],
                            
                        ];
                        $paymenttrackerModel->insert($track_data);
                        $results1 = $servicerequestModel->update($this->db->escapeString($sr_id), $updtdata);
                        $toolhistid1 = $servicehistoryModel->insert($insertdata);
                        $quoteinsert = $quotemasterModel->update($this->db->escapeString($qtm_id), $insertquote);
                    }

                    $serm_data=$servicerequestModel->where('serm_id',$sr_id)->first();

                    $us_id = $userModel->where('us_delete_flag', 0)->findAll();
                    $ntf_data = [];
                   
                    foreach ($us_id as $eachurl) {
                     
                        $indata = [
                            'id' => $eachurl['us_id'],
                            'headers'=>"Quotation Accepted For ". $serm_data['serm_number'],
                            'content'=>"Work Card Created For " . $serm_data['serm_number'] ,
                            'sourceid' => $tokendata['uid'],
                            'destid' => $eachurl['us_id'],
                            'date' => $date,

                            'nt_request_type'=>0,
                            'nt_type_id'=>$serm_data['serm_id'],
                            'nt_type'=>0
                        ];
                        array_push($ntf_data, $indata);
                    }
                    $nt_id = $notificationmasterController->create_us_notification($ntf_data);


                    $response = [
                        'ret_data' => 'success',
                        'sr_id' => $sr_id,
                        'status' => $servq_req_status,
                        'status_code' => $servq_req_status_code
                    ];


                    return $this->respond($response, 200);
                } else if ($next_status == 27) {
                    $updtdata = [
                        'serm_status' => 28,
                        'serm_updatedon' => $date,
                        'serm_updatedby' => $tokendata['uid']
                    ];
                    $insertdata = [
                        'srh_status_id' => 28,
                        'srh_serm_id' => $sr_id,
                        'srh_created_on' => $date,
                        'srh_created_by' => $tokendata['uid']
                    ];
                    $insertquote = [
                        'qtm_status_id' => $next_status,
                        'qtm_updated_by' => $tokendata['uid'],
                        'qtm_updated_on' => $date,
                    ];

                    $results1 = $servicerequestModel->update($this->db->escapeString($sr_id), $updtdata);
                    $toolhistid1 = $servicehistoryModel->insert($insertdata);
                    $quoteinsert = $quotemasterModel->update($this->db->escapeString($qtm_id), $insertquote);

                    $response = [
                        'ret_data' => 'success',
                        'sr_id' => $sr_id,
                        'status' => $servq_req_status,
                        'status_code' => $servq_req_status_code
                    ];

                    return $this->respond($response, 200);
                } else if ($next_status == 22) {
                    $updtdata = [
                        'serm_status' => 28,
                        'serm_updatedon' => $date,
                        'serm_updatedby' => $tokendata['uid']
                    ];
                    $insertdata = [
                        'srh_status_id' => 28,
                        'srh_serm_id' => $sr_id,
                        'srh_created_on' => $date,
                        'srh_created_by' => $tokendata['uid']
                    ];
                    $insertquote = [
                        'qtm_rejected_reason' => $this->request->getVar('rejected_reason'),
                        'qtm_status_id' => 22,
                        'qtm_updated_by' => $tokendata['uid'],
                        'qtm_updated_on' => $date,
                    ];
                    $results1 = $servicerequestModel->update($this->db->escapeString($sr_id), $updtdata);
                    $toolhistid1 = $servicehistoryModel->insert($insertdata);
                    $quoteinsert = $quotemasterModel->update($this->db->escapeString($this->request->getVar('qtm_id')), $insertquote);

                    $serm_data=$servicerequestModel->where('serm_id',$sr_id)->first();

                
                    $us_id = $userModel->where('us_delete_flag', 0)->findAll();
                    $ntf_data = [];
                   
                    foreach ($us_id as $eachurl) {
                     
                        $indata = [
                            'id' => $eachurl['us_id'],
                            'headers'=>"Quotation Rejected",
                            'content'=>"Quote Rejected  For " . $serm_data['serm_number'],
                            'sourceid' => $tokendata['uid'],
                            'destid' => $eachurl['us_id'],
                            'date' => $date,

                            'nt_request_type'=>0,
                            'nt_type_id'=>$serm_data['serm_id'],
                            'nt_type'=>0
                        ];
                        array_push($ntf_data, $indata);
                    }
                    $nt_id = $notificationmasterController->create_us_notification($ntf_data);

                    $response = [
                        'ret_data' => 'success',
                        'sr_id' => $sr_id,
                        'status' => $servq_req_status,
                        'status_code' => $servq_req_status_code
                    ];

                    return $this->respond($response, 200);
                } else {
                    $response = [
                        'ret_data' => 'success',
                        'sr_id' => $sr_id,
                        'status' => $servq_req_status,
                        'status_code' => $servq_req_status_code
                    ];
                }
                return $this->respond($response, 200);
            } else {
                $response['ret_data'] = 'success';
                $response['Message'] = 'No ID';
            }
        } else {

            if ($tr_id) {
                if ($status_id) {
                    if ($status_id == 3) {
                        $next_status = 4;
                    } else if ($status_id == 4) {
                        $next_status = 1;
                    } else if ($status_id == 1) {
                        if ($flag_id == 1) {
                            $next_status = 5;
                        } else {
                            $next_status = 8;
                        }
                    } else if ($status_id == 8) {
                        $next_status = 9;
                    } else if ($status_id == 9) {
                        if ($flag_id == 1) {
                            $next_status = 7;
                        } else {
                            $next_status = 18;
                        }
                    } else if ($status_id == 11) {
                        if ($flag_id == 1) {
                            $next_status = 5;
                        } else {
                            $next_status = 10;
                        }
                    } else if ($status_id == 10) {
                        $next_status = 12;
                    } else if ($status_id == 18) {
                        $next_status = 11;
                    } else if ($status_id == 12) {
                        if ($flag_id == 1) {
                            $next_status = 14;
                        } else {
                            $next_status = 13;
                        }
                    } else if ($status_id == 13) {
                        $next_status = 16;
                    } else if ($status_id == 14) {
                        $next_status = 5;
                    } else if ($status_id == 5) {
                        if ($flag_id == 1) {
                            $next_status = 15;
                        } else if ($flag_id == 2) {
                            $next_status = 8;
                        } else {
                            $next_status = 6;
                        }
                    } else if ($status_id == 15) {
                        $next_status = 6;
                    } else if ($status_id == 6) {
                        if ($flag_id == 1) {
                            $next_status = 8;
                        } else if ($flag_id == 2) {
                            $next_status = 10;
                        } else {
                            $next_status = 16;
                        }
                    } else if ($status_id == 16) {
                        $response['ret_data'] = 'error';
                        $response['Message'] = 'Tool Request Completed';
                        return $this->respond($response, 200);
                    } else if ($status_id == 18) {
                        $next_status = 18;
                    }

                    $tool_req_statusname = $statusmaster->select('sm_name,sm_code')->where('sm_id', $next_status)->findAll();
                    foreach ($tool_req_statusname as $tool_req_statusname) {
                        $tool_req_status = $tool_req_statusname['sm_name'];
                        $tool_req_status_code = $tool_req_statusname['sm_code'];
                    }
                    $updtdata = [
                        'tldt_status' => $next_status,
                        'tldt_updated_on' => $date,
                        'tldt_updated_by' => $tokendata['uid'],
                    ];
                    $insertdata = [
                        'trqh_status_id' => $next_status,
                        'trqh_tr_id' => $tr_id,
                        'trqh_created_on' => $date,
                        'trqh_created_by' => $tokendata['uid'],

                    ];

                    $results2 = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata);
                    if ($results2) {
                        $toolhistid2 = $ToolRequestHistoryModel->insert($insertdata);

                        if ($next_status == 10) {
                            $updtdata1 = [
                                'tldt_status' => 12, 'tldt_updated_on' => $date,
                                'tldt_updated_by' => $tokendata['uid'],
                            ];
                            $insertdata1 = [
                                'trqh_status_id' => 12,
                                'trqh_tr_id' => $tr_id,
                                'trqh_created_on' => $date,
                                'trqh_created_by' => $tokendata['uid'],

                            ];
                            $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                            $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                            $response['ret_data'] = 'success';
                        } else if ($next_status == 18) {

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
                                return $this->respond($response, 200);
                            }
                        } else if ($next_status == 14) {
                            $updtdata1 = [
                                'tldt_status' => 5, 'tldt_updated_on' => $date,
                                'tldt_updated_by' => $tokendata['uid'],
                            ];
                            $insertdata1 = [
                                'trqh_status_id' => 5,
                                'trqh_tr_id' => $tr_id,
                                'trqh_created_on' => $date,
                                'trqh_created_by' => $tokendata['uid'],
                            ];
                            $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                            $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                            $response['ret_data'] = 'success';
                        } else if ($next_status == 6) {
                            if ($flag_id == 1) {
                                $updtdata1 = [
                                    'tldt_paymt_flag' => 1,
                                    'tldt_status' => 8,
                                    'tldt_updated_on' => $date,
                                    'tldt_updated_by' => $tokendata['uid'],
                                ];
                                $insertdata1 = [
                                    'trqh_status_id' => 8,
                                    'trqh_tr_id' => $tr_id,
                                    'trqh_created_on' => $date,
                                    'trqh_created_by' => $tokendata['uid'],
                                ];
                                $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                                $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                            } else if ($flag_id == 2) {
                                $updtdata1 = [
                                    'tldt_paymt_flag' => 1,
                                    'tldt_status' => 11,
                                    'tldt_updated_on' => $date,
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
                            } else if ($flag_id == 3) {
                                $updtdata1 = [
                                    'tldt_paymt_flag' => 1,
                                    'tldt_advpaymt_flag' => 0,
                                    'tldt_cost' => $flag_id = $this->request->getVar('cst_adv'),
                                    'tldt_status' => 8,
                                    'tldt_updated_on' => $date,
                                    'tldt_updated_by' => $tokendata['uid'],

                                ];
                                $insertdata1 = [
                                    'trqh_status_id' => 8,
                                    'trqh_tr_id' => $tr_id,
                                    'trqh_created_on' => $date,
                                    'trqh_created_by' => $tokendata['uid'],
                                ];

                                $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                                $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                            } else {
                                $updtdata1 = [
                                    'tldt_paymt_flag' => 1,
                                    'tldt_status' => 16,
                                    'tldt_updated_on' => $date,
                                    'tldt_updated_by' => $tokendata['uid'],
                                ];
                                $insertdata1 = [
                                    'trqh_status_id' => 16,
                                    'trqh_tr_id' => $tr_id,
                                    'trqh_created_on' => $date,
                                    'trqh_created_by' => $tokendata['uid'],
                                ];

                                $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                                $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                            }


                            $response['ret_data'] = 'success';
                        } else if ($next_status == 13) {
                            $updtdata1 = [
                                'tldt_status' => 16, 'tldt_updated_on' => $date,
                                'tldt_updated_by' => $tokendata['uid'],
                            ];
                            $insertdata1 = [
                                'trqh_status_id' => 16,
                                'trqh_tr_id' => $tr_id,
                                'trqh_created_on' => $date,
                                'trqh_created_by' => $tokendata['uid'],
                            ];
                            $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                            $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                            $response['ret_data'] = 'success';
                        } else if ($next_status == 8) {
                            $updtdata1 = [
                                'tldt_status' => 8, 'tldt_updated_on' => $date,
                                'tldt_updated_by' => $tokendata['uid'],
                            ];
                            $insertdata1 = [
                                'trqh_status_id' => 8,
                                'trqh_tr_id' => $tr_id,
                                'trqh_created_on' => $date,
                                'trqh_created_by' => $tokendata['uid'],
                            ];
                            $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                            $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                            $response['ret_data'] = 'success';
                        } else {
                            $return_data = [
                                'next_status' => $next_status,
                                'status_name' => $tool_req_status,
                                'status_code' => $tool_req_status_code
                            ];
                            $response = [
                                'ret_data' => 'success',
                                'status_data' => $return_data,
                                'tool_Req_id' => $tr_id
                            ];
                        }
                    }


                    return $this->respond($response, 200);
                } else {
                    $response['Message'] = 'No status id';
                }
            } else {
                $response['Message'] = 'No tool request id';
            }
            return $this->respond($response, 200);
        }
    }
}
