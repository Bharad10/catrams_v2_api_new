<?php

namespace App\Controllers\Payment;

use App\Controllers\ServiceRequest\ServiceRequestMasterController;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Payment\PaymentHistoryModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\ServiceRequest\ServiceRequestItemsModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\System\NotificationmasterModel;
use App\Models\System\OrderMasterModel;
use App\Models\ToolRequest\ToolDetailsModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\User\UsersModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Validation;
use Config\Commonutils;
use Easebuzz;

class PaymentMasterController extends ResourceController
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
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $ordermasterModel= new OrderMasterModel();
        $serv_pay=[];
        $tool_pay=[];
        $order_pay=[];
        $tool_pay = $toolrequestmasterModel
        ->select('
                    sm_name,
                    sm_pk_id,
                    tldet_id,
                    tldt_number,
                    cstm_name,
                    rpt_created_on,
                    rpt_updated_on,
                    rpt_type,
                    rpt_status
                    ')
            ->where('tldt_delete_flag', 0)
            ->where('tldt_paymt_flag', 1)
            ->where('tldt_status!=', 2)
            ->join('status_master', 'sm_id=tldt_status')
            ->join('customer_master', 'cstm_id=tldt_cstm_id')
            ->join('request_payment_tracker', 'rpt_reqt_id=tldet_id')
            ->orderBy('tldet_id', 'desc')
            ->findAll();
        $serv_pay = $servicerequestMasterModel
        ->select('
                    sm_name,
                    sm_pk_id,
                    serm_id,
                    serm_number,
                    cstm_name,
                    rpt_created_on,
                    rpt_updated_on,
                    rpt_type,
                    rpt_status
                    ')
            ->where('serm_deleteflag', 0)
            ->join('status_master', 'sm_id=serm_status')
            ->join('customer_master', 'cstm_id=serm_custid')
            ->join('request_payment_tracker','rpt_reqt_id=serm_id','left')
            ->orderBy('serm_id', 'desc')
            ->findAll();
            $order_pay = $ordermasterModel
            ->select('
                    sm_name,
                    sm_pk_id,
                    order_id,
                    order_number,
                    cstm_name,
                    rpt_created_on,
                    rpt_updated_on
                    rpt_type,
                    rpt_status
                    ')
            ->where('order_delete_flag', 0)
            ->join('status_master', 'sm_id=order_status')
            ->join('customer_master', 'cstm_id=order_created_by')
            ->join('request_payment_tracker','rpt_reqt_id=order_id','left')
            ->orderBy('order_id', 'desc')
            ->findAll();

            if(sizeof($serv_pay)>0){
                $j=0;
                for($i=0;$i<sizeof($serv_pay);$i++){
                    if($serv_pay[$i]['rpt_type']==1){
                        if($serv_pay[$i]['rpt_status']==0||$serv_pay[$i]['rpt_status']==2){
                            $serm_pay[$j]=$serv_pay[$i];
                            $j++;
                        }
                    }
                    
                }
            }
            if(sizeof($order_pay)>0){
                $j=0;
                for($i=0;$i<sizeof($order_pay);$i++){
                    if($order_pay[$i]['rpt_type']==3){
                        if($order_pay[$i]['rpt_status']==0||$order_pay[$i]['rpt_status']==2){
                            $orm_pay[$j]=$order_pay[$i];
                            $j++;
                        }
                    }
                    
                }
            }

       
        if ($tool_pay || $serm_pay) {
            $pay_array = array_merge($tool_pay, $serm_pay);
            $response = [
                'ret_data' => 'success',
                'tool_pay' => $tool_pay,
                'serv_pay' => $serm_pay,
                'pay_array' => $pay_array,
                'order_array' => $order_pay

            ];
        } else {
            $response = [
                'Message' => 'No active request',
                'flag' => '404'
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
        $custModel = new CustomerMasterModel();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
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

    public function initiate_alert()
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

        $notificationmasterModel = new NotificationmasterModel();
        $rules = [
            'tldet_id' => 'required',
            'cstm_id' => 'required',
            'tldt_cost' => 'required',
            'tldt_number' => 'required',
            'tool_name' => 'required',
            'rpt_amount' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $custModel = new CustomerMasterModel();
        $tool_det = new ToolDetailsModel();
        $date = date("Y-m-d H:i:s");
        $target_cust = $custModel->where('cstm_id', $this->request->getVar('cstm_id'))->first();
        $player_id = [];
        $custhead = "CATRAMS- PAYMENT DUE!!!";
        $custcontent = "" . $this->request->getVar('tldt_number') . "- This is to remind that you have a due of Rs" . $this->request->getVar('rpt_amount') . "";
        array_push($player_id, $target_cust['fcm_token_mobile']);
        if (sizeof($player_id) > 0) $ret_res = $commonutils->sendMessage($custhead, $custcontent, $player_id);

        if ($ret_res) {
            $notif_data = [
                'nt_sourceid' => $tokendata['uid'],
                'nt_destid' => $this->request->getVar('cstm_id'),
                'nt_req_number' => $this->request->getVar('tldt_number'),
                'nt_sourcetype' => 1,
                'nt_header' => $custhead,
                'nt_content' => $custcontent,
                'nt_created_on' => $date
            ];
            $notificationmasterModel->insert($notif_data);

            $response['ret_data'] = 'success';
            return $this->respond($response, 200);
        }
    }

    public function fetch_pay_hist()
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
        $paymenthistory = new PaymentHistoryModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $ordermasterModel = new OrderMasterModel();

        $total_array = array();
        $data = $paymenthistory
        ->select('rph_type,
                 rph_id,
                 rph_created_on,
                 rph_status,
                 rph_rq_id,
                 rph_transaction_id,
                 rph_delete_flag,
                 rph_amount,
                 ')
            ->where('rph_delete_flag', 0)
            ->orderBy('rph_id', 'desc')
            ->findAll();

        if ($data) {
            $resultArray = [];

            foreach ($data as $payment) {
                if ($payment['rph_type'] == 0) {
                    $serviceRequest = $servicerequestMasterModel
                    ->select('
                    sm_name,
                    sm_pk_id,
                    serm_id,
                    serm_number,
                    cstm_name,
                    serm_vendor_flag,
                    ')
                        ->where('serm_deleteflag', 0)
                        ->join('status_master', 'sm_id=serm_status')
                        ->join('customer_master', 'cstm_id=serm_custid')
                        ->first();
                    $resultArray[] = $serviceRequest + $payment;
                } elseif ($payment['rph_type'] == 1) {
                    $toolRequest = $toolrequestmasterModel
                    ->select('
                    sm_name,
                    sm_pk_id,
                    tldet_id,
                    tldt_number,
                    cstm_name,
                    ')
                        ->join('status_master', 'sm_id=tldt_status')
                        ->join('customer_master', 'cstm_id=tldt_cstm_id')
                        ->first();
                    $resultArray[] = $toolRequest + $payment;
                } elseif ($payment['rph_type'] == 2) {
                    $orderMaster = $ordermasterModel
                    ->select('
                    sm_name,
                    sm_pk_id,
                    order_id,
                    order_number,
                    cstm_name,
                    ')
                        ->join('status_master', 'sm_id=order_status')
                        ->join('customer_master', 'cstm_id=order_created_by', 'left')
                        ->first();

                    $resultArray[] = $orderMaster + $payment;
                }
            }

            $response['ret_data'] = 'success';
            $response['paydata'] = $resultArray;
        } else {
            $response = ['Message' => 'fail'];
        }
        return $this->respond($response, 200);
    }

    public function fetch_specific_pay_hist()
    {

        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $custModel = new CustomerMasterModel();
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

        $notificationmasterModel = new NotificationmasterModel();
        $paymenthistory = new PaymentHistoryModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $servicerequestMasterModel = new ServiceRequestMasterModel();
        $ordermasterModel = new OrderMasterModel();
        $userModel = new UsersModel();
        $rules = [
            'serm_id' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $total_array = array();
        $data = $paymenthistory
            ->where('rph_delete_flag', 0)
            ->where('rph_type', 0)
            ->where('rph_rq_id',  $this->request->getVar('serm_id'))
            ->whereIn('rph_status', [2,3])
            ->orderBy('rph_id', 'desc')
            ->findAll();

        if ($data) {
            $resultArray = [];

            foreach ($data as $payment) {
                if ($payment['rph_by_type'] == 0) {
                    $customer = $custModel->where('cstm_id', $payment['rph_created_by'])->first();
                    $resultArray[] = $customer + $payment;
                } else {
                    $users = $userModel->where('us_id', $payment['rph_created_by'])->first();

                    $resultArray[] = $users + $payment;
                }
            }

            $response['ret_data'] = 'success';
            $response['payhist'] = $resultArray;
        } else {
            $response = ['Message' => 'fail'];
        }
        return $this->respond($response, 200);
    }

    public function admin_payment($data)
    {
        $validModel = new Validation();
        $commonutils = new Commonutils();
        $heddata = $this->request->headers();
        $custModel = new CustomerMasterModel();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $userModel = new UsersModel();
            $users = $userModel->where("us_id", $tokendata['uid'])->where("us_delete_flag", 0)->first();
            if (!$users) return $this->fail("invalid user", 400);
        } else {
            return $this->fail("invalid user", 400);
        }
        $paymenttrackerModel = new PaymentTrackermasterModel();
        $paymenthistoryModel = new PaymentHistoryModel();
    }



    public function initiatePayment()

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

        //udf1={Request Type} 0-Service Request 1-Tool Request 2-Purchase Order
        //udf2={Request ID}

        $MERCHANT_KEY = getenv('merchantKey');
        $SALT = getenv('salt');
        $ENV = 'test';
        $surl = getenv('surl');
        $furl = getenv('furl');

        $aud = $tokendata['aud'] == 'customer' ? 'C' : 'U';
        if( $tokendata['aud'] == 'customer' ){
            $udf3=0;
            $udf4=$customer['cstm_id'];
        }else{
            $udf4=$users['us_id'];
            $udf3=1;
        }
        if($this->request->getVar('udf1')==0){
            $req_type='SR'.'_'.$this->request->getVar('udf2');
        }else if($this->request->getVar('udf1')==1){
            $req_type='TR'.'_'.$this->request->getVar('udf2');
        }else if($this->request->getVar('udf1')==2){

            $req_type='OR'.'_'.$this->request->getVar('udf2');
        }
       

        $txn_id = 'RAMS_' . $aud . $tokendata['uid'] . '_' .$req_type. '_'. $this->request->getVar('phone') . '_' . time();
        $data = [];
        //  return $this->respond($txn_id,200);
    
        
        $data = [
            'key' => $MERCHANT_KEY,
            'txnid' => $txn_id,
            'amount' => $this->request->getVar('amount'),
            'productinfo' => $this->request->getVar('productinfo'),
            'firstname' => $this->request->getVar('firstname'),
            'phone' => $this->request->getVar('phone'),
            'email' => $this->request->getVar('email'),
            'surl' => $surl,
            'furl' => $furl,
            'udf1' => $this->request->getVar('udf1'), //udf1-Request Type -{0-Service Request,1-Tool Request,2-Order Request}
            'udf2' => $this->request->getVar('udf2'), //udf2-Request ID
            'udf3' => $udf3, //udf3-Payment Intitiated User-{0-Customer,1-Cat User}
            'udf4' => $udf4,//udf4-User ID
            'udf5' => $this->request->getVar('udf5'),
            'udf6' => $this->request->getVar('udf6'),
            'udf7' => $this->request->getVar('udf7'),
            'udf8' => $this->request->getVar('udf8'),
            'udf9' => $this->request->getVar('udf9'),
            'udf10' => $this->request->getVar('udf10'),
            'address1' => $this->request->getVar('address1'),
            'address2' => $this->request->getVar('address2'),
            'city' => $this->request->getVar('city'),
            'state' => $this->request->getVar('state'),
            'country' => $this->request->getVar('country'),
            'zipcode' => $this->request->getVar('zipcode'),
            'show_payment_mode' => $this->request->getVar('show_payment_mode'),
            'split_payments' => $this->request->getVar('split_payments'),
            'request_flow' => $this->request->getVar('request_flow'),
            'sub_merchant_id' => $this->request->getVar('sub_merchant_id'),
            'payment_category' => $this->request->getVar('payment_category'),
            'account_no' => $this->request->getVar('account_no'),
            'ifsc' => $this->request->getVar('ifsc')
        ];

        $hash_data = $commonutils->genearate_key($data, $SALT);

        $data['hash']  = $hash_data;
        //    return $this->respond($data, 200);

        $curl = curl_init();


        curl_setopt_array($curl, [
            CURLOPT_URL => "https://testpay.easebuzz.in/payment/initiateLink",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/x-www-form-urlencoded"
            ],
        ]);


        $response = curl_exec($curl);
        $err = curl_error($curl);
        $response_array = json_decode($response, true);

        $access_token = $response_array['data'];

        curl_close($curl);


        if ($err) {
            $ret_data = [
                'ret_data' => 'error',
                'Message' => $err,
            ];
        } else {
            $ret_data = [
                'ret_data' => 'success',
                'access_token' => $access_token,
                'payment_details' => $data
            ];
        }
        return $this->respond($ret_data, 200);
    }

    public function recieve_transaction($input)

    {


        $date = date("Y-m-d H:i:s");
        $paymenthistoryModel=new PaymentHistoryModel();
        $paymentMasterModel = new PaymentTrackermasterModel();
        $status=0;
        if($input){
            if($input['status']=='success'){
              if($input['udf1']==0) {
                    $data= $this->serv_balance_amount($input);
                }else if($input['udf1']==1){
                   
                }else{

                }
              
            }
            else if($input['status']=='failure'){
               
                $data=$this->failed_transaction($input);
            }else{
                $data=$this->failed_transaction($input);
            }
            $resposne=
            ['ret_data'=>'success','Message'=>'Details Recieved!!'];
            return $this->respond($resposne,200);
            }

    }

    public function serv_balance_amount($data)
    
    {

       

        $servicerequestMasterModel= new ServiceRequestMasterModel();
        $paymenttrackerModel= new PaymentTrackermasterModel();
        $paymenthistoryModel= new PaymentHistoryModel();
        $serequestitemsModel= new ServiceRequestItemsModel();
        $ServiceController= new ServiceRequestMasterController;
        $date = date("Y-m-d H:i:s");


        $serm_data=$servicerequestMasterModel->where('serm_id',$data->udf2)->first();
        $sitems_data=$serequestitemsModel->where('sitem_serid',$data->udf2)->findAll();
        $payment_details=$paymenttrackerModel->where('rpt_type',1)->where('rpt_reqt_id',$data->udf2)->first();


        $balance_amount =( ($serm_data['serm_discount_amount'])>0)? $serm_data['serm_discount_amount'] - $serm_data['serm_custpay_amount']:
        $serm_data['serm_cost'] -$serm_data['serm_custpay_amount'];

        if($balance_amount!=0){
            
            $due_amount=$balance_amount-$data->amount;
        }else{
            $due_amount=$balance_amount;
        }
        

        if ($due_amount == 0) {
            $track_data = [
                'rpt_amount' => $data->amount,
                'rpt_status' => 1,
                'rpt_updated_on' => $date,
                'rpt_updated_by' => $data->udf4,
                'rpt_transaction_id' => $data->txnid,
            ];
            
            if (count($sitems_data) > 0) {
           
                foreach($sitems_data as $each_data){
                    $infdata=[
                        'sitem_updatedby' =>  $data->udf4,
                        'sitem_updatedon' => $date,
                        'sitem_paid_status'=>2  
                    ];
                $serequestitemsModel->update($each_data['sitem_id'],$infdata);
                    $infdata=[];
                }
            }
            $hist3 = [
                            'rph_type' => 0,
                            'rph_rq_id' => $serm_data['serm_id'],
                            'rph_status' => 2,
                            'rph_amount' => $data->amount,
                            'rph_created_on' => $date,
                            'rph_created_by' => $data->udf4,
                            'rph_transaction_id'=>$data->txnid,
                    ];
                        $paymenthistoryModel->insert($hist3);
        } else {
            if (count($sitems_data) > 0) {
           
                foreach($sitems_data as $each_data){
                    $infdata=[
                        'sitem_updatedby' =>  $data->udf4,
                        'sitem_updatedon' => $date,
                        'sitem_paid_status'=>1
                    ];
                    $serequestitemsModel->update($each_data['sitem_id'],$infdata);
                    $infdata=[];
                }
            }
            $hist4 = [
                'rph_status' => 2,
                'rph_type' => 0,
                'rph_rq_id' => $serm_data['serm_id'],
                'rph_amount' => $data->amount,
                'rph_created_on' => $date,
                'rph_created_by' => $data->udf4,
                'rph_transaction_id'=>$data->txnid,
            ];
            $hist5 = [
                'rph_type' => 0,
                'rph_rq_id' => $serm_data['serm_id'],
                'rph_amount' => $balance_amount,
                'rph_created_on' => $date,
                'rph_created_by' => $data->udf4,
                'rph_transaction_id'=>$data->txnid,
            ];

            $paymenthistoryModel->insert($hist4);
            $paymenthistoryModel->insert($hist5);
            $track_data = [
                'rpt_amount' => $balance_amount,
                'rpt_status' => 0,
                'rpt_updated_on' => $date,
                'rph_created_by' => $data->udf4,
                'rph_transaction_id'=>$data->txnid,
            ];
        }

        $paymenttrackerModel->update($payment_details['rpt_id'],$track_data);

        if($due_amount == 0 &&$serm_data['serm_status']==30){
           $r_data= $ServiceController->success_serv_payment($data);
        }
        return $payment_details;
    }

    public function failed_transaction($data)

    {


        $servicerequestMasterModel= new ServiceRequestMasterModel();
        $paymenttrackerModel= new PaymentTrackermasterModel();
        $paymenthistoryModel= new PaymentHistoryModel();
        $serequestitemsModel= new ServiceRequestItemsModel();
        $date = date("Y-m-d H:i:s");


        $serm_data=$servicerequestMasterModel->where('serm_id',$data->udf2)->first();
        $sitems_data=$serequestitemsModel->where('sitem_serid',$data->udf2)->findAll();
        $payment_details=$paymenttrackerModel->where('rpt_type',1)->where('rpt_reqt_id',$data->udf2)->first();

        $track_data = [
            'rpt_amount' => $serm_data['serm_cost'],
            'rpt_status' => 0,
            'rpt_updated_on' => $date,
            'rpt_updated_by' => $data->udf4,
            'rpt_transaction_id' => $data->txnid,
        ];
        
        if (count($sitems_data) > 0) {
       
            foreach($sitems_data as $each_data){
                $infdata=[
                    'sitem_updatedby' =>  $data->udf4,
                    'sitem_updatedon' => $date,
                    'sitem_paid_status'=>0 
                ];
                $data=$serequestitemsModel->update($each_data['sitem_id'],$infdata);
                $infdata=[];
            }
        }
        $hist3 = [
                        'rph_type' => 0,
                        'rph_rq_id' => $serm_data['serm_id'],
                        'rph_status' => 3,
                        'rph_amount' => $serm_data['serm_custpay_amount'],
                        'rph_created_on' => $date,
                        'rph_created_by' => $data->udf4,
                        'rph_transaction_id'=>$data->txnid,
                ];
                    $paymenthistoryModel->insert($hist3);
                    $hist3 = [
                        'rph_type' => 0,
                        'rph_rq_id' => $serm_data['serm_id'],
                        'rph_status' => 0,
                        'rph_amount' => $serm_data['serm_cost'],
                        'rph_created_on' => $date,
                        'rph_created_by' => $data->udf4
                ];
                    $paymenthistoryModel->insert($hist3);
                return $hist3;

    }

    
    

    
}
