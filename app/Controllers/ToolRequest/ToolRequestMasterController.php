<?php

namespace App\Controllers\ToolRequest;

use App\Controllers\Payment\PaymentMasterController;
use App\Controllers\Shipment\ShipmentMasterController;
use App\Controllers\Status\StatusMasterController;
use App\Controllers\System\UsersNotificationController;
use App\Models\Approval\ApprovalmasterModel;
use App\Models\Coupon\CouponTrackerModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolDetailsModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Media\RequestMediaModel;
use App\Models\Payment\PaymentHistoryModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\Sequence\SequenceGeneratorModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\Shipment\ShipmentMasterModel;
use App\Models\Shipment\ShipmentTrackingModel;
use App\Models\StatusMaster\StatusMasterModel;
use App\Models\System\CatsalesHistoryModel;
use App\Models\System\CustomerDiscountModel;
use App\Models\System\ExpenseTrackerModel;
use App\Models\System\NotificationmasterModel;
use App\Models\System\ToolTrackerModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use App\Models\ToolRequest\ToolRequestTrackerModel;
use App\Models\User\UsersModel;
use Config\Commonutils;
use Config\Validation;
use CodeIgniter\HTTP\Request;
use CodeIgniter\I18n\Time;

class ToolRequestMasterController extends ResourceController
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
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }

        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $result = $toolrequestmasterModel
            ->join('customer_master', 'cstm_id=tldt_cstm_id')
            ->join('tool_details', 'tool_id=tldt_tool_id')
            ->join('status_master', 'sm_id=tldt_status')
            ->orderBy('tldet_id', 'desc')
            ->findAll();

        $response = $result
            ?
            [
                'ret_data' => 'success',
                'result' => $result
            ]
            :
            [
                'ret_data' => 'success',
                'result' => $result

            ];

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
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $ToolRequesttrackerModel = new ToolRequestTrackerModel();
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $expensetrackerModel = new ExpenseTrackerModel();
        $coupontrackerModel = new CouponTrackerModel();
        $ShipmentmasterModel = new ShipmentMasterModel();
        $ShipmenttrackerModel = new ShipmentTrackingModel();
        $requestMediaModel = new RequestMediaModel();
        $servicerequestdetailsModel = new ServiceRequestMasterModel();
        $customerdiscountModel = new CustomerDiscountModel();
        $paymenthistoryModel = new PaymentHistoryModel();


        $tr_det = array();
        $tr_det = $ToolRequestDetailsModel
            ->where('tldet_id', base64_decode($id))
            ->join('tool_details', 'tool_id=tldt_tool_id')
            ->join('status_master', 'sm_id=tldt_status')
            ->join('customer_master', 'cstm_id=tldt_cstm_id')
            ->first();


        if ($tr_det) {

            $payment_history=$paymenthistoryModel->where('rph_type',1)->where('rph_rq_id',base64_decode($id))
            ->whereIn('rph_status',[2,3])
            ->orderby('rph_id','desc')->findall();
            $tr_det['payment_history']=sizeof($payment_history)>0?$payment_history:[];
            if ($tr_det['tldt_pmd_flag'] == 1) {
                $tr_det['coupon_id'] = 0;
                $tr_det['customer_dicounts'] = $tr_det['cstm_type'] == 1 ? $customerdiscountModel->where('cd_active_flag', 0)->first() : [];

                if ($tr_det['customer_dicounts']['cd_request_type'] == 3 || $tr_det['customer_dicounts']['cd_request_type'] == 1) {

                    if ($tr_det['customer_dicounts']['cd_type'] == 1) {
                        $discount_amount = (($tr_det['tldt_rent_cost'] * $tr_det['customer_dicounts']['cd_rate']) / 100);
                    } else {
                        $discount_amount = ($tr_det['customer_dicounts']['cd_rate']);
                    }
                    $updated_total_amount = $tr_det['tldt_rent_cost'] - $discount_amount;

                    $tr_det['customer_dicounts']['discount_amount'] = $discount_amount;
                    $tr_det['customer_dicounts']['updated_total_amount'] = $updated_total_amount;
                }
            } else {
                $tr_det['customer_dicounts'] = [];
                $coupons = $coupontrackerModel->where('ct_coup_rqtype', 0)
                    ->where('ct_coup_rqid', base64_decode($id))
                    ->first();
                if ($coupons) {
                    $tr_det = $tr_det + $coupons;
                } else {
                    $tr_det['coupon_id'] = 0;
                }
            }



            $tr_det['Service_request_details'] = $tr_det['tldt_reference'] != 0 ?
                $servicerequestdetailsModel->where('serm_id', $tr_det['tldt_reference'])->first() : 0;


            $medias = $requestMediaModel
                ->where('rmedia_delete_flag', 0)
                ->where('rmedia_url_type', 1)
                ->where('rmedia_request_id', base64_decode($id))
                ->findAll();
            $tr_det['usr_tool_images'] = sizeof($medias) > 0 ?
                $medias :
                [];


            $ship_Det = $ShipmentmasterModel
                ->where('shm_by_type', 0)
                ->where('shm_type', 0)
                ->where('shm_request_id', base64_decode($id))
                ->orderBy('shm_id', 'desc')
                ->first();

            if ($ship_Det) {
                $trackship = $ShipmenttrackerModel
                    ->where('shtrack_shm_id', $ship_Det['shm_id'])
                    ->findAll();

                $ship_Det['tracking_data'] = $trackship ? $trackship : [];

                $tr_det['shipment_details'] = $ship_Det;
            }

            $ship_Detcust = $ShipmentmasterModel
                ->where('shm_by_type', 1)
                ->where('shm_type', 0)
                ->where('shm_request_id', base64_decode($id))
                ->orderBy('shm_id', 'desc')
                ->first();

            if ($ship_Detcust) {
                $trackship_cust = $ShipmenttrackerModel
                    ->where('shtrack_shm_id', $ship_Detcust['shm_id'])
                    ->findAll();

                $ship_Det['tracking_data'] = $trackship_cust ? $trackship_cust : [];

                $tr_det['shipment_details_customer'] = $ship_Detcust;
            }
            
            $request = $paymentTrackerModel
                ->where('rpt_type', 2)
                ->where("rpt_reqt_id", base64_decode($id))
                ->orderBy('rpt_id', 'desc')
                ->first();
            if ($request) {
                $tr_det = $tr_det + $request;
            }
        }

        if ($tr_det['tldt_due_date'] != null) {
            $tldt_due_date = Time::createFromFormat('Y-m-d H:i:s', $tr_det['tldt_due_date']);
            $current_date = Time::now();
            if ($current_date->isAfter($tldt_due_date)) {
                $time_difference_seconds = $current_date->getTimestamp() - $tldt_due_date->getTimestamp();
                $time_difference_minutes = floor($time_difference_seconds / 60);
                $time_difference_hours = floor($time_difference_minutes / 60);
                $time_difference_days = floor($time_difference_hours / 24);
                $tr_det['due_days'] = (string) $time_difference_days;
                $d_price = ($time_difference_days * $tr_det['tool_rent_cost'] * $tr_det['tldt_tool_quant'] );
                $tr_det['due_rent_price']=(string) $d_price+($d_price*$tr_det['tool_delay_percentage'])/ 100;
            } else {
                $time_difference_seconds = $tldt_due_date->getTimestamp() - $current_date->getTimestamp();
                $time_difference_minutes = floor($time_difference_seconds / 60);
                $time_difference_hours = floor($time_difference_minutes / 60);
                $time_difference_days = floor($time_difference_hours / 24);
                $tr_det['expected_Days'] = (string) $time_difference_days;
            }
        }

        if ($tr_det) {
            $approvalMasterModel = new ApprovalmasterModel();
            $approval_Data_hold = $approvalMasterModel
                ->where('am_reqid', base64_decode($id))
                ->where('am_type', 3)
                ->orderBy('am_id', 'desc')->first();
            if ($approval_Data_hold) {
                $approval_Data = $approval_Data_hold;
            } else {
                $approval_Data['am_id'] = '0';
            }



            $cstm_id = $tr_det['tldt_cstm_id'];
            if ($tr_det['tldt_status'] == 7) {
                $trt_type = 1;
            } else if ($tr_det['tldt_status'] == 42) {
                $trt_type = 2;
            } else {
                $trt_type = 0;
            }
            $track_data = $ToolRequesttrackerModel
                ->where('trt_rq_id',  base64_decode($id))
                ->where('trt_type', $trt_type)
                ->first();
            if (!$track_data) {
                $track_data = 0;
            }
            if ($tr_det['tool_adv_payment'] != 0) {
                $tr_det['tool_advance_price'] = ($tr_det['tool_rent_cost'] * $tr_det['tool_adv_price']) / 100;
                $tr_det['tool_price_after_adv'] = ($tr_det['tldt_cost'] - $tr_det['tool_advance_price']);
            }
            if ($tr_det['tool_deposit_id'] != 0) {
                $tr_det['tool_dep_price'] = ($tr_det['tool_rent_cost'] * $tr_det['tool_deposit_price']) / 100;
                $tr_det['tool_price_after_deposit'] = ($tr_det['tldt_cost'] - $tr_det['tool_deposit_price']);
            }

            $additional_expenses = $expensetrackerModel
                ->where('expt_type', 1)
                ->where('expt_rq_id', base64_decode($id))
                ->findAll();
            $additional_expenses = (sizeof($additional_expenses) > 0) ? $additional_expenses : 0;
            $tr_det['additional_expenses'] = $additional_expenses;


            $response = [
                'ret_data' => 'success',
                'req_list' => $tr_det,
                'track_data' => $track_data,
                'approval_Data' => $approval_Data,
                'imageurl'=>getenv('AWS_URL')
            ];
        } else {
            $response['Message'] = 'No Request id';
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
    }


    public function tool_req_create()
    {

        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $expensetrackerModel = new ExpenseTrackerModel();
        $coupontrackerModel = new CouponTrackerModel();
        $notificationmasterController = new UsersNotificationController;
        $userModel = new UsersModel();
        $custModel = new CustomerMasterModel();

        $seqModel = new SequenceGeneratorModel();
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
            'cust_id' => 'required',
            'tool_id' => 'required',
            'tool_rent_quantity' => 'required',
            'tool_rent' => 'required',
            'total_amount' => 'required',
            'advp_id' => 'required',
            'cust_address' => 'required',
            'tldt_created_on' => 'required',

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");

        $seq = $seqModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_TR" . $seq[0]['toolreq_sequence']);
        $insert_tool_data = [
            'tldt_cstm_id' =>  $this->request->getVar('cust_id'),
            'tldt_tool_id' => $this->request->getVar('tool_id'),
            'tldt_number' => $nextval,
            'tldt_tool_quant' => $this->request->getVar('tool_rent_quantity'),
            'tldt_tool_duration' => $this->request->getVar('tool_rent'),
            'tldt_cost' => $this->request->getVar('total_amount'),
            'tldt_delivery_address' => $this->request->getVar('cust_address'),
            'tldt_status' => 2,
            'tldt_active_flag' => 0,
            'tldt_advpaymt_flag' => $this->request->getVar('advp_id'),
            'tldt_paymt_flag' => 1,
            'tldt_created_on' => $date,
            'tldt_created_by' => $tokendata['uid'],
            'tldt_updated_on' => $date,
            'tldt_updated_by' => $tokendata['uid'],
            'tldt_rent_cost' => $this->request->getVar('tool_rent_cost'),
            'tldt_pmd_flag' => $this->request->getVar('tldt_pmd_flag'),
        ];
        $toolid = $ToolRequestDetailsModel->insert($insert_tool_data);

        $tooltrackerModel = new ToolTrackerModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $req_data = $ToolDetailsModel->where('tool_id', $this->request->getVar('tool_id'))->first();
        $revised_master = [
            'tool_rent_quantity' => ($req_data['tool_rent_quantity']) - ($this->request->getVar('tool_rent_quantity')),
            'tool_total_quantity' => ($req_data['tool_total_quantity']) - ($this->request->getVar('tool_rent_quantity')),
            'tool_updated_on' => $date,
        ];
        $revised_data = [
            'trk_tool_id' => $this->request->getVar('tool_id'),
            'trk_type' => 1,
            'trk_status' => 2,
            'trk_rq_id' => $toolid,
            'trk_created_by' => $tokendata['uid'],
            'trk_quant' => $this->request->getVar('tool_rent_quantity'),
            'trk_updated_by' => $tokendata['uid'],
            'trk_updated_on' => $date,
        ];

// <--------------------------------------->
        // To be Pushed


    //     $req_data = $ToolDetailsModel->where('tool_id', $this->request->getVar('tool_id'))->first();
        
    //     $tool_r_quantity = ($req_data['tool_rent_quantity']) - ($this->request->getVar('tool_rent_quantity'));
    //     $tool_t_quantity =($req_data['tool_total_quantity']) - ($this->request->getVar('tool_rent_quantity'));
    //     $tool_u_on = $date;
    
    
    // $t_data=[
    //     'tool_rent_quantity'=>$tool_r_quantity,
    //     'tool_total_quantity'=>$tool_t_quantity,
    //     'tool_updated_on'=>$tool_u_on,
    //     'trk_tool_id' => $this->request->getVar('tool_id'),
    //     'trk_type' => 1,
    //     'trk_status' => 2,
    //     'trk_rq_id' => $toolid,
    //     'trk_created_by' => $tokendata['uid'],
    //     'trk_quant' => $this->request->getVar('tool_rent_quantity'),
    //     'trk_updated_by' => $tokendata['uid'],
    //     'trk_updated_on' => $date,
    // ];

    // $id_data=$toolMasterController->update_tool_stock($t_data);


    
    // <--------------------------------------->



        
        $ToolDetailsModel->update(($this->request->getVar('tool_id')), $revised_master);
        $trk_id = $tooltrackerModel->insert($revised_data);

        if ($this->request->getVar('expense_cost')) {
            $add_exp = [
                'expt_type' => 2,
                'expt_rq_id' => $toolid,
                'expt_name' => $this->request->getVar('expense_name'),
                'expt_cost' => $this->request->getVar('expense_cost'),
                'expt_created_by' => $tokendata['uid'],
                'expt_created_on' => $date,
                'expt_updated_by' => $tokendata['uid'],
                'expt_updated_on' => $date,

            ];
            $exp_data = $expensetrackerModel->insert($add_exp);
        }
        if (($this->request->getVar('coupon_id')) != null) {
            $ctrack = [
                'ct_coup_id' => $this->request->getVar('coupon_id'),
                'ct_coup_rqid' => $toolid,
                'ct_cstm_id' => $tokendata['uid'],
                'ct_cost' => $this->request->getVar('discount'),
                'ct_bf_cost' => $this->request->getVar('coupon_bf_cost'),
                'ct_af_cost' => $this->request->getVar('total_amount'),
                'ct_created_by' => $tokendata['uid'],
                'ct_created_on' => $date,
                'ct_updated_by' => $tokendata['uid'],
                'ct_updated_on' => $date,

            ];
            $coupontrackerModel->insert($ctrack);
        }
        if ($toolid) {
            $histdata = [
                'trqh_tr_id' => $toolid,
                'trqh_status_id' => 17,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $toolhistid = $ToolRequestHistoryModel->insert($histdata);
            if ($toolhistid) {
                $updt_data = [
                    'tldt_status' => 2,
                    'tldt_updated_on' => $date,
                    'tldt_updated_by' => $tokendata['uid'],
                ];
                $updt_datahist = [
                    'trqh_tr_id' => $toolid,
                    'trqh_status_id' => 2,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
                $toolhistid = $ToolRequestHistoryModel->insert($updt_datahist);

                $tlrq_data = $ToolRequestDetailsModel->where('tldet_id', $toolid)->first();



                $us_id = $userModel->where('us_delete_flag', 0)->findAll();
                $ntf_data = [];
               
                foreach ($us_id as $eachurl) {
                 
                    $indata = [
                        'id' => $eachurl['us_id'],
                        'headers' => "New Rent Request",
                        'content' => "New Tool Rent Request Created " . $tlrq_data['tldt_number'] ,
                        'sourceid' => $tokendata['uid'],
                        'destid' => $eachurl['us_id'],
                        'date' => $date,
                        'nt_request_type'=>1,
                        'nt_type_id'=>$tlrq_data['tldet_id'],
                        'nt_type'=>0
                    ];
                    array_push($ntf_data, $indata);
                }
                $nt_id = $notificationmasterController->create_us_notification($ntf_data);

                if ($toolhistid) {
                    $seq = (intval($seq[0]['toolreq_sequence']) + 1);
                    $seq_data = ['toolreq_sequence' => $seq];
                    $seqModel->update(1, $seq_data);
                    $response = [
                        'ret_data' => 'success',
                        'id' => $toolid,
                    ];
                    return $this->respond($response, 200);
                } else {
                    $response['Message'] = 'Error in insertion in history';
                }
            } else {
                // If there was an error during insertion, set an error message.
                $response['message'] = 'Error in history insertion';
                return $this->respond($response, 200);
            }
        } else {
            $response['message'] = 'Error in insertion  Data';
            return $this->respond($response, 200);
        }
    }


    public function get_pend_tr()
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
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $ToolRequesttrackerModel = new ToolRequestTrackerModel();
        $tr_det = array();
        $tr_det = $ToolRequestDetailsModel
            ->whereIn('tldt_status', [2, 37])
            ->join('tool_details', 'tool_id=tldt_tool_id')
            ->join('customer_master', 'cstm_id=tldt_cstm_id')
            ->findAll();
        if ($tr_det) {

            for ($i = 0; $i < sizeof($tr_det); $i++) {
                if ($tr_det[$i]['tool_adv_payment'] != 0) {
                    $tr_det[$i]['tool_advance_price'] = ($tr_det[$i]['tldt_cost'] * $tr_det[$i]['tool_adv_price']) / 100;
                    $tr_det[$i]['tool_price_after_adv'] = ($tr_det[$i]['tldt_cost'] * $tr_det[$i]['tool_advance_price']);
                }
            }
            $response = [
                'ret_data' => 'success',
                'req_list' => $tr_det
            ];
        } else {
            $response['Message'] = 'No Request id';
        }
        return $this->respond($response, 200);
    }

    public function tool_req_accept()
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
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $ToolRequesttrackerModel = new ToolRequestTrackerModel();
        $custModel = new CustomerMasterModel();
        $notificationmasterController = new UsersNotificationController;
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $paymenthistoryModel = new PaymentHistoryModel();
        $req_data = $this->request->getVar('data');

        $date = date('Y-m-d H:i:s');



        if ($this->request->getVar('flag')) {
            $data1 = [
                'tldt_status' => 7,
                'tldt_active_flag' => 0,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],


            ];
            $histdata1 = [
                'trqh_tr_id' => $req_data->tldet_id,
                'trqh_status_id' => 7,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],

            ];
            $track = [
                'trt_rq_id' => $req_data->tldet_id,
                'trt_type' => 1,
                'trt_notes' => $this->request->getVar('rejectreason'),
                'trt_created_by' => $tokendata['uid'],
                'trt_created_on' => $date,
                'trt_updated_by' => $tokendata['uid'],
                'trt_updated_on' => $date,
            ];
            $results1 = $ToolRequestDetailsModel->update(($req_data->tldet_id), $data1);
            $toolhistid1 = $ToolRequestHistoryModel->insert($histdata1);
            $tool_det = $ToolRequestDetailsModel->where('tldet_id', $req_data->tldet_id)->first();

            $tooltrackerModel = new ToolTrackerModel();
            $ToolDetailsModel = new ToolDetailsModel();
            $reqmaster_data = $ToolDetailsModel->where('tool_id', $req_data->tool_id)->first();
            $revised_master = [
                'tool_rent_quantity' => ($reqmaster_data['tool_rent_quantity']) + ($req_data->tool_rent_quantity),
                'tool_total_quantity' => ($reqmaster_data['tool_total_quantity']) + ($req_data->tool_rent_quantity),
                'tool_updated_on' => $date,
            ];
            $revised_data = [
                'trk_tool_id' => $req_data->tool_id,
                'trk_type' => 1,
                'trk_status' => 7,
                'trk_rq_id' => $req_data->tldet_id,
                'trk_quant' => $req_data->tool_rent_quantity,
                'trk_created_by' => $tokendata['uid'],
                'trk_created_on' => $date,
                'trk_updated_by' => $tokendata['uid'],
                'trk_updated_on' => $date,
            ];
            $ToolDetailsModel->update(($req_data->tool_id), $revised_master);
            $trk_id = $tooltrackerModel->insert($revised_data);

            $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
            $player_id = [];
            $custhead = "CATRAMS- Request Rejected!!!";
            $custcontent = "" . $tool_det['tldt_number'] . "-Your Request for Tool has been Rejected.Sorry for the inconvienences.";
            array_push($player_id, $target_cust['fcm_token_mobile']);
            if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

            if ($ret_res) {
                $notif_data = [
                    'id'=>$tool_det['tldt_cstm_id'],
                    'sourceid' => $tokendata['uid'],
                    'destid' => $tool_det['tldt_cstm_id'],
                    'nt_req_number' => $tool_det['tldt_number'],

                    'nt_sourcetype' => 1,
                    'headers' => $custhead,
                    'content' => $custcontent,
                    'date' => $date,
                    'nt_type'=>0,
                    'nt_request_type'=>1,
                    'nt_type_id'=>$tool_det['tldet_id']
                ];
                $notificationmasterController->create_cust_notification($notif_data);
            }

            $trackres = $ToolRequesttrackerModel->insert($track);
            if ($results1 && $toolhistid1 && $trackres) {
                $response = [
                    'ret_data' => 'success',
                    'Message' => 'Request Rejected'
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'Message' => 'Request error'
                ];
                return $this->respond($response, 200);
            }
        } else {


            if ($req_data->tool_adv_payment == 1 || $req_data->tool_deposit_id == 1) {
                $data1 = [
                    'tldt_status' => 5,
                    'tldt_active_flag' => 0,
                ];
                $histdata1 = [
                    'trqh_tr_id' => $req_data->tldet_id,
                    'trqh_status_id' => 5,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
                $results1 = $ToolRequestDetailsModel->update($req_data->tldet_id, $data1);
                $toolhistid1 = $ToolRequestHistoryModel->insert($histdata1);
                if ($req_data->tool_deposit_id == 1) {
                    $payMast = [
                        'rpt_reqt_id' => $req_data->tldet_id,
                        'rpt_type' => 2,
                        'rpt_amount' => $req_data->deposit_price,
                        'rpt_status' => 0,
                        'rpt_cust_id' => $req_data->tldt_cstm_id,
                        'rpt_created_on' => $date,
                        'rpt_created_by' => $tokendata['uid'],
                        'rpt_updated_on' => $date,
                        'rpt_updated_by' => $tokendata['uid'],
                    ];
                    $pay_d = [
                        'rph_type' => 1,
                        'rph_rq_id' => $req_data->tldet_id,
                        'rph_status' => 0,
                        'rph_amount' => $req_data->deposit_price,
                        'rph_created_on' => $date,
                        'rph_created_by' => $tokendata['uid'],

                    ];
                } else {
                    $payMast = [
                        'rpt_reqt_id' => $req_data->tldet_id,
                        'rpt_type' => 2,
                        'rpt_amount' => $req_data->advance_price,
                        'rpt_status' => 0,
                        'rpt_cust_id' => $req_data->tldt_cstm_id,
                        'rpt_created_on' => $date,
                        'rpt_created_by' => $tokendata['uid'],
                        'rpt_updated_on' => $date,
                        'rpt_updated_by' => $tokendata['uid'],
                    ];
                    $pay_d = [
                        'rph_type' => 1,
                        'rph_rq_id' => $req_data->tldet_id,
                        'rph_status' => 0,
                        'rph_amount' => $req_data->advance_price,
                        'rph_created_on' => $date,
                        'rph_created_by' => $tokendata['uid'],
                    ];
                }

                $paymentTrackerModel->insert($payMast);
                $paymenthistoryModel->insert($pay_d);
                $tool_det = $ToolRequestDetailsModel->where('tldet_id', $req_data->tldet_id)->first();
                $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();

                $player_id = [];
                $custhead = "CATRAMS Tool Request Accepted!!!";
                $custcontent = "" . $tool_det['tldt_number'] . "-Your Request for Tool has been accepted.Tap to pay.";
                array_push($player_id, $target_cust['fcm_token_mobile']);
                if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                if ($ret_res) {
                    $notif_data = [
                        'sourceid' => $tokendata['uid'],
                        'destid' => $tool_det['tldt_cstm_id'],
                        'nt_req_number' => $tool_det['tldt_number'],
                        'id'=>$tool_det['tldt_cstm_id'],
    
                        'nt_sourcetype' => 1,
                        'headers' => $custhead,
                        'content' => $custcontent,
                        'date' => $date,
                        'nt_type'=>0,
                        'nt_request_type'=>1,
                        'nt_type_id'=>$tool_det['tldet_id']
                    ];
                    $notificationmasterController->create_cust_notification($notif_data);
                }

                $response = [
                    'ret_data' => 'success',
                    'Message' => 'Request Proceeded for Advance Payment'
                ];
            } else {

                $histdata1 = [
                    'trqh_tr_id' => $req_data->tldet_id,
                    'trqh_status_id' => 1,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
                $data2 = [
                    'tldt_status' => 5,
                    'tldt_active_flag' => 1,
                    'tldt_updated_on' => $date,
                    'tldt_updated_by' => $tokendata['uid'],
                ];
                $histdata2 = [
                    'trqh_tr_id' => $req_data->tldet_id,
                    'trqh_status_id' => 5,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];

                $toolhistid1 = $ToolRequestHistoryModel->insert($histdata1);
                $results1 = $ToolRequestDetailsModel->update($req_data->tldet_id, $data2);
                $toolhistid2 = $ToolRequestHistoryModel->insert($histdata2);

                $payMast = [
                    'rpt_reqt_id' => $req_data->tldet_id,
                    'rpt_type' => 2,
                    'rpt_amount' => $req_data->tldt_cost,
                    'rpt_status' => 0,
                    'rpt_cust_id' => $req_data->tldt_cstm_id,
                    'rpt_created_on' => $date,
                    'rpt_created_by' => $tokendata['uid'],
                    'rpt_updated_on' => $date,
                    'rpt_updated_by' => $tokendata['uid'],
                ];
                $pay_d = [
                    'rph_type' => 1,
                    'rph_rq_id' => $req_data->tldet_id,
                    'rph_status' => 0,
                    'rph_amount' => $req_data->tldt_cost,
                    'rph_created_on' => $date,
                    'rph_created_by' => $tokendata['uid'],
                ];
                $paymentTrackerModel->insert($payMast);
                $paymenthistoryModel->insert($pay_d);

                $tool_det = $ToolRequestDetailsModel->where('tldet_id', $req_data->tldet_id)->first();

                $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                $player_id = [];
                $custhead = "CATRAMS Tool Request Accepted!!!";
                $custcontent = "" . $tool_det['tldt_number'] . "-Your Request for Tool has been accepted.Tap to pay or pay later.";
                array_push($player_id, $target_cust['fcm_token_mobile']);
                if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                if ($ret_res) {
                    $notif_data = [
                        'sourceid' => $tokendata['uid'],
                        'destid' => $tool_det['tldt_cstm_id'],
                        'nt_req_number' => $tool_det['tldt_number'],
                        'id'=>$tool_det['tldt_cstm_id'],
    
                        'nt_sourcetype' => 1,
                        'headers' => $custhead,
                        'content' => $custcontent,
                        'date' => $date,
                        'nt_type'=>0,
                        'nt_request_type'=>1,
                        'nt_type_id'=>$tool_det['tldet_id']
                    ];
                    $notificationmasterController->create_cust_notification($notif_data);
                }
                $response = [
                    'ret_data' => 'success',
                    'Message' => 'Request Accepted'
                ];
            }
        }

        if ($notif_data) {

            return $this->respond($response, 200);
        } else {
            return $this->fail('fail', 400);
        }
    }

    public function tool_req_list()
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

        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $rentDelayController=new RentDelayCalcController;
        $tool_pay_pend = 0;
        $pending_tool = 0;
        $tool_open = 0;
        if ($this->request->getVar('cstm_id') != null) {

            $result = $toolrequestmasterModel
                ->where('tldt_cstm_id', $this->request->getVar('cstm_id'))
                ->where('tldt_status!=', 16)
                ->where('tldt_status!=', 7)
                ->join('customer_master', 'cstm_id=tldt_cstm_id')

                ->join('tool_details', 'tool_id=tldt_tool_id')
                ->join('status_master', 'sm_id=tldt_status')
                ->orderBy('tldet_id', 'desc')
                ->findAll();
        } else {

            $result = $toolrequestmasterModel
                ->select('tldet_id,
                tldt_pmd_flag,
                tldt_status,
                tldt_due_date,
                tldt_tool_quant,
                tool_delay_percentage,
                tool_cost,
                tool_rent_cost,
                tldt_reference,
                tldt_updated_on,
                tldt_number,
                sm_name,
                sm_pk_id,
                tool_name,
                sm_code,
                cstm_name,
                cstm_id')
                ->where('tldt_status!=', 16)
                ->join('customer_master', 'cstm_id=tldt_cstm_id')
                ->join('tool_details', 'tool_id=tldt_tool_id')
                ->join('status_master', 'sm_id=tldt_status')
                ->orderBy('tldet_id', 'desc')
                ->findAll();
            // return $this->respond($result, 200);
        }



        if ($result) {



            for ($i = 0; $i < sizeof($result); $i++) {



                if ($result[$i]['tldt_status'] == 17 || $result[$i]['tldt_status'] == 2) {
                    $tool_open = $tool_open + 1;
                } else if ($result[$i]['tldt_status'] == 3 || $result[$i]['tldt_status'] == 5 || $result[$i]['tldt_status'] == 15 || $result[$i]['tldt_status'] == 42 || $result[$i]['tldt_status'] == 48) {
                    $tool_pay_pend = $tool_pay_pend + 1;
                } else {
                    $pending_tool = $pending_tool + 1;
                }

                if ($result[$i]['tldt_due_date']) {

                    

                    $delay_result=$rentDelayController->calculate_due_req($result[$i]);
                    $result[$i]['due_days']=$delay_result['due_days']?$delay_result['due_days']:null;
                    $result[$i]['due_rent_price']=$delay_result['due_rent_price']?$delay_result['due_rent_price']:null;
                    $result[$i]['expected_Days'] =$delay_result['expected_Days']?$delay_result['expected_Days']:null;
                    
                }

                $pay_data[$i] = $paymentTrackerModel->where('rpt_type', 0)->where('rpt_reqt_id', $result[$i]['tldet_id'])->first();

                if ($pay_data[$i]) {
                    array_merge($result[$i], $pay_data[$i]);
                }
            }
            $count_list['tool_open'] = $tool_open;
            $count_list['tool_pay_pend'] = $tool_pay_pend;
            $count_list['pending_tool'] = $pending_tool;


            $response = [
                'ret_data' => 'success',
                'result' => $result,
                'count_list' => $count_list
            ];
        } else {
            $response['Message'] = 'No tool request ';
        }
        return $this->respond($response, 200);
    }

    public function tool_req_history()
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
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $statusmaster = new StatusMasterModel();
        $CustomerMasterModel = new CustomerMasterModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $cs_id = $this->request->getVar('cstm_id');
        if ($cs_id) {
            $tool_req_details = $ToolRequestDetailsModel->where('tldt_status', 7)
                ->where('tldt_cstm_id', $cs_id)
                ->orderBy('tldet_id', 'desc')
                ->get()
                ->getResult();
        } else {
            $tool_req_details = $ToolRequestDetailsModel->where('tldt_status', 7)->get()->getResult();
        }

        if ($tool_req_details) {
            for ($i = 0; $i < sizeof($tool_req_details); $i++) { {
                    # Extract various attributes from the tool request detail.
                    $tool_id = $tool_req_details[$i]->tldt_tool_id;
                    $tool_req_id = $tool_req_details[$i]->tldet_id;
                    $tool_duration = $tool_req_details[$i]->tldt_tool_duration;
                    $tool_rent_quantity_from_cust = $tool_req_details[$i]->tldt_tool_quant;
                    $tool_del_address = $tool_req_details[$i]->tldt_delivery_address;
                    $tool_cost_from_cust = $tool_req_details[$i]->tldt_cost;
                    $tool_req_created_on = $tool_req_details[$i]->tldt_created_on;
                    $tldt_adv_cost = $tool_req_details[$i]->tldt_adv_cost;
                    $tool_req_last_updated_on = $tool_req_details[$i]->tldt_updated_on;
                    $tldt_number = $tool_req_details[$i]->tldt_number;
                    $tool_req_statusid = $tool_req_details[$i]->tldt_status;
                    $tool_req_statusname = $statusmaster->select('sm_name')->where('sm_id', $tool_req_statusid)->findAll();
                    $tool_req_statusname = $tool_req_statusname[0];
                    $tool_req_status = $tool_req_statusname['sm_name'];
                    # Retrieve tool data based on the 'tool_id'.
                    $tool_data = $ToolDetailsModel->select('tool_name,tool_adv_price,tool_delay_percentage,tool_discount,tool_description,tool_cost,tool_rent_quantity,tool_sale_quantity,tool_adv_payment,tool_rent_id')->where('tool_id', $tool_id)->findAll();
                    # Loop through each tool data.
                    if (!empty($tool_data)) {
                        # Extract attributes from the first item in $tool_data.
                        $first_tool_data = $tool_data[0];
                        $tool_name = $first_tool_data['tool_name'];
                        $tool_cost = $first_tool_data['tool_cost'];
                        $tool_rent_quantity = $first_tool_data['tool_rent_quantity'];
                        $tool_sale_quantity = $first_tool_data['tool_sale_quantity'];
                        $tool_description = $first_tool_data['tool_description'];
                        $tool_adv_payment = $first_tool_data['tool_adv_payment'];
                        $tool_rent_id = $first_tool_data['tool_rent_id'];
                        $tool_advance_price = $first_tool_data['tool_adv_price'];
                        $tool_discount = $first_tool_data['tool_discount'];
                        $tool_delay_percentage = $first_tool_data['tool_delay_percentage'];
                        $tldt_adv_cost = $first_tool_data['tldt_adv_cost'];
                    }
                    $tool_adv_price = (($tool_cost_from_cust) * ($tool_advance_price)) / 100;
                    $tool_price_after_adv = ($tool_cost_from_cust) - ($tool_adv_price);
                    # Extract the customer ID associated with the tool request detail.
                    if ($cs_id) {
                        $cstm_id = $cs_id;
                    } else {
                        $cstm_id = $tool_req_details[$i]->tldt_cstm_id;
                    }

                    # Retrieve customer data based on the 'cstm_id'.
                    $cust_data = $CustomerMasterModel->select('cstm_name,cstm_address,cstm_city,cstm_state,cstm_phone,cstm_email, cstm_cstp_id')->where('cstm_id', $cstm_id)->findAll();
                    # Loop through each customer data.
                    foreach ($cust_data as $cust_data) {
                        # Extract various attributes from the customer data.
                        $cstm_name = $cust_data['cstm_name'];
                        $cstm_address = $cust_data['cstm_address'];
                        $cstm_city = $cust_data['cstm_city'];
                        $cstm_state = $cust_data['cstm_state'];
                        $cstm_phone = $cust_data['cstm_phone'];
                        $cstm_email = $cust_data['cstm_email'];
                        $cstm_cstp_id = $cust_data['cstm_cstp_id'];
                    }
                    # Create an array with combined tool request and customer data.
                    $tool_req_data = [
                        'customer_name' => $cstm_name,
                        'customer_id' => $cstm_id,
                        'customer_address' => $cstm_address,
                        'customer_city' => $cstm_city,
                        'customer_state' => $cstm_state,
                        'customer_phone' => $cstm_phone,
                        'customer_email' => $cstm_email,
                        'cstm_cstp_id' => $cstm_cstp_id,
                        'tool_duration' => $tool_duration,
                        'tool_rent_quantity_by_customer' => $tool_rent_quantity_from_cust,
                        'tool_delivery_address' => $tool_del_address,
                        'tool_cost_from_cust' => $tool_cost_from_cust,
                        'tool_request_id' => $tool_req_id,
                        'tool_id' => $tool_id,
                        'tool_name' => $tool_name,
                        'tool_cost' => $tool_cost,
                        'tool_rent_quantity' => $tool_rent_quantity,
                        'tool_sale_quantity' => $tool_sale_quantity,
                        'tool_description' => $tool_description,
                        'tool_rent_id' => $tool_rent_id,
                        'tool_adv_price' => $tool_adv_price,
                        'tool_adv_payment' => $tool_adv_payment,
                        'tool_req_status' => $tool_req_status,
                        'tool_req_status_id' => $tool_req_statusid,
                        'tool_req_created_on' => $tool_req_created_on,
                        'tool_req_last_updated_on' => $tool_req_last_updated_on,
                        'tool_price_after_adv' => $tool_price_after_adv,
                        'tool_discount' => $tool_discount,
                        'tool_delay_percentage' => $tool_delay_percentage,
                        'tldt_adv_cost' => $tldt_adv_cost,
                        'tldt_number' => $tldt_number



                    ];
                    # Store the combined data in the 'tool_req_fetch' array.
                    $tool_req_fetch[$i] = $tool_req_data;
                }
            }
            #
        } else {
            $response['Messgae'] = 'No Tool List';
            return $this->respond($response, 200);
        }

        if ($tool_req_fetch) {
            $response = [
                'ret_data' => 'success',
                'tool_req_list' => $tool_req_fetch
            ];
        } else {
            $response['Message'] = 'Errorin fetch';
        }
        return $this->respond($response, 200);
    }

    public function fetch_request_status()
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

        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $ToolRequestTrackerModel = new ToolRequestTrackerModel();
        $statusmaster = new StatusMasterModel();
        $tr_id = $this->request->getVar('tr_id');
        if ($tr_id) {
            $curr_data = $ToolRequestDetailsModel->select('sm_name,sm_code,sm_id')->where('tldet_id', $tr_id)->join('status_master', 'tldt_status=sm_id')->first();
            $history_data = $ToolRequestHistoryModel->where('trqh_tr_id', $tr_id)->get()->getResult();
            if ($history_data) {
                for ($i = 0; $i < sizeof($history_data); $i++) {
                    $trid = $history_data[$i]->trqh_tr_id;
                    $status_id = $history_data[$i]->trqh_status_id;
                    $status_created_on = $history_data[$i]->trqh_created_on;
                    $tool_req_statusname = $statusmaster->select('sm_name,sm_code')->where('sm_id', $status_id)->findAll();
                    $tool_req_statusname = $tool_req_statusname[0];
                    $tool_req_status = $tool_req_statusname['sm_name'];
                    $tool_req_status_code = $tool_req_statusname['sm_code'];
                    $histdata = [
                        'tool_req_id' => $trid,
                        'status_id' => $status_id,
                        'status_name' => $tool_req_status,
                        'status_code' => $tool_req_status_code,
                        'status_created_on' => $status_created_on
                    ];

                    $data[$i] = $histdata;
                }
                $ret_data['history_details'] = $data;
                $tool_recieve_img = $ToolRequestTrackerModel->where('trt_type', 3)->where('trt_rq_id', $tr_id)->findAll();
                if (!$tool_recieve_img) {
                    $tool_recieve_img = 0;
                }
                $response = [
                    'ret_data' => 'success',
                    'toolrqst' => $ret_data,
                    'tool_status' => $curr_data,
                    'tlrq_imgs_r' => $tool_recieve_img
                ];
            }
        } else {
            $response['Message'] = 'No Tool ID';
        }
        return $this->respond($response, 200);
    }

    public function fetch_toolreq_hist()
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
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $statusmaster = new StatusMasterModel();
        $CustomerMasterModel = new CustomerMasterModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $history_data = $ToolRequestHistoryModel->orderBy('trqh_id', 'desc')->get()->getResult();
        if ($history_data) {

            for ($i = 0; $i < sizeof($history_data); $i++) {

                $trid = $history_data[$i]->trqh_tr_id;
                $tool_req_det = $ToolRequestDetailsModel->select('tldt_cstm_id,tldt_number,tldt_tool_id,tldt_tool_duration,tldt_tool_quant,tldt_cost,tldt_created_on,tldt_delivery_address')->where('tldet_id', $trid)->findAll();
                foreach ($tool_req_det as $tool_req_det) {
                    $cstm_id = $tool_req_det['tldt_cstm_id'];
                    $tldt_number = $tool_req_det['tldt_number'];

                    $cstm_det = $CustomerMasterModel->select('cstm_name,cstm_phone,cstm_cstr_id')->where('cstm_id', $cstm_id)->findAll();
                    foreach ($cstm_det as $cstm_det) {
                        $cstm_name = $cstm_det['cstm_name'];
                        $cstm_phone = $cstm_det['cstm_phone'];
                        $cstm_role = $cstm_det['cstm_cstr_id'];
                    }
                    $tool_id = $tool_req_det['tldt_tool_id'];
                    $tool_names = $ToolDetailsModel->select('tool_name')->where('tool_id', $tool_id)->findAll();
                    foreach ($tool_names as $tool_names) {
                        $tool_name = $tool_names['tool_name'];
                    }
                    $tool_duration = $tool_req_det['tldt_tool_duration'];
                    $tool_rent_quantity = $tool_req_det['tldt_tool_quant'];
                    $tool_req_cost = $tool_req_det['tldt_cost'];
                    $tool_req_created_on = $tool_req_det['tldt_created_on'];
                    $tool_del_address = $tool_req_det['tldt_delivery_address'];
                }
                $status_id = $history_data[$i]->trqh_status_id;
                $status_created_on = $history_data[$i]->trqh_created_on;
                $tool_req_statusname = $statusmaster->select('sm_name,sm_code')->where('sm_id', $status_id)->findAll();
                foreach ($tool_req_statusname as $tool_req_statusname) {
                    $tool_req_status = $tool_req_statusname['sm_name'];
                    $tool_req_status_code = $tool_req_statusname['sm_code'];
                }
                $histdata = [
                    'tool_req_id' => $trid,
                    'tool_req_status_id' => $status_id,
                    'tool_req_status' => $tool_req_status,
                    'status_code' => $tool_req_status_code,
                    'status_created_on' => $status_created_on,
                    'tool_name' => $tool_name,
                    'tool_duration' => $tool_duration,
                    'tool_rent_quantity' => $tool_rent_quantity,
                    'tool_req_cost' => $tool_req_cost,
                    'tool_req_created_on' => $tool_req_created_on,
                    'tool_del_address' => $tool_del_address,
                    'cstm_name' => $cstm_name,
                    'cstm_phone' => $cstm_phone,
                    'cstm_role' => $cstm_role,
                    'tldt_number' => $tldt_number,
                ];

                $data[$i] = $histdata;
            }
            $response = [
                'ret_data' => 'success',
                'history_details' => $data
            ];
        }
        return $this->respond($response, 200);
    }

    public function status_master_controller()
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


        $response['ret_data'] = 'success';
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $statusmaster = new StatusMasterModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $ToolRequesttrackerModel = new ToolRequestTrackerModel();
        $custModel = new CustomerMasterModel();
        $commonutils = new Commonutils();
        $validModel = new Validation();
        $notificationmasterModel = new NotificationmasterModel();
        $notificationmasterController = new UsersNotificationController;
        $date = date("Y-m-d H:i:s");
        $heddata = $this->request->headers();
        $tr_id = $this->request->getVar('tr_id');
        $status_id = $this->request->getVar('status_id');
        $flag_id = $this->request->getVar('flag_id');
        //$tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));


        //Status id for payment completed (id=6):no flag=>request completed,flag =1 =>tool ready to deliver ,flag=2=>.tool reached workshop 
        //status id for request acceptes(id=1):flag=1=>payment pending,no flag=>tool ready to deliver.
        //status id for return tool(id=11):no flag=>tool reached workshop,flag=1=>payment pending.
        // Status id for inspection in progress(id=12):no flag=>Inspection Completed,flag=1=>Inspection Tool Damaged.
        //Status id for Payment Pending(id=5):no flag =>Payment Completed,flag = 1 =>Payment Due,flag=2=>tool ready to deliver.


        if ($tr_id) {
            if ($status_id) {
                if ($status_id == 8) {
                    $next_status = 9;
                } else if ($status_id == 9) {
                    $next_status = 38;
                } else if ($status_id == 5) {
                    $next_status = 8;
                } else if ($status_id == 11) {
                    $next_status = 41;
                } else if ($status_id == 10) {
                    $next_status = 12;
                } else if ($status_id == 18) {
                    $next_status = 18;
                } else if ($status_id == 12) {
                    if ($flag_id == 1) {
                        $next_status = 14;
                    } else {
                        $next_status = 13;
                    }
                } else if ($status_id == 38) {
                    if ($flag_id == 1) {
                        $next_status = 36;
                    } else {
                        $next_status = 18;
                    }
                } else if ($status_id == 37) {
                    if ($flag_id == 1) {
                        $next_status = 39;
                    } else {
                        $next_status = 40;
                    }
                } else if ($status_id == 11) {
                    $next_status = 41;
                } else if ($status_id == 41) {
                    $next_status = 10;
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
                            'tldt_status' => 12,
                            'tldt_hold_flag' => 0,
                            'tldt_updated_on' => $date,
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


                        $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
                        $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                        $player_id = [];
                        $custhead = "CATRAMS- Inspection In Progress!!!! ";
                        $custcontent = "" . $tool_det['tldt_number'] . "-Tool Reached CATRAMS ,Inspection in progress.";
                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                        if ($ret_res) {
                            $notif_data = [
                                'sourceid' => $tokendata['uid'],
                                'destid' => $tool_det['tldt_cstm_id'],
                                'nt_req_number' => $tool_det['tldt_number'],
                                'id'=>$tool_det['tldt_cstm_id'],
            
                                'nt_sourcetype' => 1,
                                'headers' => $custhead,
                                'content' => $custcontent,
                                'date' => $date,
                                'nt_type'=>0,
                                'nt_request_type'=>1,
                                'nt_type_id'=>$tool_det['tldet_id']
                            ];
                            $notificationmasterController->create_cust_notification($notif_data);
                        }
                        $response['ret_data'] = 'success';
                    } else if ($next_status == 40) {
                        $updtdata1 = ['tldt_status' => 41];
                        $insertdata1 = [
                            'trqh_status_id' => 41,
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
                                'tldt_status' => 15,
                                'tldt_due_date' => $this->request->getVar('tldt_due_date'),
                                'tldt_R_date' => $this->request->getVar('tldt_R_date'), 'tldt_updated_on' => $date,
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
                                'tldt_status' => 11,
                                'tldt_due_date' => $this->request->getVar('tldt_due_date'),
                                'tldt_R_date' => $this->request->getVar('tldt_R_date'), 'tldt_updated_on' => $date,
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

                        if ($this->request->getVar('r_images') && (sizeof($this->request->getVar('r_images')) > 0)) {
                            $tarray = [];
                            foreach ($this->request->getVar('r_images') as $eachurl) {
                                $insert_track = [
                                    'trt_rq_id' => $tr_id,
                                    'trt_type' => 3,
                                    'trt_url' => $eachurl,
                                    'trt_created_by' => $tokendata['uid'],
                                    'trt_created_on' => $date,
                                    'trt_updated_by' => $tokendata['uid'],
                                    'trt_updated_on' => $date,
                                ];
                                array_push($tarray, $insert_track);
                            }
                            sizeof($tarray) > 0 ? $ToolRequesttrackerModel->insertBatch($tarray) : "";
                        }

                        return $this->respond($response, 200);
                    } else if ($next_status == 14) {
                        $updtdata1 = ['tldt_status' => 5, 'tldt_paymt_flag' => 1];
                        $insertdata1 = [
                            'trqh_status_id' => 5,
                            'trqh_tr_id' => $tr_id,
                            'trqh_created_on' => $date,
                            'trqh_created_by' => $tokendata['uid'],

                        ];
                        $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                        $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);

                        $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
                        $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                        $player_id = [];
                        $custhead = "CATRAMS-Tool Damaged ";
                        $custcontent = "" . $tool_det['tldt_number'] . "-Tool has been Damaged.Tap to Pay";

                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                        if ($ret_res) {
                            $notif_data = [
                                'sourceid' => $tokendata['uid'],
                                'destid' => $tool_det['tldt_cstm_id'],
                                'nt_req_number' => $tool_det['tldt_number'],
                                'id'=>$tool_det['tldt_cstm_id'],
            
                                'nt_sourcetype' => 1,
                                'headers' => $custhead,
                                'content' => $custcontent,
                                'date' => $date,
                                'nt_type'=>0,
                                'nt_request_type'=>1,
                                'nt_type_id'=>$tool_det['tldet_id']
                            ];
                            $notificationmasterController->create_cust_notification($notif_data);
                        }
                        $response['ret_data'] = 'success';
                    } else if ($next_status == 13) {
                        $updtdata1 = ['tldt_status' => 16];
                        $insertdata1 = [
                            'trqh_status_id' => 16,
                            'trqh_tr_id' => $tr_id,
                            'trqh_created_on' => $date,
                            'trqh_created_by' => $tokendata['uid'],
                        ];

                        $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
                        $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                        $player_id = [];
                        $custhead = "CATRAMS-Offers in services ";
                        $custcontent = "Checkout the services offered by CATRAMS";
                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                        if ($ret_res) {
                            $notif_data = [
                                'sourceid' => $tokendata['uid'],
                                'destid' => $tool_det['tldt_cstm_id'],
                                'nt_req_number' => $tool_det['tldt_number'],
                                'id'=>$tool_det['tldt_cstm_id'],
            
                                'nt_sourcetype' => 1,
                                'headers' => $custhead,
                                'content' => $custcontent,
                                'date' => $date,
                                'nt_type'=>0,
                                'nt_request_type'=>1,
                                'nt_type_id'=>$tool_det['tldet_id']
                            ];
                            $notificationmasterController->create_cust_notification($notif_data);
                        }

                        $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                        $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                        $response['ret_data'] = 'success';
                    } else if ($next_status == 41) {
                        $shipmenttrackingModel = new ShipmentTrackingModel();
                        $shipmentmasterModel = new ShipmentMasterModel();
                        $shipmast = [
                            'shm_type' => 0,
                            'shm_by_type' => 1,
                            'shm_request_id' => $this->request->getVar('tr_id'),
                            'shm_status' => 0,
                            'shm_track_id' => $this->request->getVar('tracking_id'),
                            'shm_track_url' => $this->request->getVar('tracking_link'),
                            'shm_reference' => $this->request->getVar('reference_id'),
                            'shm_created_on' => date("Y-m-d H:i:s"),
                            'shm_created_by' => $tokendata['uid'],
                        ];

                        $shm_id = $shipmentmasterModel->insert($shipmast);
                        $shiptrack = [
                            'shtrack_shm_id' => $shm_id,
                            'shtrack_status' => 0,
                            'shtrack_created_on' => date("Y-m-d H:i:s"),
                            'shtrack_created_by' => $tokendata['uid']
                        ];

                        $shtrack_id = $shipmenttrackingModel->insert($shiptrack);
                        $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
                        $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                        $player_id = [];
                        $custhead = "CATRAMS- Return Initiated";
                        $custcontent = "" . $tool_det['tldt_number'] . "-Return Initiated.";
                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                        if ($ret_res) {
                            $notif_data = [
                                'sourceid' => $tokendata['uid'],
                                'destid' => $tool_det['tldt_cstm_id'],
                                'nt_req_number' => $tool_det['tldt_number'],
                                'id'=>$tool_det['tldt_cstm_id'],
            
                                'nt_sourcetype' => 1,
                                'headers' => $custhead,
                                'content' => $custcontent,
                                'date' => $date,
                                'nt_type'=>0,
                                'nt_request_type'=>1,
                                'nt_type_id'=>$tool_det['tldet_id']
                            ];
                            $notificationmasterController->create_cust_notification($notif_data);
                        }
                        $response['ret_data'] = 'success';
                    } else if ($next_status == 39) {
                        $updtdata1 = ['tldt_status' => 18];
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
                            $updtdata2 = ['tldt_status' => 15];
                            $insertdata2 = [
                                'trqh_status_id' => 15,
                                'trqh_tr_id' => $tr_id,
                                'trqh_created_on' => $date,
                                'trqh_created_by' => $tokendata['uid'],
                            ];
                            $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata2);
                            $toolhistid = $ToolRequestHistoryModel->insert($insertdata2);
                            $response['ret_data'] = 'success';
                            return $this->respond($response, 200);
                        } else {
                            $updtdata1 = ['tldt_status' => 11];
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
                    } else if ($next_status == 36) {

                        $updtdata1 = ['tldt_status' => 37];
                        $insertdata1 = [
                            'trqh_status_id' => 37,
                            'trqh_tr_id' => $tr_id,
                            'trqh_created_on' => $date,
                            'trqh_created_by' => $tokendata['uid'],
                        ];

                        if ($this->request->getVar('toolimages')  &&  sizeof($this->request->getVar('toolimages')) > 0) {

                            $tarray = [];
                            foreach ($this->request->getVar('toolimages') as $eachurl) {
                                $insert_track = [
                                    'trt_rq_id' => $tr_id,
                                    'trt_type' => 0,
                                    'trt_url' => $eachurl,
                                    'trt_url_type' => 0,
                                    'trt_notes' => $this->request->getVar('reject_reason'),
                                    'trt_created_by' => $tokendata['uid'],
                                    'trt_created_on' => $date,
                                    'trt_updated_by' => $tokendata['uid'],
                                    'trt_updated_on' => $date,
                                ];
                                array_push($tarray, $insert_track);
                            }
                            sizeof($tarray) > 0 ? $ToolRequesttrackerModel->insertBatch($tarray) : "";

                            $imageFile = $this->request->getFile('audio');
                            if ($imageFile) {
                                $imageFile->move(ROOTPATH . 'public/uploads/ToolrequestTracker_images');
                                $auddata = [
                                    'trt_rq_id' => $tr_id,
                                    'trt_type' => 1,
                                    'trt_url' => 'uploads/ToolrequestTracker_images/' . $imageFile->getName(),
                                    'trt_url_type' => 1,
                                    'trt_notes' => $this->request->getVar('reject_reason'),
                                    'trt_created_by' => $tokendata['uid'],
                                    'trt_created_on' => $date,
                                    'trt_updated_by' => $tokendata['uid'],
                                    'trt_updated_on' => $date,
                                ];
                                $ToolRequesttrackerModel->insert($auddata);
                            }
                            $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                            $ToolRequestHistoryModel->insert($insertdata1);
                            $aprovalmasterModel = new ApprovalmasterModel();
                            $apData = [
                                'am_reqid' => $tr_id,
                                'am_requestedby' =>  $tokendata['uid'],
                                'am_updatedby' => $tokendata['uid'],
                                'am_updatedon' => $date,
                                'am_createdby' => $tokendata['uid'],
                                'am_createdon' => $date,
                            ];
                            $aprovalmasterModel->insert($apData);
                        } else {
                        }
                        $response['ret_data'] = 'success';
                    } else if ($next_status == 9) {
                        $shipmenttrackingModel = new ShipmentTrackingModel();
                        $shipmentmasterModel = new ShipmentMasterModel();
                        $requestMediaModel = new RequestMediaModel();
                        $shipmentMasterController= new ShipmentMasterController;
                        $infdata = [];
                        foreach ($this->request->getVar('tool_images') as $eachurl) {

                            $indata = [
                                'rmedia_type' => 0,
                                'rmedia_request_id' => $this->request->getVar('tr_id'),
                                'rmedia_url_type' => 1,
                                'rmedia_url' => $eachurl,
                                'rmedia_by_type' => 0,
                                'rmedia_created_on' => date("Y-m-d H:i:s"),
                                'rmedia_created_by' => $tokendata['uid']
                            ];
                            array_push($infdata, $indata);
                        }
                        if (sizeof($infdata) > 0) {
                            $requestMediaModel->insertBatch($infdata);
                        }
                        

                        $shipmast = [
                            'shm_type' => 0,
                            'shm_by_type' => 0,
                            'shm_request_id' => $this->request->getVar('tr_id'),
                            'shm_status' => 0,
                            'shm_track_id' => $this->request->getVar('track_id'),
                            'shm_track_url' => $this->request->getVar('track_url'),
                            'shm_reference' => $this->request->getVar('track_reference'),
                            'shm_created_by' => $tokendata['uid'],
                            'shm_created_on' => $date,
                            'shm_updated_by' => $tokendata['uid'],
                            'shm_updated_on' => $date,
                        ];
                        
                        $shiptrack = [
                            
                            'shtrack_status' => 0,
                            'shtrack_created_by' => $tokendata['uid'],
                            'shtrack_created_on' => $date,
                        ];
                        
                       $shtrack_id= $shipmentMasterController->create_shipment($shipmast,$shiptrack);
                        {
                            $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
                            $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                            $player_id = [];
                            $custhead = "CATRAMS- Tool Dispatched ";
                            $custcontent = "" . $tool_det['tldt_number'] . "-Tool Dispatched Successfully.";
                            array_push($player_id, $target_cust['fcm_token_mobile']);
                            if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);
                            if ($ret_res) {
                                $notif_data = [
                                    'id'=>$tool_det['tldt_cstm_id'],

                                    'sourceid' => $tokendata['uid'],
                                    'destid' => $tool_det['tldt_cstm_id'],
                                    'nt_req_number' => $tool_det['tldt_number'],
                
                                    'nt_sourcetype' => 1,
                                    'headers' => $custhead,
                                    'content' => $custcontent,
                                    'date' => $date,
                                    'nt_type'=>0,
                                    'nt_request_type'=>1,
                                    'nt_type_id'=>$tool_det['tldet_id']
                                ];
                                $notificationmasterController->create_cust_notification($notif_data);
                            }
                            $response = ['ret_data' => 'success'];
                            return $this->respond($response, 200);
                        }
                    } else if ($next_status == 8) {
                        $shipmenttrackingModel = new ShipmentTrackingModel();
                        $shipmentmasterModel = new ShipmentMasterModel();

                        $shipmast = [
                            'shm_type' => 0,
                            'shm_by_type' => 0,
                            'shm_request_id' => $this->request->getVar('tr_id'),
                            'shm_status' => 0,
                            'shm_track_id' => $this->request->getVar('tracking_id'),
                            'shm_track_url' => $this->request->getVar('tracking_link'),
                            'shm_reference' => $this->request->getVar('reference_id'),
                            'shm_created_by' => $tokendata['uid'],
                            'shm_created_on' => $date,
                            'shm_updated_by' => $tokendata['uid'],
                            'shm_updated_on' => $date,
                        ];

                        $shm_id = $shipmentmasterModel->insert($shipmast);
                        $shiptrack = [
                            'shtrack_shm_id' => $shm_id,
                            'shtrack_status' => 0,
                            'shtrack_created_by' => $tokendata['uid'],
                            'shtrack_created_on' => $date,

                        ];
                        $shtrack_id = $shipmenttrackingModel->insert($shiptrack);
                        $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
                        $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                        $player_id = [];
                        $custhead = "CATRAMS- Ready to Deliver";
                        $custcontent = "" . $tool_det['tldt_number'] . "-Tool is being ready for delivery.";
                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                        if ($ret_res) {
                            $notif_data = [
                                'id'=>$tool_det['tldt_cstm_id'],

                                'sourceid' => $tokendata['uid'],
                                'destid' => $tool_det['tldt_cstm_id'],
                                'nt_req_number' => $tool_det['tldt_number'],
            
                                'nt_sourcetype' => 1,
                                'headers' => $custhead,
                                'content' => $custcontent,
                                'date' => $date,
                                'nt_type'=>0,
                                'nt_request_type'=>1,
                                'nt_type_id'=>$tool_det['tldet_id']
                            ];
                            $notificationmasterController->create_cust_notification($notif_data);
                            $response['ret_data'] = 'success';
                        }
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

    public function fetch_inspection_list()
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
        $response['ret_data'] = 'eror';
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $statusmaster = new StatusMasterModel();
        $inspection_list = $ToolRequestDetailsModel->whereIn('tldt_status', [41, 12, 14])->join('status_master', 'sm_id=tldt_status')->orderBy('tldet_id', 'desc')->findAll();
        if ($inspection_list) {
            $response = [
                'ret_data' => 'success',
                'inspection_list' => $inspection_list
            ];
        } else {
            $response['Message'] = 'No inspection list';
            $response['code'] = 6;
        }
        return $this->respond($response, 200);
    }

   
    public function completed_request()
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
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $statusmaster = new StatusMasterModel();
        $CustomerMasterModel = new CustomerMasterModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $ToolRequesttrackerModel = new ToolRequestTrackerModel();
        $cs_id = $this->request->getVar('cstm_id');
        if ($cs_id) {
            $tool_req_details = $ToolRequestDetailsModel
                ->where('tldt_cstm_id', $cs_id)->where('tldt_status', 16)
                ->orwhere('tldt_status', 7)
                ->join('tool_details', 'tool_id =tldt_tool_id')
                ->join('status_master', 'sm_id=tldt_status')
                ->join('customer_master', 'cstm_id=tldt_cstm_id')
                ->orderBy('tldet_id', 'desc')
                ->findAll();
            // return $this->respond($tool_req_details, 200);

            for ($i = 0; $i < sizeof($tool_req_details); $i++) {
                if ($tool_req_details[$i]['tldt_status'] == 7) {
                    $rejected_reason = $ToolRequesttrackerModel
                        ->select('trt_notes')
                        ->where('trt_rq_id', $tool_req_details[$i]['tldet_id'])
                        ->first();

                    $tool_req_details[$i]['rejected_reason'] = $rejected_reason['trt_notes'];
                } else {
                    // Handle the case when tldt_status is not 7 if needed
                }
            }
        } else {
            $tool_req_details = $ToolRequestDetailsModel->where('tldt_status', 16)->findAll();
        }
        if ($tool_req_details) {
            $response = [
                'ret_data' => 'success',
                'tool_req_list' => $tool_req_details
            ];
            return $this->respond($response, 200);
            // for ($i = 0; $i < sizeof($tool_req_details); $i++) { {
            //         # Extract various attributes from the tool request detail.
            //         $tool_id = $tool_req_details[$i]->tldt_tool_id;
            //         $tool_req_id = $tool_req_details[$i]->tldet_id;
            //         $tool_duration = $tool_req_details[$i]->tldt_tool_duration;
            //         $tldt_number = $tool_req_details[$i]->tldt_number;

            //         $tool_rent_quantity_from_cust = $tool_req_details[$i]->tldt_tool_quant;
            //         $tool_del_address = $tool_req_details[$i]->tldt_delivery_address;
            //         $tool_cost_from_cust = $tool_req_details[$i]->tldt_cost;
            //         $tool_req_created_on = $tool_req_details[$i]->tldt_created_on;
            //         $tool_req_last_updated_on = $tool_req_details[$i]->tldt_updated_on;
            //         $tldt_adv_cost= $tool_req_details[$i]->tldt_adv_cost;
            //         $tool_req_statusid = $tool_req_details[$i]->tldt_status;
            //         $tool_adv_payment = $tool_req_details[$i]->tldt_advpaymt_flag;
            //         $tool_req_statusname = $statusmaster->select('sm_name')->where('sm_id', $tool_req_statusid)->findAll();
            //         $tool_req_statusname = $tool_req_statusname[0];
            //         $tool_req_status = $tool_req_statusname['sm_name'];
            //         # Retrieve tool data based on the 'tool_id'.
            //         $tool_data = $ToolDetailsModel->select('tool_name,tool_discount,tool_delay_percentage,tool_adv_price,tool_description,tool_cost,tool_rent_quantity,tool_sale_quantity,tool_rent_id')->where('tool_id', $tool_id)->findAll();
            //         # Loop through each tool data.
            //         if (!empty($tool_data)) {
            //             # Extract attributes from the first item in $tool_data.
            //             $first_tool_data = $tool_data[0];
            //             $tool_name = $first_tool_data['tool_name'];
            //             $tool_cost = $first_tool_data['tool_cost'];
            //             $tool_rent_quantity = $first_tool_data['tool_rent_quantity'];
            //             $tool_sale_quantity = $first_tool_data['tool_sale_quantity'];
            //             $tool_description = $first_tool_data['tool_description'];
            //             $tool_rent_id = $first_tool_data['tool_rent_id'];
            //             $tool_advance_price = $first_tool_data['tool_adv_price'];
            //             $tool_discount = $first_tool_data['tool_discount'];
            //             $tool_delay_percentage = $first_tool_data['tool_delay_percentage'];

            //         }
            //         $tool_adv_price = (($tool_cost_from_cust) * ($tool_advance_price)) / 100;
            //         $tool_price_after_adv = ($tool_cost_from_cust) - ($tool_adv_price);
            //         # Extract the customer ID associated with the tool request detail.
            //         if ($cs_id) {
            //             $cstm_id = $cs_id;
            //         } else {
            //             $cstm_id = $tool_req_details[$i]->tldt_cstm_id;
            //         }

            //         # Retrieve customer data based on the 'cstm_id'.
            //         $cust_data = $CustomerMasterModel->select('cstm_name,cstm_address,cstm_city,cstm_state,cstm_phone,cstm_email, cstm_cstp_id')->where('cstm_id', $cstm_id)->findAll();
            //         # Loop through each customer data.
            //         foreach ($cust_data as $cust_data) {
            //             # Extract various attributes from the customer data.
            //             $cstm_name = $cust_data['cstm_name'];
            //             $cstm_address = $cust_data['cstm_address'];
            //             $cstm_city = $cust_data['cstm_city'];
            //             $cstm_state = $cust_data['cstm_state'];
            //             $cstm_phone = $cust_data['cstm_phone'];
            //             $cstm_email = $cust_data['cstm_email'];
            //             $cstm_cstp_id = $cust_data['cstm_cstp_id'];
            //         }
            //         # Create an array with combined tool request and customer data.
            //         $tool_req_data = [
            //             'customer_name' => $cstm_name,
            //             'customer_id' => $cstm_id,
            //             'customer_address' => $cstm_address,
            //             'customer_city' => $cstm_city,
            //             'customer_state' => $cstm_state,
            //             'customer_phone' => $cstm_phone,
            //             'customer_email' => $cstm_email,
            //             'cstm_cstp_id' => $cstm_cstp_id,
            //             'tool_duration' => $tool_duration,
            //             'tool_rent_quantity_by_customer' => $tool_rent_quantity_from_cust,
            //             'tool_delivery_address' => $tool_del_address,
            //             'tool_cost_from_cust' => $tool_cost_from_cust,
            //             'tool_request_id' => $tool_req_id,
            //             'tool_id' => $tool_id,
            //             'tool_name' => $tool_name,
            //             'tool_cost' => $tool_cost,
            //             'tool_rent_quantity' => $tool_rent_quantity,
            //             'tool_sale_quantity' => $tool_sale_quantity,
            //             'tool_description' => $tool_description,
            //             'tool_rent_id' => $tool_rent_id,
            //             'tool_adv_payment' => $tool_adv_payment,
            //             'tool_req_status' => $tool_req_status,
            //             'tool_req_status_id' => $tool_req_statusid,
            //             'tool_req_created_on' => $tool_req_created_on,
            //             'tool_req_last_updated_on' => $tool_req_last_updated_on,
            //             'tool_adv_price' => $tool_adv_price,
            //             'tool_price_after_adv' => $tool_price_after_adv,
            //             'tool_discount' => $tool_discount,
            //             'tool_delay_percentage' => $tool_delay_percentage,
            //             'tldt_adv_cost'=>$tldt_adv_cost,
            //             'tldt_number'=>$tldt_number

            //         ];
            //         # Store the combined data in the 'tool_req_fetch' array.
            //         $tool_req_fetch[$i] = $tool_req_data;
            //     }
            // }
            #
        } else {
            $response = [
                'Messgae' => 'No Tool List',
                'ret_data' => 'success'
            ];
            return $this->respond($response, 200);
        }
    }

    public function due_Date_adjust()
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
            'tldet_id' => 'required'
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $approvalModel = new ApprovalmasterModel();
        $notificationmasterModel = new NotificationmasterModel();
        $custModel = new CustomerMasterModel();
        $notificationmasterController = new UsersNotificationController;
        if ($this->request->getVar('tldt_due_date') && $this->request->getVar('tldt_R_date')) {
            $master = [
                'tldt_due_date' => $this->request->getVar('tldt_due_date'),
                'tldt_R_date' => $this->request->getVar('tldt_R_date')
            ];
        } else if ($this->request->getVar('tldt_due_date')) {
            $master = [
                'tldt_due_date' => $this->request->getVar('tldt_due_date'),
                'tldt_hold_by' => $tokendata['uid'],
                'tldt_hold_flag' => 1,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $data = [
                'tldet_id' => $this->request->getVar("tldet_id"),
                'am_status' => 1,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
            ];
            $approvalModel->update($this->request->getVar("am_id"), $data);
            $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tldet_id'))->first();
            $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
            $player_id = [];
            $custhead = "CATRAMS- Your Hold Request Has been Approved";
            $custcontent =  "Please do checkout the revised date";
            array_push($player_id, $target_cust['fcm_token_mobile']);
            if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

            if ($ret_res) {
                $notif_data = [
                    'id'=>$tool_det['tldt_cstm_id'],

                    'sourceid' => $tokendata['uid'],
                    'destid' => $tool_det['tldt_cstm_id'],
                    'nt_req_number' => $tool_det['tldt_number'],

                    'nt_sourcetype' => 1,
                    'headers' => $custhead,
                    'content' => $custcontent,
                    'date' => $date,
                    'nt_type'=>0,
                    'nt_request_type'=>1,
                    'nt_type_id'=>$tool_det['tldet_id']
                ];
                $notificationmasterController->create_cust_notification($notif_data);
            }
        } else {
            $master = [
                'tldt_due_date' => $this->request->getVar('tldt_R_date')
            ];
        }

        $tr_id = $ToolRequestDetailsModel->update($this->request->getVar('tldet_id'), $master);
        if ($tr_id) {
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

    public function refund_calc()
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
            'tldet_id' => 'required',
            'type' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $trequestrackerModel = new ToolRequestTrackerModel();
        $date = date("Y-m-d H:i:s");
        if ($this->request->getVar('refund_price')) {

            $refund_price = $this->request->getVar('refund_price');
            $rpt_id = $this->request->getVar('rpt_id');
            $tldt_status = $this->request->getVar('tldt_status');
            $tldet_id = $this->request->getVar('tldet_id');
            $master = [
                'tldt_cost' => $this->request->getVar('refund_price'),
                'tldt_status' => 16,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $hist = [
                'trqh_tr_id' => $this->request->getVar('tldet_id'),
                'trqh_status_id' => 13,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $ToolRequestHistoryModel->insert($hist);
            $pay = [
                'rpt_amount' => $this->request->getVar('refund_price'),
                'rpt_status' => 3,
                'rpt_updated_on' => $date,
                'rpt_updated_by' => $tokendata['uid'],
            ];
            $histupt1 = [
                'trqh_tr_id' => $this->request->getVar('tldet_id'),
                'trqh_status_id' => 55,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $ToolRequestHistoryModel->insert($histupt1);
            $histupt2 = [
                'trqh_tr_id' => $this->request->getVar('tldet_id'),
                'trqh_status_id' => 16,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $insert_data = [
                'trt_rq_id' => $this->request->getVar('tldet_id'),
                'trt_type' => 2,
                'trt_url' => $this->request->getVar('refund_price'),
                'trt_created_by' => $tokendata['uid'],
                'trt_created_on' => $date,
                'trt_updated_by' => $tokendata['uid'],
                'trt_updated_on' => $date,
            ];
            $us_id = $trequestrackerModel->insert($insert_data);
            $ToolRequestHistoryModel->insert($histupt2);
            $paymentTrackerModel->update($this->request->getVar('rpt_id'), $pay);
            $toolrequestmasterModel->update($this->request->getVar('tldet_id'), $master);

            $response = [
                'ret_data' => 'success'
            ];
            return $this->respond($response, 200);
        }
    }

    public function hold_tlrq()
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
            'tldet_id' => 'required',
            'am_reason' => 'required',
            'hold_days' => 'required',
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $paymentTrackerModel = new PaymentTrackermasterModel();
        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $trequestrackerModel = new ToolRequestTrackerModel();
        $approvalMasterModel = new ApprovalmasterModel();
        $date = date("Y-m-d H:i:s");
        $hold_criteria = [
            'am_reason' => $this->request->getVar('am_reason'),
            'am_reqid' => $this->request->getVar('tldet_id'),
            'am_type' => 3,
            'am_requestedby' => $tokendata['uid'],
            'am_status' => 0,
            'am_referenceid' => $this->request->getVar('hold_days'),
            'am_updatedby' => $tokendata['uid'],
            'am_updatedon' => $date,
            'am_createdby' => $tokendata['uid'],
            'am_createdon' => $date,
        ];
        $imageFile = $this->request->getFile('audio');
        if ($imageFile) {
            $imageFile->move(ROOTPATH . 'public/uploads/ServiceRequest_audio');
            $hold_criteria = [
                'am_url' => 'uploads/ServiceRequest_audio/' . $imageFile->getName(),
            ];
        }


        $am_id = $approvalMasterModel->insert($hold_criteria);
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

    public function damagedtool_insp()
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
        $date = date("Y-m-d H:i:s");


        if ($this->request->getVar('type') == 0) {
            $data = [
                'am_reqid' => $this->request->getVar('tldet_id'),
                'am_type' => 8,
                'am_status' => 0,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
                'am_createdby' => $tokendata['uid'],
                'am_createdon' => $date,
            ];
            $app_data = $approvalMasterModel->insert($data);
            if ($app_data) {
                $hist1 = [
                    'trqh_tr_id' => $this->request->getVar('tldet_id'),
                    'trqh_status_id' => 13,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];

                $ToolRequestHistoryModel->insert($hist1);
                $master = [
                    'tldt_status' => 37,
                    'tldt_updated_on' => $date,
                    'tldt_updated_by' => $tokendata['uid'],
                ];
                $ToolRequestDetailsModel->update($this->request->getVar('tldet_id'), $master);
                $hist2 = [
                    'trqh_tr_id' => $this->request->getVar('tldet_id'),
                    'trqh_status_id' => 37,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];

                $ToolRequestHistoryModel->insert($hist2);
            }
        } else {
            $data = [
                'am_reqid' => $this->request->getVar('tldet_id'),
                'am_type' => 9,
                'am_referenceid' => $this->request->getVar('due_cost'),
                'am_status' => 0,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
                'am_createdby' => $tokendata['uid'],
                'am_createdon' => $date,
            ];
            $app_data = $approvalMasterModel->insert($data);
            if ($app_data) {
                $total_cost = $this->request->getVar('rpt_amount') + $this->request->getVar('due_cost');


                $pay_master = [
                    'rpt_amount' => $total_cost,
                    'rpt_status' => 0,
                    'rpt_updated_on' => $date,
                    'rpt_updated_by' => $tokendata['uid'],
                ];
                $paymentmasterModel->update($this->request->getVar('rpt_id'), $pay_master);



                $hist1 = [
                    'trqh_tr_id' => $this->request->getVar('tldet_id'),
                    'trqh_status_id' => 14,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];

                $ToolRequestHistoryModel->insert($hist1);
                $master = [
                    'tldt_status' => 37,
                    'tldt_updated_on' => $date,
                    'tldt_updated_by' => $tokendata['uid'],
                ];
                $ToolRequestDetailsModel->update($this->request->getVar('tldet_id'), $master);
                $hist2 = [
                    'trqh_tr_id' => $this->request->getVar('tldet_id'),
                    'trqh_status_id' => 37,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];

                $ToolRequestHistoryModel->insert($hist2);
            }
        }

        $response['ret_data'] = 'success';
        return $this->respond($response, 200);
    }

    public function damagedtoolreq_updt()
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
        $date = date("Y-m-d H:i:s");
        $data = [
            'am_status' => 1,
            'am_updatedby' => $tokendata['uid'],
            'am_updatedon' => $date,
        ];
        $app_data = $approvalMasterModel->update($this->request->getVar('am_id'), $data);
        $paymodel = $paymentmasterModel
            ->where('rpt_reqt_id', $this->request->getVar('tldet_id'))
            ->first();


        if ($app_data) {
            $hist1 = [
                'trqh_tr_id' => $this->request->getVar('tldet_id'),
                'trqh_status_id' => 40,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];

            $ToolRequestHistoryModel->insert($hist1);


            if ($this->request->getVar('type') == 0) {
                if ($paymodel['rpt_amount'] == 0) {
                    $master = [
                        'tldt_status' => 8,
                        'tldt_damaged' => 0,
                        'tldt_updated_on' => $date,
                        'tldt_updated_by' => $tokendata['uid'],
                    ];
                    $hist2 = [
                        'trqh_tr_id' => $this->request->getVar('tldet_id'),
                        'trqh_status_id' => 8,
                        'trqh_created_on' => $date,
                        'trqh_created_by' => $tokendata['uid'],
                    ];
                } else {
                    $master = [
                        'tldt_status' => 5,
                        'tldt_updated_on' => $date,
                        'tldt_updated_by' => $tokendata['uid'],
                    ];
                    $hist2 = [
                        'trqh_tr_id' => $this->request->getVar('tldet_id'),
                        'trqh_status_id' => 5,
                        'trqh_created_on' => $date,
                        'trqh_created_by' => $tokendata['uid'],
                    ];
                }
                $ToolRequestDetailsModel->update($this->request->getVar('tldet_id'), $master);
                $ToolRequestHistoryModel->insert($hist2);
            } else if ($this->request->getVar('type') == 1) {
                $hist1 = [
                    'trqh_tr_id' => $this->request->getVar('tldet_id'),
                    'trqh_status_id' => 16,
                    'trqh_created_on' => $date,
                    'trqh_created_by' => $tokendata['uid'],
                ];
                $ToolRequestHistoryModel->insert($hist1);
                if ($paymodel['rpt_amount'] != 0) {
                    $master = [
                        'tldt_status' => 16,
                        'tldt_updated_on' => $date,
                        'tldt_updated_by' => $tokendata['uid'],
                    ];
                } else {
                    $master = [
                        'tldt_status' => 55,
                        'tldt_updated_on' => $date,
                        'tldt_updated_by' => $tokendata['uid'],
                    ];
                    $ToolRequestDetailsModel->update($this->request->getVar('tldet_id'), $master);
                    $hist2 = [
                        'trqh_tr_id' => $this->request->getVar('tldet_id'),
                        'trqh_status_id' => 55,
                        'trqh_created_on' => $date,
                        'trqh_created_by' => $tokendata['uid'],
                    ];
                    $rpt_data = [
                        'rpt_amount' => $this->request->getVar('tldt_cost'),
                        'rpt_status' => 3,
                        'rpt_updated_on' => $date,
                        'rpt_updated_by' => $tokendata['uid'],
                    ];

                    $paymentmasterModel->update($this->request->getVar('rpt_id'), $rpt_data);
                }



                $ToolRequestHistoryModel->insert($hist2);
            }

            $response['ret_data'] = 'success';
            return $this->respond($response, 200);
        }
    }

    public function payment_complete()

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

        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $statusmaster = new StatusMasterModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $notificationmasterModel = new NotificationmasterModel();
        $custModel = new CustomerMasterModel();
        $paymentmasterModel = new PaymentTrackermasterModel();
        $paymenthistoryModel = new PaymentHistoryModel();
        $notificationmasterController = new UsersNotificationController;
        $tr_id = $this->request->getVar('tr_id');
        $status_id = $this->request->getVar('status_id');
        $flag_id = $this->request->getVar('flag_id');
        $t_details = $ToolRequestDetailsModel->where('tldet_id', $tr_id)->join('request_payment_tracker', 'rpt_reqt_id=tldet_id')->first();
        $date = date("Y-m-d H:i:s");
        if ($tr_id) {
            if ($status_id == 5) {
                $next_status = 6;
            } else if ($status_id == 15) {
                $next_status = 6;
            } else if ($status_id == 42) {
                $next_status = 43;
            } else if ($status_id = 48) {
                $next_status = 49;
            }
            $upd = [
                'tldt_status' => $next_status,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $indt = [
                'trqh_status_id' => $next_status,
                'trqh_tr_id' => $tr_id,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $res = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $upd);
            $tlhtid = $ToolRequestHistoryModel->insert($indt);
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
            if ($flag_id) {
                if ($next_status == 6) {
                    //flag =1 =>tool ready to deliver 
                    if ($flag_id == 1) {
                        $updtdata1 = [
                            'tldt_paymt_flag' => 0,
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
                        $response['ret_data'] = 'success';
                        $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
                        $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                        $player_id = [];
                        $custhead = "CATRAMS- Payment Success";
                        $custcontent = "" . $tool_det['tldt_number'] . "-Tool is being ready for delivery.";
                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                        if ($ret_res) {
                            $notif_data = [
                                'id'=>$tool_det['tldt_cstm_id'],

                                'sourceid' => $tokendata['uid'],
                                'destid' => $tool_det['tldt_cstm_id'],
                                'nt_req_number' => $tool_det['tldt_number'],
            
                                'nt_sourcetype' => 1,
                                'headers' => $custhead,
                                'content' => $custcontent,
                                'date' => $date,
                                'nt_type'=>0,
                                'nt_request_type'=>1,
                                'nt_type_id'=>$tool_det['tldet_id']
                            ];
                            $notificationmasterController->create_cust_notification($notif_data);
                        }

                        $rpt_Data = [
                            'rpt_amount' => 0,
                            'rpt_status' => 1,
                            'rpt_updated_on' => $date,
                            'rpt_updated_by' => $tokendata['uid'],
                            'rpt_transaction_id' => $this->request->getVar('txnid'),
                            
                        ];
                        $pay_d = [
                            'rph_type' => 1,
                            'rph_rq_id' => $tr_id,
                            'rph_status' => 1,
                            'rph_amount' => $this->request->getVar('advance_amount'),
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                            'rph_transaction_id' => $this->request->getVar('txnid'),
                        ];

                        $pay_d1 = [
                            'rph_type' => 1,
                            'rph_rq_id' => $tr_id,
                            'rph_status' => 2,
                            'rph_amount' => $this->request->getVar('advance_amount'),
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                            'rph_transaction_id' => $this->request->getVar('txnid'),
                        ];

                        $paymentmasterModel->update($this->request->getVar('rpt_id'), $rpt_Data);
                        $paymenthistoryModel->insert($pay_d);
                        $paymenthistoryModel->insert($pay_d1);
                        return $this->respond($response, 200);
                    }
                    //flag=2=>Return tool
                    else if ($flag_id == 2) {
                        $updtdata1 = [
                            'tldt_paymt_flag' => 0,
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
                        $tool_det = $ToolRequestDetailsModel->select('tldt_number,tldt_cstm_id,tldt_cost')->where('tldet_id', $tr_id)->first();
                        $rpt_Data = [
                            'rpt_amount' => 0,
                            'rpt_status' => 1,
                            'rpt_updated_on' => $date,
                            'rpt_updated_by' => $tokendata['uid'],
                            'rpt_transaction_id' => $this->request->getVar('txnid'),
                        ];
                        $paymentmasterModel->update($this->request->getVar('rpt_id'), $rpt_Data);
                        $pay_d = [
                            'rph_type' => 1,
                            'rph_rq_id' => $tr_id,
                            'rph_status' => 1,
                            'rph_amount' => $tool_det['tldt_cost'],
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                            'rph_transaction_id' => $this->request->getVar('txnid'),
                        ];
                        $pay_d1 = [
                            'rph_type' => 1,
                            'rph_rq_id' => $tr_id,
                            'rph_status' => 2,
                            'rph_amount' => $tool_det['tldt_cost'],
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                            'rph_transaction_id' => $this->request->getVar('txnid'),
                        ];

                        $paymenthistoryModel->insert($pay_d);
                        $paymenthistoryModel->insert($pay_d1);
                        $response['ret_data'] = 'success';
                    } else if ($flag_id == 3) {

                        $updtdata1 = [
                            'tldt_paymt_flag' => 1,
                            'tldt_advpaymt_flag' => 0,
                            'tldt_adv_cost' => $flag_id = $this->request->getVar('advance_amount'),
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

                        $cost_after_Adv = $t_details['tldt_cost'] - $this->request->getVar('advance_amount');
                        $payMast = [
                            'rpt_amount' => $cost_after_Adv,
                            'rpt_status' => 0,
                            'rpt_cust_id' => $tokendata['uid'],
                            'rpt_updated_on' => $date,
                            'rpt_updated_by' => $tokendata['uid'],
                            'rpt_transaction_id' => $this->request->getVar('txnid'),
                        ];
                        $paymentmasterModel->update($this->request->getVar('rpt_id'), $payMast);

                        $response['ret_data'] = 'success';
                        $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
                        $pay_d = [
                            'rph_type' => 1,
                            'rph_rq_id' => $tr_id,
                            'rph_status' => 1,
                            'rph_amount' => $this->request->getVar('advance_amount'),
                            'rph_created_by' => $tokendata['uid'],
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                            'rph_transaction_id' => $this->request->getVar('txnid'),
                        ];

                        $paymenthistoryModel->insert($pay_d);
                        $pay_d1 = [
                            'rph_type' => 1,
                            'rph_rq_id' => $tr_id,
                            'rph_status' => 2,
                            'rph_amount' => $this->request->getVar('advance_amount'),
                            'rph_created_by' => $tokendata['uid'],
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                            'rph_transaction_id' => $this->request->getVar('txnid'),
                        ];
                        $paymenthistoryModel->insert($pay_d1);
                        $pay_d2 = [
                            'rph_type' => 1,
                            'rph_rq_id' => $tr_id,
                            'rph_status' => 0,
                            'rph_amount' => $cost_after_Adv,
                            'rph_created_by' => $tokendata['uid'],
                            'rph_created_on' => $date,
                            'rph_created_by' => $tokendata['uid'],
                            'rph_transaction_id' => $this->request->getVar('txnid'),
                        ];
                        $paymenthistoryModel->insert($pay_d2);
                        $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                        $player_id = [];
                        $custhead = "CATRAMS- Payment Success";
                        $custcontent = "" . $tool_det['tldt_number'] . "-Tool is being ready for delivery.";
                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                        if ($ret_res) {
                            $notif_data = [
                                'id'=>$tool_det['tldt_cstm_id'],

                                'sourceid' => $tokendata['uid'],
                                'destid' => $tool_det['tldt_cstm_id'],
                                'nt_req_number' => $tool_det['tldt_number'],
            
                                'nt_sourcetype' => 1,
                                'headers' => $custhead,
                                'content' => $custcontent,
                                'date' => $date,
                                'nt_type'=>0,
                                'nt_request_type'=>1,
                                'nt_type_id'=>$tool_det['tldet_id']
                            ];
                            $notificationmasterController->create_cust_notification($notif_data);
                        }
                    }
                    return $this->respond($response, 200);
                }
            } else if ($next_status = 43) { {
                    //Web Inspection Payment Complete
                    $updtdata1 = [
                        'tldt_paymt_flag' => 0,
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
                    $response['ret_data'] = 'success';
                    return $this->respond($response, 200);
                }
            } else if ($next_status = 49) { {
                    //Tool Purchased
                    $updtdata1 = [
                        'tldt_paymt_flag' => 0,
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

                    $catsalesdataModel = new CatsalesHistoryModel();


                    $data = [
                        'csh_customername' => $this->request->getVar('csh_customername'),
                        'csh_phone' => $this->request->getVar('csh_phone'),
                        'csh_email' => $this->request->getVar('csh_email'),
                        'csh_gstin' => $this->request->getVar('csh_gstin'),
                        'csh_invnumber' => $this->request->getVar('csh_invnumber'),
                        'csh_productname' => $this->request->getVar('csh_productname'),
                        'csh_amount' => $this->request->getVar('csh_amount'),
                        'csh_invdate' => $date,

                    ];

                    $catsalesdataModel->insert($data);



                    $response['ret_data'] = 'success';
                    return $this->respond($response, 200);
                }
            }
        }
    }

    public function success_payment_complete()

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

        $ToolRequestHistoryModel = new ToolRequestHistoryModel();
        $statusmaster = new StatusMasterModel();
        $ToolRequestDetailsModel = new ToolRequestDetailsModel();
        $notificationmasterModel = new NotificationmasterModel();
        $custModel = new CustomerMasterModel();
        $paymentmasterModel = new PaymentTrackermasterModel();
        $paymenthistoryModel = new PaymentHistoryModel();
        $tr_id = $this->request->getVar('tr_id');
        $status_id = $this->request->getVar('status_id');
        $flag_id = $this->request->getVar('flag_id');
        $t_details = $ToolRequestDetailsModel->where('tldet_id', $tr_id)->join('request_payment_tracker', 'rpt_reqt_id=tldet_id')->first();
        $date = date("Y-m-d H:i:s");
        if ($tr_id) {
            if ($status_id == 5) {
                $next_status = 6;
            } else if ($status_id == 15) {
                $next_status = 6;
            } else if ($status_id == 42) {
                $next_status = 43;
            } else if ($status_id = 48) {
                $next_status = 49;
            }
            $upd = [
                'tldt_status' => $next_status,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $indt = [
                'trqh_status_id' => $next_status,
                'trqh_tr_id' => $tr_id,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $res = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $upd);
            $tlhtid = $ToolRequestHistoryModel->insert($indt);
            $tool_req_statusname = $statusmaster->select('sm_name,sm_code')->where('sm_id', $next_status)->first();
                $tool_req_status = $tool_req_statusname['sm_name'];
                $tool_req_status_code = $tool_req_statusname['sm_code'];
            
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
            if ($flag_id) {
                if ($next_status == 6) {
                    //flag =1 =>tool ready to deliver 
                    if ($flag_id == 1) {
                        if($t_details->result=='payment_successfull'){
                        
                            $updtdata1 = [
                                'tldt_paymt_flag' => 0,
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
    
                            $rpt_Data = [
                                'rpt_amount' => $this->request->getVar('advance_amount'),
                                'rpt_status' => 1,
                                'rpt_updated_on' => $date,
                                'rpt_updated_by' => $tokendata['uid'],
                                'rpt_transaction_id' => $this->request->getVar('txnid'),
                            ];
                            $pay_d = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 1,
                                'rph_amount' => $this->request->getVar('advance_amount'),
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
                            $pay_e = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 2,
                                'rph_amount' => $this->request->getVar('advance_amount'),
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
                            $paymentmasterModel->update($this->request->getVar('rpt_id'), $rpt_Data);
                            $paymenthistoryModel->insert($pay_d);
                            $paymenthistoryModel->insert($pay_e);
                            
                            $response['ret_data']='success';
                        }
                        
                        else{
    
                            $updtdata1 = [
                                'tldt_paymt_flag' => 0,
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
                            $rpt_Data = [
                                'rpt_amount' => $this->request->getVar('advance_amount'),
                                'rpt_status' => 0,
                                'rpt_updated_on' => $date,
                                'rpt_updated_by' => $tokendata['uid'],
                                'rpt_transaction_id' => $this->request->getVar('txnid'),
                            ];
                            $pay_d = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 3,
                                'rph_amount' => $this->request->getVar('advance_amount'),
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
                            $pay_e = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 0,
                                'rph_amount' => $this->request->getVar('advance_amount'),
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
                            $paymentmasterModel->update($this->request->getVar('rpt_id'), $rpt_Data);
                            $paymenthistoryModel->insert($pay_d);
                            $paymenthistoryModel->insert($pay_e);
                          
                            $response['ret_data']='success';
                        }
                    
                        $insertdata1 = [
                            'trqh_status_id' => 6,
                            'trqh_tr_id' => $tr_id,
                            'trqh_created_on' => $date,
                            'trqh_created_by' => $tokendata['uid'],
                        ];
                        $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                        $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);
                            //  return $this->respond($response, 200);
                        $response['ret_data'] = 'success';
                        $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
                        $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                        $player_id = [];
                        $custhead = "CATRAMS- Payment Success";
                        $custcontent = "" . $tool_det['tldt_number'] . "-Tool is being ready for delivery.";
                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                        if ($ret_res) {
                            $notificationmasterController = new UsersNotificationController;
                            $notif_data = [
                                'id'=>$tool_det['tldt_cstm_id'],

                                'sourceid' => $tokendata['uid'],
                                'destid' => $tool_det['tldt_cstm_id'],
                                'nt_req_number' => $tool_det['tldt_number'],
            
                                'nt_sourcetype' => 1,
                                'headers' => $custhead,
                                'content' => $custcontent,
                                'date' => $date,
                                'nt_type'=>0,
                                'nt_request_type'=>1,
                                'nt_type_id'=>$tool_det['tldet_id']
                            ];
                            $notificationmasterController->create_cust_notification($notif_data);
                        }

                      
                        return $this->respond($response, 200);
                    }
                    //flag=2=>Return tool
                    else if ($flag_id == 2) {
                        $tool_det = $ToolRequestDetailsModel->select('tldt_number,tldt_cstm_id,tldt_cost')->where('tldet_id', $tr_id)->first();
                       
                        if($t_details->result=='payment_successfull'){
                            $updtdata1 = [
                                'tldt_paymt_flag' => 0,
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

                            $rpt_Data = [
                                'rpt_amount' => $tool_det['tldt_cost'],
                                'rpt_status' => 0,
                                'rpt_updated_on' => $date,
                                'rpt_updated_by' => $tokendata['uid'],
                                'rpt_transaction_id' => $this->request->getVar('txnid'),
                            ];
                            $paymentmasterModel->update($this->request->getVar('rpt_id'), $rpt_Data);
                            $pay_d = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 1,
                                'rph_amount' => $tool_det['tldt_cost'],
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
                        }else{
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
                            $rpt_Data = [
                                'rpt_amount' => $tool_det['tldt_cost'],
                                'rpt_status' => 0,
                                'rpt_updated_on' => $date,
                                'rpt_updated_by' => $tokendata['uid'],
                                'rpt_transaction_id' => $this->request->getVar('txnid'),
                            ];
                            $paymentmasterModel->update($this->request->getVar('rpt_id'), $rpt_Data);
                            $pay_d = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 1,
                                'rph_amount' => $tool_det['tldt_cost'],
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];     
                        }
                      
                        $paymenthistoryModel->insert($pay_d);

                        $response['ret_data'] = 'success';
                    } else if ($flag_id == 3) {
                        $tool_det = $ToolRequestDetailsModel->where('tldet_id', $this->request->getVar('tr_id'))->first();

                        $cost_after_Adv = $t_details['tldt_cost'] - $this->request->getVar('advance_amount');
                        if($t_details->result=='payment_successfull'){

                            $updtdata1 = [
                                'tldt_paymt_flag' => 1,
                                'tldt_advpaymt_flag' => 0,
                                'tldt_adv_cost' => $flag_id = $this->request->getVar('advance_amount'),
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

                            $payMast = [
                                'rpt_amount' => $cost_after_Adv,
                                'rpt_status' => 0,
                                'rpt_cust_id' => $tokendata['uid'],
                                'rpt_updated_on' => $date,
                                'rpt_updated_by' => $tokendata['uid'],
                                'rpt_transaction_id' => $this->request->getVar('txnid'),
                            ];
    
                            $paymentmasterModel->update($this->request->getVar('rpt_id'), $payMast);
    
                            
                            $pay_d = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 1,
                                'rph_amount' => $this->request->getVar('advance_amount'),
                                'rph_created_by' => $tokendata['uid'],
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
    
                            $paymenthistoryModel->insert($pay_d);
                            $pay_d1 = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 2,
                                'rph_amount' => $this->request->getVar('advance_amount'),
                                'rph_created_by' => $tokendata['uid'],
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
                            $paymenthistoryModel->insert($pay_d1);
                            $pay_d2 = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 0,
                                'rph_amount' => $cost_after_Adv,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
                            $paymenthistoryModel->insert($pay_d2);
                        }else
                        {
                            $updtdata1 = [
                                'tldt_paymt_flag' => 1,
                                'tldt_advpaymt_flag' => 1,
                                'tldt_adv_cost' => $flag_id = $this->request->getVar('advance_amount'),
                                'tldt_updated_on' => $date,
                                'tldt_updated_by' => $tokendata['uid'],
                            ];
                            $insertdata1 = [
                                'trqh_status_id' => 5,
                                'trqh_tr_id' => $tr_id,
                                'trqh_created_on' => $date,
                                'trqh_created_by' => $tokendata['uid'],
                            ];
                            $payMast = [
                                'rpt_amount' => $cost_after_Adv,
                                'rpt_status' => 0,
                                'rpt_cust_id' => $tokendata['uid'],
                                'rpt_updated_on' => $date,
                                'rpt_updated_by' => $tokendata['uid'],
                                'rpt_transaction_id' => $this->request->getVar('txnid'),
                            ];
    
                            $paymentmasterModel->update($this->request->getVar('rpt_id'), $payMast);
    
                            $pay_d = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 1,
                                'rph_amount' => $this->request->getVar('advance_amount'),
                                'rph_created_by' => $tokendata['uid'],
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
    
                            $paymenthistoryModel->insert($pay_d);
                            $pay_d1 = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 3,
                                'rph_amount' => $this->request->getVar('advance_amount'),
                                'rph_created_by' => $tokendata['uid'],
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
                            $paymenthistoryModel->insert($pay_d1);
                            $pay_d2 = [
                                'rph_type' => 1,
                                'rph_rq_id' => $tr_id,
                                'rph_status' => 0,
                                'rph_amount' => $cost_after_Adv,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_created_on' => $date,
                                'rph_created_by' => $tokendata['uid'],
                                'rph_transaction_id'=>$this->request->getVar('txnid')
                            ];
                            $paymenthistoryModel->insert($pay_d2);
                        }
                        
                        $results = $ToolRequestDetailsModel->update($this->db->escapeString($tr_id), $updtdata1);
                        $toolhistid = $ToolRequestHistoryModel->insert($insertdata1);

                        
                     
                        $response['ret_data'] = 'success';
                        $target_cust = $custModel->where('cstm_id', $tool_det['tldt_cstm_id'])->first();
                        $player_id = [];
                        $custhead = "CATRAMS- Payment Success";
                        $custcontent = "" . $tool_det['tldt_number'] . "-Tool is being ready for delivery.";
                        array_push($player_id, $target_cust['fcm_token_mobile']);
                        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

                        if ($ret_res) {
                            $notificationmasterController = new UsersNotificationController;
                            $notif_data = [
                                'id'=>$tool_det['tldt_cstm_id'],

                                'sourceid' => $tokendata['uid'],
                                'destid' => $tool_det['tldt_cstm_id'],
                                'nt_req_number' => $tool_det['tldt_number'],
            
                                'nt_sourcetype' => 1,
                                'headers' => $custhead,
                                'content' => $custcontent,
                                'date' => $date,
                                'nt_type'=>0,
                                'nt_request_type'=>1,
                                'nt_type_id'=>$tool_det['tldet_id']
                            ];
                            $notificationmasterController->create_cust_notification($notif_data);
                        }
                    }
                    return $this->respond($response, 200);
                }
            } else if ($next_status = 43) {
                 {
                    //Web Inspection Payment Complete
                    $updtdata1 = [
                        'tldt_paymt_flag' => 0,
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
                    $response['ret_data'] = 'success';
                    return $this->respond($response, 200);
                }
            } else if ($next_status = 49) {
                 {

                $rpt_data=$paymentmasterModel->where('rpt_type',2)->where('rpt_reqt_id',$tr_id)->first();
                    //Tool Purchased
                    $updtdata1 = [
                        'tldt_paymt_flag' => 0,
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

                    $payMast = [
                        'rpt_amount' => $this->request->getVar('purchase_amount'),
                        'rpt_status' => 0,
                        'rpt_cust_id' => $tokendata['uid'],
                        'rpt_updated_on' => $date,
                        'rpt_updated_by' => $tokendata['uid'],
                        'rpt_transaction_id' => $this->request->getVar('txnid'),
                    ];

                    $paymentmasterModel->update($rpt_data['rpt_id'], $payMast);
                    $pay_p = [
                        'rph_type' => 1,
                        'rph_rq_id' => $tr_id,
                        'rph_status' => 0,
                        'rph_amount' => $this->request->getVar('purchase_amount'),
                        'rph_created_by' => $tokendata['uid'],
                        'rph_created_on' => $date,
                        'rph_created_by' => $tokendata['uid'],
                        'rph_transaction_id'=>$this->request->getVar('txnid')
                    ];
                    $paymenthistoryModel->insert($pay_p);
                    $pay_i = [
                        'rph_type' => 1,
                        'rph_rq_id' => $tr_id,
                        'rph_status' => 1,
                        'rph_amount' => $this->request->getVar('purchase_amount'),
                        'rph_created_by' => $tokendata['uid'],
                        'rph_created_on' => $date,
                        'rph_created_by' => $tokendata['uid'],
                        'rph_transaction_id'=>$this->request->getVar('txnid')
                    ];

                    $paymenthistoryModel->insert($pay_i);
                    $pay_s = [
                        'rph_type' => 1,
                        'rph_rq_id' => $tr_id,
                        'rph_status' => 2,
                        'rph_amount' => $this->request->getVar('purchase_amount'),
                        'rph_created_by' => $tokendata['uid'],
                        'rph_created_on' => $date,
                        'rph_created_by' => $tokendata['uid'],
                        'rph_transaction_id'=>$this->request->getVar('txnid')
                    ];
                    $paymenthistoryModel->insert($pay_s);
                   

                    $catsalesdataModel = new CatsalesHistoryModel();


                    $data = [
                        'csh_customername' => $this->request->getVar('csh_customername'),
                        'csh_phone' => $this->request->getVar('csh_phone'),
                        'csh_email' => $this->request->getVar('csh_email'),
                        'csh_gstin' => $this->request->getVar('csh_gstin'),
                        'csh_invnumber' => $this->request->getVar('csh_invnumber'),
                        'csh_productname' => $this->request->getVar('csh_productname'),
                        'csh_amount' => $this->request->getVar('csh_amount'),
                        'csh_invdate' => $date,

                    ];

                    $catsalesdataModel->insert($data);



                    $response['ret_data'] = 'success';
                    return $this->respond($response, 200);
                }
            }
        }
    }

    public function fetch_dashb_details(){
        $toolRequestModel = new ToolRequestDetailsModel();
        $tool_open = 0;
        $tool_closed = 0;
        $tool_pay_pend = 0;
        $pending_tool = 0;
        $total_hold_tool = 0;
        $open_ticket_tool=array();

        $tool_data = $toolRequestModel->where('tldt_active_flag', 0)
            ->select('tldt_number,
               tldet_id,
                sm_id,
                sm_name,
                sm_pk_id,
                tldt_created_on,
                tldt_status,
                tldt_hold_flag')
            ->join('status_master', 'sm_id=tldt_status')
            ->join('request_payment_tracker', 'rpt_reqt_id=tldet_id', 'left')
            ->join('tool_details', 'tool_id=tldt_tool_id')
            ->join('customer_master', 'cstm_id=tldt_cstm_id')
            ->findAll();

        for ($i = 0; $i < sizeof($tool_data); $i++) {

            if ($tool_data[$i]['tldt_status'] == 17 || $tool_data[$i]['tldt_status'] == 2) {
                $open_ticket_tool[$i] = $tool_data[$i];
                $tool_open = $tool_open + 1;
            } else if ($tool_data[$i]['tldt_status'] == 49 || $tool_data[$i]['tldt_status'] == 16 || $tool_data[$i]['tldt_status'] == 7) {
                $tool_closed = $tool_closed + 1;
            } else if ($tool_data[$i]['tldt_status'] == 3 || $tool_data[$i]['tldt_status'] == 5 || $tool_data[$i]['tldt_status'] == 15 || $tool_data[$i]['tldt_status'] == 42 || $tool_data[$i]['tldt_status'] == 48) {
                $tool_pay_pend = $tool_pay_pend + 1;
            } else {
                $pending_tool = $pending_tool + 1;
            }

            if ($tool_data[$i]['tldt_hold_flag'] == 1) {
                $total_hold_tool = $total_hold_tool + 1;
            }
        }


        $ret_data=[

            'tool_data' => $tool_data,
            'tool_open' => $tool_open,
            "open_ticket_tool"=>$open_ticket_tool,
            'tool_closed' => $tool_closed,
            'tool_pay_pend' => $tool_pay_pend,
            'inprogress_tool' => $pending_tool,
            'total_hold_tool' => $total_hold_tool,
        ];
        return $ret_data;
    }

    




    }

    

