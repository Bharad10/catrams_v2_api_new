<?php

namespace App\Controllers\Order;

use App\Controllers\Payment\PaymentMasterController;
use App\Controllers\Shipment\ShipmentMasterController;
use App\Controllers\System\UsersNotificationController;
use App\Models\Approval\ApprovalmasterModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Media\RequestMediaModel;
use App\Models\Order\OrderrequesttrackerModel;
use App\Models\Payment\PaymentHistoryModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\Sequence\SequenceGeneratorModel;
use App\Models\Shipment\ShipmentMasterModel;
use App\Models\StatusMaster\StatusMasterModel;
use App\Models\System\CustomerDiscountModel;
use App\Models\System\ExpenseTrackerModel;
use App\Models\System\OrderHistoryModel;
use App\Models\System\OrderItemsModel;
use App\Models\System\OrderMasterModel;
use App\Models\System\ToolTrackerModel;
use App\Models\ToolRequest\ToolDetailsModel;
use App\Models\ToolRequest\ToolRequestTrackerModel;
use App\Models\User\UsersModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\RESTful\ResourceController;
use Config\Validation;
use Config\Commonutils;

class OrderMasterController extends ResourceController
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

        $orderMasterModel = new OrderMasterModel();
        $orders_data = $orderMasterModel
            ->where('order_delete_flag', 0)
            ->join('customer_master', 'cstm_id=order_created_by')
            ->join('status_master', 'sm_id=order_status')
            ->orderBy('order_id', 'desc')
            ->findAll();
        $open=0;
            for($i=0;$i<sizeof($orders_data);$i++){

                $open=  $orders_data[$i]['sm_id']==51?$open+1:$open;
            }

        if ($orders_data) {
            $response = [
                'ret_data' => 'success',
                'data' => $orders_data,
                'open_tickets'=>$open
            ];
            return $this->respond($response, 200);
        } else {
            $response = [
                'ret_data' => 'error'
            ];
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

        $CustomerMasterModel = new CustomerMasterModel();
        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $statusMasterModel = new StatusMasterModel();
        $orderTrackerModel = new OrderrequesttrackerModel();
        $approvalmasterModel = new ApprovalmasterModel();
        $expensetrackerModel = new ExpenseTrackerModel();
        $customerdiscountModel =  new CustomerDiscountModel();
        $requestmediaModel = new RequestMediaModel();
        $shipmentMasterModel = new ShipmentMasterModel();
        $paymenthistoryModel= new PaymentHistoryModel();



        $order_data = $orderMasterModel->where('order_id', base64_decode($id))
            ->join('status_master', 'sm_id=order_status', 'left')
            ->join('customer_master', 'cstm_id=order_created_by')
            ->join('request_payment_tracker', 'rpt_reqt_id=order_id', 'left')
            ->where('rpt_type', 3)
            ->first();

        if ($order_data) {

            $payment_history=$paymenthistoryModel->where('rph_type',2)
            ->where('rph_rq_id', base64_decode($id))
            ->whereIn('rph_status',[2,3])
            ->orderBy('rph_id','desc')
            ->findAll();

            $order_data['payment_history'] = sizeof($payment_history)>0 ? $payment_history : [];
            $ship_det = $shipmentMasterModel
                ->where('shm_delete_flag', 0)
                ->where('shm_type', 1)
                ->where('shm_request_id', base64_decode($id))
                ->first();
           


            $order_data['shipment_details'] = $ship_det ? $ship_det : [];

            $tr_media = $requestmediaModel
                ->where('rmedia_delete_flag', 0)
                ->where('rmedia_url_type', 2)
                ->where('rmedia_by_type', 0)
                ->where('rmedia_request_id', base64_decode($id))
                ->findAll();

            $order_data['Tracking_Media']['User'] = sizeof($tr_media) > 0 ? $tr_media : [];


            $order_data['customer_dicounts'] = $order_data['cstm_type'] == 1 ? $customerdiscountModel->where('cd_active_flag', 0)->first() : [];

            if ($order_data['customer_dicounts'] != null) {

                $discount_price = $order_data['customer_dicounts']['cd_type'] == 1 ?
                    ($order_data['order_total_cost'] * $order_data['customer_dicounts']['cd_rate']) / 100 :
                    $order_data['customer_dicounts']['cd_rate'];

                $grand_total = $order_data['order_total_cost'] - $discount_price;
                $order_data['customer_dicounts']['discount_price'] = $discount_price;
                $order_data['customer_dicounts']['grand_total'] = $grand_total;
            }

            $order_history = $orderHistoryModel
                ->where('ohist_delete_flag', 0)
                ->join('status_master', 'sm_id=ohist_order_status', 'left')
                ->Where('ohist_order_id', base64_decode($id))
                ->findAll();
            $additional_expenses = $expensetrackerModel
                ->where('expt_type', 2)
                ->where('expt_rq_id', base64_decode($id))
                ->findAll();

            $additional_expenses = (sizeof($additional_expenses) > 0) ? $additional_expenses : 0;
            $order_data['additional_expenses'] = $additional_expenses;
        }
        $items = $orderitemsModel->where('oitem_order_id', base64_decode($id))
            ->where('oitem_delete_flag', 0)
            ->join('tool_details', 'tool_id=oitem_tool_id', 'left')
            ->findAll();

        $images = $orderTrackerModel->where('ort_order_id', base64_decode($id))->findAll();
        if (sizeof($images) == 0) {
            $images = 0;
            $approvals = 0;
        } else {
            $approvals = $approvalmasterModel
                ->where('am_type', 10)
                ->where('am_reqid', base64_decode($id))
                ->orderBy('am_id ', 'desc')
                ->first();
            if (!$approvals) {
                $approvals = 0;
            }
        }

        if ($order_data) {
            $response['ret_data'] = "success";
            $response['order_data'] = $order_data;
            $response['items'] = $items;
            $response['order_history'] = $order_history;
            $response['images'] = $images;
            $response['approvals'] = $approvals;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No details for this order';
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
            'customer_id' => 'required',
            'order_items' => 'required',
            'order_total_cost' => 'required',
            'order_address' => 'required',
            'order_pay' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $services = $this->request->getVar('order_items');
        $seqModel = new SequenceGeneratorModel();
        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $tooltrackerModel = new ToolTrackerModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $paymenthistory = new PaymentHistoryModel();
        $expensetrackerModel = new ExpenseTrackerModel();
        $paymentmasterController = new PaymentMasterController;
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $seq = $seqModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_OR" . $seq[0]['order_sequence']);

        $inData = [
            'order_created_by' => $this->request->getVar('customer_id'),
            'order_total_cost' => $this->request->getVar('order_total_cost'),
            'order_number' => $nextval,
            'order_address' => $this->request->getVar('order_address'),
            'order_status' => 51,
            'order_pay' => $this->request->getVar('order_pay'),
            'order_created_by' => $tokendata['uid'],
            'order_created_on' => $date,
            'order_updated_on' => $date,
            'order_updated_by' => $tokendata['uid'],

        ];
        $result = $orderMasterModel->insert($inData);




        if ($this->request->getVar('expense_cost')) {
            $add_exp = [
                'expt_type' => 3,
                'expt_rq_id' => $result,
                'expt_name' => $this->request->getVar('expense_name'),
                'expt_cost' => $this->request->getVar('expense_cost'),
                'expt_created_by' => $tokendata['uid'],
                'expt_created_on' => $date,
                'expt_updated_by' => $tokendata['uid'],
                'expt_updated_on' => $date,
            ];
            $exp_data = $expensetrackerModel->insert($add_exp);
        }
        if (count($services) > 0) {

            $in_data = array();
            for ($i = 0; $i < count($services); $i++) {
                $infdata = [
                    'oitem_tool_id'   => $services[$i]->oitem_tool_id,
                    'oitem_order_id'   => $result,
                    'oitem_cost' => $services[$i]->oitem_cost,
                    'oitem_quantity' => $services[$i]->oitem_quantity,
                    'oitem_created_by' => $tokendata['uid'],
                    'oitem_created_on' => $date,
                    'oitem_updated_on' => $date,
                    'oitem_updated_by' => $tokendata['uid'],
                ];
                array_push($in_data, $infdata);
                $req_data = $ToolDetailsModel->where('tool_id', $services[$i]->oitem_tool_id)->first();

                $revised_master = [
                    'tool_sale_quantity' => ($req_data['tool_sale_quantity']) - ($services[$i]->oitem_quantity),
                    'tool_total_quantity' => ($req_data['tool_total_quantity']) - ($services[$i]->oitem_quantity),
                    'tool_updated_on' => $date,
                ];
                $revised_data = [
                    'trk_tool_id' => $services[$i]->oitem_tool_id,
                    'trk_type' => 2,
                    'trk_status' => 51,
                    'trk_rq_id' => $result,
                    'trk_quant' => $services[$i]->oitem_quantity,
                    'trk_created_by' => $tokendata['uid'],
                    'trk_created_on' => $date,
                    'trk_updated_by' => $tokendata['uid'],
                    'trk_updated_on' => $date,

                ];

                $tooltrackM = $tooltrackerModel->insert($revised_data);
                $tooltrack = $ToolDetailsModel->update($services[$i]->oitem_tool_id, $revised_master);
            }
            $ret = $orderitemsModel->insertBatch($in_data);
        }

        $paymentTrackerModel = new PaymentTrackermasterModel();
        $payMast = [
            'rpt_reqt_id' => $result,
            'rpt_type' => 3,
            'rpt_amount' => $this->request->getVar('order_total_cost'),
            'rpt_status' => $this->request->getVar('order_pay'),
            'rpt_cust_id' => $tokendata['uid'],
            'rpt_created_on' => $date,
            'rpt_created_by' => $tokendata['uid'],
            'rpt_updated_on' => $date,
            'rpt_updated_by' => $tokendata['uid'],
        ];

        $paymentTrackerModel->insert($payMast);

        if ($this->request->getVar('order_pay') != 2) {
            $payhist = [
                'rph_type' => 2,
                'rph_rq_id' =>  $result,
                'rph_status' => 0,
                'rph_amount' => $this->request->getVar('order_total_cost'),
                'rph_created_on' => $date,
                'rph_created_by' => $tokendata['uid'],
            ];
            $paymenthistory->insert($payhist);



            if ($ret_data['ret_data'] = 'succes') {
                $response = [];
                $seq = (intval($seq[0]['order_sequence']) + 1);
                $seq_data = ['order_sequence' => $seq];
                $seqModel->update(1, $seq_data);


                $response = [
                    'ret_data' => 'success',
                    'Order_id' => $result,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail'
                ];
            }
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
    public function update($id = null)
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
            'order_id' => 'required',
            'status_id' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $statusMasterModel = new StatusMasterModel();
        $ordermastercontroller = new OrderMasterController;
        $date = date("Y-m-d H:i:s");
        $order_det = $orderMasterModel->where('order_id', $this->request->getVar('order_id'))->first();
        $target_cust = $custModel->where('cstm_id', $order_det['order_created_by'])->first();
        $player_id = [];

        
        if ($this->request->getVar('status_id') == 51) {
            $tool_img = $this->request->getVar('tool_images');

            $track_data = [
                'track_id' => $this->request->getVar('track_id'),
                'track_url' => $this->request->getVar('track_url'),
                'track_reference' => $this->request->getVar('track_reference'),
            ];
            $order_id = $ordermastercontroller->order_shipped($tokendata['uid'], $order_det, $tool_img, $track_data);
            $custhead = "CATRAMS- Order Shipped!!";
            $custcontent = "" . $order_det['order_number'] . " Your Order Has Been Shipped!!.";
        } else if ($this->request->getVar('status_id') == 52) {
            $order_id = $ordermastercontroller->order_Delivered($tokendata['uid'], $order_det);
            $custhead = "CATRAMS- Order Delivered!!";
            $custcontent = "" . $order_det['order_number'] . "- Your Order Has Been Delivered!!.";
        } else if ($this->request->getVar('status_id') == 41) {

            $order_id = $ordermastercontroller->order_canceled($tokendata['uid'], $order_det);
            $custhead = "CATRAMS- Order Canceled!!";
            $custcontent = "" . $order_det['order_number'] . "- Your Order Has Been Canceled!!.";
        }

        
      
            $notificationmasterController = new UsersNotificationController;
            $notif_data = [
                'sourceid' => $tokendata['uid'],
                'destid' => $order_det['order_created_by'],
                'nt_req_number' => $order_det['order_number'],
                'id' => $order_det['order_created_by'],


                'nt_sourcetype' => 1,
                'headers' => $custhead,
                'content' => $custcontent,
                'date' => $date,
                'nt_type'=>0,
                'nt_request_type'=>2,
                'nt_type_id'=>$order_det['order_id']
            ];
            $notificationmasterController->create_cust_notification($notif_data);
        


        if ($order_id) {
            $response = [
                'ret_data' => 'success'
            ];

            return $this->respond($response, 200);
        } else {
            $response = [
                'ret_data' => 'fail',
                'Message' => 'Error!!'
            ];

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

    public function get_order_list()
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
        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $statusMasterModel = new StatusMasterModel();

        $order_data = $orderMasterModel
            ->where('order_delete_flag', 0)
            ->where('order_created_by', $tokendata['uid'])
            ->where('order_status', 51)
            ->Orwhere('order_status', 52)
            ->where('order_delete_flag', 0)
            ->join('status_master', 'sm_id=order_status', 'left')
            ->findall();
        for ($i = 0; $i < sizeof($order_data); $i++) {
            $order_data[$i]['items'] = $orderitemsModel->where('oitem_order_id', $order_data[$i]['order_id'])
                ->where('oitem_delete_flag', 0)
                ->join('tool_details', 'tool_id=oitem_tool_id', 'left')->findAll();
        }

        if ($order_data) {
            $response['ret_data'] = "success";
            $response['order_data'] = $order_data;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No details for this order';
            return $this->respond($response, 200);
        }
    }

    public function order_payment()
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
            'order_id' => 'required',
            'order_pay' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $statusMasterModel = new StatusMasterModel();
        $tooltrackerModel = new ToolTrackerModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $date = date("Y-m-d H:i:s");
        $order_data = $orderMasterModel->where('order_id', $this->request->getVar('order_id'))->first();

        if ($order_data['order_delete_flag'] == 1) {
            $response = [
                'ret_data' => 'success',
                'deleted_item' => 1
            ];
            return $this->respond($response, 200);
        }

        if ($this->request->getVar('order_pay') == 0) {

            $master_Data = [
                'order_delete_flag' => 1,
                'order_status' => 54
            ];
            $this->order_history_update(54,$tokendata['uid'],$this->request->getVar('order_id'));

            $hist_data = [
                'ohist_order_id' => $this->request->getVar('order_id'),
                'ohist_order_status' => 54,
                'ohist_created_on' => $date
            ];
        } else {

            $paymentTrackerModel = new PaymentTrackermasterModel();

            $master_Data = [
                'order_delete_flag' => 1,
                'order_status' => 54
            ];

            $hist_data = [
                'ohist_order_id' => $this->request->getVar('order_id'),
                'ohist_order_status' => 54,
                'ohist_created_on' => $date
            ];

            $payMast = [
                'rpt_reqt_id' => $this->request->getVar('order_id'),
                'rpt_type' => 3,
                'rpt_amount' => $this->request->getVar('order_id'),
                'rpt_status' => 3
            ];
            $orderHistoryModel->update($this->request->getVar('rpt_id'), $hist_data);
        }


        $item_Data = $orderitemsModel->where('oitem_order_id', $this->request->getVar('order_id'))->findAll();
        for ($i = 0; $i < sizeof($item_Data); $i++) {
            $req_data = $ToolDetailsModel->where('tool_id', $item_Data[$i]['oitem_tool_id'])->first();
            $revised_master = [
                'tool_sale_quantity' => ($req_data['tool_sale_quantity']) + ($item_Data[$i]['oitem_quantity']),
                'tool_total_quantity' => ($req_data['tool_total_quantity']) + ($item_Data[$i]['oitem_quantity']),
            ];
            $revised_data = [
                'trk_tool_id' => $item_Data[$i]['oitem_tool_id'],
                'trk_type' => 2,
                'trk_status' => 54,
                'trk_rq_id' => $this->request->getVar('order_id'),
                'trk_created_by' => $tokendata['uid'],
                'trk_quant' => $item_Data[$i]['oitem_quantity']
            ];
            $ToolDetailsModel->update(($item_Data[$i]['oitem_tool_id']), $revised_master);
            $trk_id = $tooltrackerModel->insert($revised_data);
        }

        $orderMasterModel->update($this->request->getVar('order_id'), $master_Data);
        $orderHistoryModel->insert($hist_data);
        $response = [
            'ret_data' => 'success',
            'deleted_item' => 0
        ];

        return $this->respond($response, 200);
    }

    public function update_Date()
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
            'order_id' => 'required',
            'order_est_days' => 'required'

        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $orderMasterModel = new OrderMasterModel();
        $master_Data = [
            'order_est_days' => $this->request->getVar('order_est_days'),
        ];
        if ($this->request->getVar('order_act_days')) {
            $master_Data['order_act_days'] = $this->request->getVar('order_act_days');
        }
        $orderMasterModel->update($this->request->getVar('order_id'), $master_Data);

        $response = [
            'ret_data' => 'success'
        ];

        return $this->respond($response, 200);
    }

    public function completed_and_rejected_orders()
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

        $orderMasterModel = new OrderMasterModel();
        $orders_data = $orderMasterModel
            ->where('order_created_by', $this->request->getVar('order_created_by'))
            ->where('order_status', 53)
            ->orwhere('order_status', 54)
            ->orwhere('order_status', 55)
            ->join('customer_master', 'cstm_id=order_created_by')
            ->join('status_master', 'sm_id=order_status')
            ->orderBy('order_id', 'desc')
            ->findAll();
        if ($orders_data) {
            $response = [
                'ret_data' => 'success',
                'data' => $orders_data
            ];
            return $this->respond($response, 200);
        } else {
            return $this->fail("No Order Data", 400);
        }
    }

    public function order_r_img()
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
            'r_images' => 'required',
            'order_id ' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $ToolRequesttrackerModel = new ToolRequestTrackerModel();

        if (sizeof($this->request->getVar('r_images')) > 0) {
            $tarray = [];
            foreach ($this->request->getVar('r_images') as $eachurl) {
                $insert_track = [
                    'trt_rq_id' => $this->request->getVar('order_id'),
                    'trt_type' => 4,
                    'trt_url' => $eachurl,
                    'trt_created_by' => $tokendata['uid']
                ];
                array_push($tarray, $insert_track);
            }
            sizeof($tarray) > 0 ? $ToolRequesttrackerModel->insertBatch($tarray) : "";
        }
        $response = [
            'ret_data' => 'success'
        ];
        return $this->respond($response, 200);
    }

    public function order_recievedcust()

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


        $orderTrackerModel = new OrderrequesttrackerModel();
        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderhistoryModel = new OrderHistoryModel();
        $approvalmasterModel = new ApprovalmasterModel();
        $date = date("Y-m-d H:i:s");

        $rules = [
            'r_images' => 'required',
            'order_id' => 'required',
            'type' => 'required',
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $data = $this->request->getVar('r_images');
        if (sizeof($this->request->getVar('r_images')) > 0) {
            $tarray = [];

            foreach ($this->request->getVar('r_images') as $eachurl) {

                $insert_track = [
                    'ort_order_id' => $this->request->getVar('order_id'),
                    'ort_url' => $eachurl,
                    'ort_created_by' => $tokendata['uid'],
                    'ort_created_by' => $tokendata['uid'],
                    'ort_created_on' => $date,
                    'ort_updated_on' => $date,
                    'ort_updated_by' =>  $tokendata['uid'],
                ];

                array_push($tarray, $insert_track);
            }
            sizeof($tarray) > 0 ? $orderTrackerModel->insertBatch($tarray) : "";
        }

        if ($this->request->getVar('type') == 1) {

            $infdata = [
                'am_referenceid'   => $this->request->getVar('oitem_tool_id'),
                'am_reqid'   =>  $this->request->getVar('order_id'),
                'am_type' => 10,
                'am_reason' =>  $this->request->getVar('reason'),
                'am_requestedby' => $tokendata['uid'],
                'am_status' => 0,
                'am_updatedby' => $tokendata['uid'],
                'am_updatedon' => $date,
                'am_createdby' => $tokendata['uid'],
                'am_createdon' => $date,

            ];

            $approvalmasterModel->insert($infdata);

            
        $this->order_history_update(37,$tokendata['uid'],$this->request->getVar('order_id'));



            $master = [
                'order_status' => 52,
                'order_updated_on' => $date,
                'order_updated_by' => $tokendata['uid'],
            ];
            $orderMasterModel->update($this->request->getVar('order_id'), $master);
        }

        $response['ret_data'] = 'success';

        return $this->respond($response, 200);
    }

    public function order_shipped($token_id, $order_det, $tool_images, $track_data)
    {

        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $requestMediaModel = new RequestMediaModel();
        $shipmentMasterController = new ShipmentMasterController;
        $date = date("Y-m-d H:i:s");
        $master_Data = [
            'order_status' => 52,
            'order_updated_on' => $date,
            'order_updated_by' => $token_id,

        ];
        
        $order_id=$this->order_history_update(52,$token_id,$order_det['order_id']);

        $infdata = [];
        foreach ($tool_images as $eachurl) {

            $indata = [
                'rmedia_type' => 0,
                'rmedia_request_id' => $order_det['order_id'],
                'rmedia_url_type' => 2,
                'rmedia_url' => $eachurl,
                'rmedia_by_type' => 0,
                'rmedia_created_on' => $date,
                'rmedia_created_by' => $token_id
            ];
            array_push($infdata, $indata);
        }
        if (sizeof($infdata) > 0) {
            $requestMediaModel->insertBatch($infdata);
        }
        $orderMasterModel->update($order_det['order_id'], $master_Data);
       
        $shipmast = [

            'shm_type' => 1,
            'shm_by_type' => 0,
            'shm_request_id' => $order_det['order_id'],
            'shm_status' => 0,
            'shm_track_id' => $track_data['track_id'],
            'shm_track_url' => $track_data['track_url'],
            'shm_reference' => $track_data['track_reference'],
            'shm_created_by' => $token_id,
            'shm_created_on' => $date,
            'shm_updated_by' => $token_id,
            'shm_updated_on' => $date,

        ];

        $shiptrack = [

            'shtrack_status' => 0,
            'shtrack_created_by' => $token_id,
            'shtrack_created_on' => $date,
        ];

        $shtrack_id = $shipmentMasterController->create_shipment($shipmast, $shiptrack);
        return $order_id;
    }

    public function order_history()
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

        $orderMasterModel = new OrderMasterModel();
        $orders_data = $orderMasterModel
            ->join('customer_master', 'cstm_id=order_created_by')
            ->join('status_master', 'sm_id=order_status')
            ->orderBy('order_id', 'desc')
            ->findAll();
        if ($orders_data) {
            $response = [
                'ret_data' => 'success',
                'data' => $orders_data
            ];
            return $this->respond($response, 200);
        } else {
            $response = [
                'ret_data' => 'error'
            ];
            return $this->respond($response, 200);
        }
    }

    public function order_Delivered($token_id, $order_det)
    {

        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $requestMediaModel = new RequestMediaModel();
        $shipmentMasterController = new ShipmentMasterController;
        $date = date("Y-m-d H:i:s");
        $master_Data = [
            'order_status' => 53,
            'order_updated_on' => $date,
            'order_updated_by' => $token_id,

        ];
        $order_id=$this->order_history_update(53,$token_id,$order_det['order_id']);
        $orderMasterModel->update($order_det['order_id'], $master_Data);
        return $order_id;
    }

    public function order_canceled($token_id, $order_det)
    {

        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $requestMediaModel = new RequestMediaModel();
        $shipmentMasterController = new ShipmentMasterController;
        $date = date("Y-m-d H:i:s");
        $master_Data = [
            'order_status' => 53,
            'order_updated_on' => $date,
            'order_updated_by' => $token_id,

        ];
        $this->order_history_update(53,$token_id,$order_det['order_id']);
       
        if ($order_det['order_pay'] == 1) {
            $master_Data = [
                'order_status' => 55,
                'order_delete_flag' => 0,
                'order_updated_on' => $date,
                'order_updated_by' => $token_id,
            ];
        $order_id=$this->order_history_update(55,$token_id,$order_det['order_id']);

           
        } else {
            $master_Data = [
                'order_status' => 54,
                'order_delete_flag' => 0,
                'order_updated_on' => $date,
                'order_updated_by' => $token_id,
            ];
        $order_id=$this->order_history_update(54,$token_id,$order_det['order_id']);

        }

        $orderMasterModel->update($order_det['order_id'], $master_Data);
       
        return $order_id;
    }

    public function recieve_payment_order()

    {
        $custModel = new CustomerMasterModel();
        $userModel = new UsersModel();
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

        $date = date("Y-m-d H:i:s");

        $seqModel = new SequenceGeneratorModel();
        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $tooltrackerModel = new ToolTrackerModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $paymenthistory = new PaymentHistoryModel();
        $expensetrackerModel = new ExpenseTrackerModel();
        $paymentmasterController = new PaymentMasterController;
        $paymentmasterModel = new PaymentTrackermasterModel();
        $notificationmasterController= new UsersNotificationController;


        $t_details = $this->request->getVar('transaction_details');
        $pay_details=$t_details->payment_response;
        $rpt_data = $paymentmasterModel
        ->where('rpt_type',3)
        ->where('rpt_reqt_id', $pay_details->udf2)->first();

        $hist1 = [
            'rph_type' => 2,
            'rph_rq_id' =>  $pay_details->udf2,
            'rph_status' => 1,
            'rph_amount' => $pay_details->amount,
            'rph_created_on' => $date,
            'rph_created_by' => $pay_details->udf4,
            'rph_transaction_id' => $pay_details->txnid
        ];
        $paymenthistory->insert($hist1);

        if ($t_details->result == 'payment_successfull') {

            $hist2 = [
                'rph_type' => 2,
                'rph_rq_id' => $pay_details->udf2,
                'rph_status' => 2,
                'rph_amount' => $pay_details->amount,
                'rph_created_on' => $date,
                'rph_created_by' => $pay_details->udf4,
                'rph_transaction_id' => $pay_details->txnid
            ];
            $paymenthistory->insert($hist2);

            $track_data = [
                'rpt_status' => 1,
                'rpt_transaction_id' => $pay_details->txnid
            ];
            $paymentmasterModel->update($rpt_data['rpt_id'], $track_data);
            $inData = [
                'order_status' => 51,
                'order_pay' => 1,
                'order_updated_on' => $date,
                'order_updated_by' => $pay_details->udf4,

            ];
            $result = $orderMasterModel->update($pay_details->udf2, $inData);

            $ifData = [
                'ohist_order_id' => $pay_details->udf2,
                'ohist_order_status' => 51,
                'ohist_created_by' => $pay_details->udf4,
                'ohist_created_on' => $date,
                'ohist_updated_on' => $date,
                'ohist_updated_by' =>  $pay_details->udf4,
            ];
            $resulthist = $orderHistoryModel->insert($ifData);

        } else {

            $hist2 = [
                'rph_type' => 2,
                'rph_rq_id' => $pay_details->udf2,
                'rph_status' => 3,
                'rph_amount' => $pay_details->amount,
                'rph_created_on' => $date,
                'rph_created_by' => $pay_details->udf4,
                'rph_transaction_id' => $pay_details->txnid
            ];
            $paymenthistory->insert($hist2);
            $hist3 = [
                'rph_type' => 2,
                'rph_rq_id' => $pay_details->udf2,
                'rph_status' => 0,
                'rph_amount' => $pay_details->amount,
                'rph_created_on' => $date,
                'rph_created_by' => $pay_details->udf4,
                'rph_transaction_id' => $pay_details->txnid
            ];
            $paymenthistory->insert($hist3);

            $track_data = [
                'rpt_status' => 0,
                'rpt_transaction_id' => $pay_details->txnid
            ];
            $paymentmasterModel->update($rpt_data['rpt_id'], $track_data);
            $inData = [
                'order_status' => 51,
                'order_pay' => 2,
                'order_updated_on' => $date,
                'order_updated_by' => $pay_details->udf4,

            ];
            $result = $orderMasterModel->update($pay_details->udf2, $inData);

            $ifData = [
                'ohist_order_id' => $pay_details->udf2,
                'ohist_order_status' => 51,
                'ohist_created_by' => $pay_details->udf4,
                'ohist_created_on' => $date,
                'ohist_updated_on' => $date,
                'ohist_updated_by' =>  $pay_details->udf4,
            ];
            $resulthist = $orderHistoryModel->insert($ifData);
        }
        $us_id = $userModel->where('us_delete_flag', 0)->findAll();
        $ntf_data = [];
               
        foreach ($us_id as $eachurl) {
         
            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "New Order Request",
                'content' => "New Order Request Created ",
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date
            ];
            array_push($ntf_data, $indata);
        }
        $nt_id = $notificationmasterController->create_us_notification($ntf_data);
        $response['ret_data']='success';
        return $this->respond($response,200);
    }

    public function cash_on_delivery()

    {
        $userModel = new UsersModel();
        $validModel = new Validation();
        $commonutils = new Commonutils();
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

        $date = date("Y-m-d H:i:s");

        $seqModel = new SequenceGeneratorModel();
        $orderMasterModel = new OrderMasterModel();
        $orderitemsModel = new OrderItemsModel();
        $orderHistoryModel = new OrderHistoryModel();
        $tooltrackerModel = new ToolTrackerModel();
        $ToolDetailsModel = new ToolDetailsModel();
        $paymenthistory = new PaymentHistoryModel();
        $expensetrackerModel = new ExpenseTrackerModel();
        $paymentmasterController = new PaymentMasterController;
        $paymentmasterModel = new PaymentTrackermasterModel();

            $payhist = [
                'rph_type' => 2,
                'rph_rq_id' => $this->request->getVar('order_id'),
                'rph_amount' => $this->request->getVar('order_total_cost'),
                'rpt_status' => 0,
                'rph_created_by' => $tokendata['uid'],
            ];
            $paymenthistory->insert($payhist);
            $inData = [
                'order_status' => 51,
                'order_pay' => 2,
                'order_updated_on' => $date,
                'order_updated_by' => $tokendata['uid'],

            ];
            $result = $orderMasterModel->update( $this->request->getVar('order_id'), $inData);

            $ifData = [
                'ohist_order_id' =>  $this->request->getVar('order_id'),
                'ohist_order_status' => 51,
                'ohist_created_by' => $tokendata['uid'],
                'ohist_created_on' => $date,
                'ohist_updated_on' => $date,
                'ohist_updated_by' =>  $tokendata['uid'],
            ];
            $resulthist = $orderHistoryModel->insert($ifData);
            $us_id = $userModel->where('us_delete_flag', 0)->findAll();
            $ntf_data = [];
               
        foreach ($us_id as $eachurl) {
         
            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "New Order Request",
                'content' => "New Order Request Created ",
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date
            ];
            array_push($ntf_data, $indata);
        }
        

            if ($resulthist) {
                
                $response = [
                    'ret_data' => 'success',
                    'Order_id' =>  $this->request->getVar('order_id')
                ];
                return $this->respond($response, 200);
            } else {

                return $this->fail("invalid user", 400);
            }
            
    }

    public function order_history_update($status,$token_id,$order_id)
    {
        $orderHistoryModel = new OrderHistoryModel();
        $date = date("Y-m-d H:i:s");

        $hist_data = [
            'ohist_order_id' => $order_id,
            'ohist_order_status' => $status,
            'ohist_created_by' => $token_id,
            'ohist_created_on' => $date,
            'ohist_updated_on' => $date,
            'ohist_updated_by' =>  $token_id,
        ];

        $hist_id =$orderHistoryModel->insert($hist_data);

        return $hist_id;

    }
}
