<?php

namespace App\Controllers\Quote;

use App\Controllers\System\UsersNotificationController;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerVehicleModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use app\Models\ServiceRequest\ServiceRequestMedia;
use app\Models\ServiceRequest\ServiceRequestModel;
use app\models\ServiceRequest\ServiceRequestDetailsModel;
use app\models\Request\RequesMasterModel;
use app\Models\Request\RequestStatusMasterModel;
use app\Models\Invoice\InvoiceDetailModel;
use app\Models\Invoice\InvoiceMasterModel;
use App\Models\Packages\ServiceRequestPackageModel;
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
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use App\Models\VehicleMaster\CatVehicleDataModel;
use Config\Commonutils;
use Config\Validation;
use CodeIgniter\HTTP\Request;

class QuoteMasterController extends ResourceController
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
        $CustomerMasterModel = new CustomerMasterModel();
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $UsersModel = new UsersModel();

        $result = $quotemodel->where('qtm_delete_flag', 0)
            ->join('customer_master', 'cstm_id=qtm_custid')
            ->join('servicerequest_master', 'serm_id=qtm_serm_id')
            ->join('status_master', 'sm_id=qtm_status_id')
            ->join('users', 'us_id=qtm_created_by')
            ->orderBy('qtm_id', 'desc')
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

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        // return $this->respond('ddddddddddddddddddddddd', 200);

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
        // $response['ret_data'] = 'error';
        $CustomerMasterModel = new CustomerMasterModel();
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $UsersModel = new UsersModel();
        $servicepackageModel = new ServiceRequestPackageModel();
        $vehmasterModel = new CatVehicleDataModel();
        $servicesmappingModel = new ServicesMappingModel();



        $result = $quotemodel->where('qtm_delete_flag', 0)
            ->where('qtm_id', base64_decode($id))
            ->where('qtm_type', 0)
            ->join('servicerequest_master', 'serm_id=qtm_serm_id')
            ->join('customer_master', 'cstm_id=qtm_custid')
            ->join('customer_vehicle', 'custveh_id =serm_vehid')
            ->join('cat_vehicle_data', 'id=customer_vehicle.custveh_veh_id')
            ->first();
        $services = $quoteitemsmodel->where('qti_deleted_flag', 0)
            ->where('qti_qm_id', base64_decode($id))
            ->join('service_request_package', 'servpack_id=qti_item_id')
            ->findAll();
        $packages = $servicepackageModel->where('servpack_delete_flag', 0)->findAll();
        for ($i = 0; $i < sizeof($packages); $i++) {
            $map_d = [];
            $map_d = $servicesmappingModel->where('srm_delete_flag', 0)
                ->where('srm_servpack_id', $packages[$i]['servpack_id'])
                ->join('tool_details', 'tool_id=srm_tool_id')
                ->findAll();
            $packages[$i]['tools'] = $map_d ? $map_d : 0;
        }

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

        // return $this->respond('ddddddddddddddddddddddd', 200);

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
        $notificationmasterController = new UsersNotificationController;
        //$result = $quotemodel->where('qtm_delete_flag', 0)->where('qtm_id', $id)->join('quote_details', 'qtdet_servrq_id=qtm_id')->findAll();
        //$result = $serequestModel->where('serm_deleteflag', 0)->where('serm_id',$id)->first();
        $rules = [
            'serm_custid' => 'required',
            'serm_vehid' => 'required',
            'serm_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $seq = $seqModel->where('seq_id', 1)->findAll();
        $nextval = ("RAMS_QT" . $seq[0]['quote_sequence']);
        $date = date("Y-m-d H:i:s");
        $serm_data=$serequestModel->where('serm_id',$this->request->getVar("serm_id"))->first();
        $inData = [
            'qtm_custid' => $this->request->getVar("serm_custid"),
            'qtm_vehid' => $this->request->getVar("serm_vehid"),
            'qtm_number' => $nextval,
            'qtm_serm_id' => $this->request->getVar("serm_id"),
            'qtm_cost' => $this->request->getVar("totalcost"),
            'qtm_ad_type' => $this->request->getVar("qtm_ad_type"),
            'qtm_ad_charge' => $this->request->getVar("qtm_ad_charge"),
            'qtm_ad_charge_cost' => $this->request->getVar("qtm_ad_charge_cost"),
            'qtm_status_id' => 21,
            'qtm_created_by' => $tokendata['uid'],
            'qtm_created_on' => $date,
            'qtm_updated_by' => $tokendata['uid'],
            'qtm_updated_on' => $date,


        ];

        $result = $quotemodel->insert($inData);

        $upd_data2 = [
            'serm_status' => 21,
            'serm_cost' => $this->request->getVar("serm_pay_amount"),
            'serm_updatedon' => $date,
            'serm_updatedby' => $tokendata['uid'],
            'serm_pay_amount'=>$this->request->getVar("serm_pay_amount"),
            'serm_pay_alert'=>$this->request->getVar("serm_pay_alert"),
        ];
        $inst_data = [
            'srh_serm_id' => $this->request->getVar("serm_id"),
            'srh_status_id' => 21,
            'srh_created_on' => $date,
            'srh_created_by' => $tokendata['uid']
        ];
        $result_insert = $servicehistoryModel->insert($inst_data);

        $result_ipdt2 = $servicequestModel->update($this->request->getVar("serm_id"), $upd_data2);
        if ($result) {
            if (sizeof($this->request->getVar("quote_items")) > 0) {
                $serviceData = [];
                foreach ($this->request->getVar("quote_items") as $eachservice) {
                    $each_data = [
                        'qti_item_id'   => $eachservice->servpack_id,
                        'qti_qm_id'   => $result,
                        'qti_type' => 0,
                        'qti_cost' => $eachservice->servpack_cost,
                        'qti_created_by' => $tokendata['uid'],
                        'qti_created_on' => $date,
                        'qti_updated_by' => $tokendata['uid'],
                        'qti_updated_on' => $date,
                    ];
                    array_push($serviceData, $each_data);
                }
                $resultitem = $quoteitemsmodel->insertBatch($serviceData);
            }
            //  $notif_data=[
            //     'cstm_id'=>$this->request->getVar("serm_custid"),
            //     'head_message'=>'New Quotation created',
            //     'content_message'=>'New Quote created tap to view'
            //  ];

            //  $notification_push=$notificationController->push_notification($notif_data);
            //   return $this->respond($notif_data, 200);

           
           
            $ntf_data=[
                'id'=>$this->request->getVar("serm_custid"),
                'headers'=>"New Quotation created" ,
                'content'=>"New Quote created against " . $serm_data['serm_number'] . ". Tap to see",
                'sourceid'=>$tokendata['uid'],
                'destid'=>$this->request->getVar("serm_custid"),
                'date'=>$date,
                'nt_type'=>0,
                'nt_request_type'=>0,
                'nt_type_id'=> $serm_data['serm_id'],
                'nt_req_number'=>$serm_data['serm_number']
            ];
            
            $nt_id=$notificationmasterController->create_cust_notification($ntf_data);
            
            if ($result && $resultitem) {
                $seq = (intval($seq[0]['quote_sequence']) + 1);
                $seq_data = ['quote_sequence' => $seq];
                $seqModel->update(1, $seq_data);
                $response['ret_data'] = "success";
                $response['result'] = $result;
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
        $CustomerMasterModel = new CustomerMasterModel();
        $servicequestModel = new ServiceRequestMasterModel();
        $servicerequestitemsModels = new ServiceRequestItemsModel();
        $serequestModel = new ServiceRequestMasterModel();
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $seqModel = new SequenceGeneratorModel();
        //$result = $quotemodel->where('qtm_delete_flag', 0)->where('qtm_id', $id)->join('quote_details', 'qtdet_servrq_id=qtm_id')->findAll();
        //$result = $serequestModel->where('serm_deleteflag', 0)->where('serm_id',$id)->first();
        $rules = [
            'serm_custid' => 'required',
            'serm_vehid' => 'required',
            'serm_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
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

        $result = $quotemodel->update($this->request->getVar("qtm_id"), $inData);
        if ($result) {
            if (sizeof($this->request->getVar("quote_items")) > 0) {
                $serviceData = [];
                foreach ($this->request->getVar("quote_items") as $eachservice) {
                    $each_data = [
                        'qti_id'   => $eachservice->qti_id,
                        'qti_item_id'   => $eachservice->servpack_id,
                        'qti_qm_id'   => $result,
                        'qti_type' => 0,
                        'qti_cost' => $eachservice->servpack_cost,
                        'qti_created_by' => $tokendata['uid'],
                        'qti_created_on' => $date,
                        'qti_updated_by' => $tokendata['uid'],
                        'qti_updated_on' => $date,
                    ];
                    array_push($serviceData, $each_data);
                }
                $resultitem = $quoteitemsmodel->updateBatch($serviceData, 'qti_id');
            }
            if ($result && $resultitem) {
                $response['ret_data'] = "success";
                $response['result'] = $result;
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
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
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
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $qt_id = $this->request->getVar("qt_id");
        $items_id = $this->request->getVar("qti_id");

        $inData = [
            'qtm_delete_flag' => 1,
            'qtm_updated_by' => $tokendata['uid'],
            'qtm_updated_on' => $date,
        ];
        $inData = [
            'qti_deleted_flag' => 1,
            'qti_updated_by' => $tokendata['uid'],
            'qti_updated_on' => $date,
        ];

        $result = $quotemodel->update(($qt_id), $inData);
        $resultitem = $quotemodel->update(($items_id), $inData);
        if ($result && $resultitem) {

            $response['ret_data'] = "success";
            return $this->respond($response, 200);
        }
    }
    public function quotedetailsby_requestid()
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

        // $response['ret_data'] = 'error';
        $servicequestModel = new ServiceRequestMasterModel();
        $CustomerMasterModel = new CustomerMasterModel();
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $UsersModel = new UsersModel();
        $customerdiscountModel= new CustomerDiscountModel();

        $result = $quotemodel->where('qtm_delete_flag', 0)->where('qtm_serm_id',  $this->request->getVar("request_id"))
            ->where('qtm_type', 0)
            ->join('customer_master', 'cstm_id=qtm_custid')
            ->join('servicerequest_master', 'serm_id=qtm_serm_id')
            ->orderBy('qtm_created_on', 'desc')
            ->first();

         $result['customer_dicounts']=$result['cstm_type']==1?$customerdiscountModel->where('cd_active_flag',0)->first():[];
        $services = $quoteitemsmodel
            ->where('qti_deleted_flag', 0)
            ->where('qti_qm_id', $result['qtm_id'])
            ->join('service_request_package', 'servpack_id=qti_item_id')
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

    public function reject_quote()
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
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $toolrequesthistoryModel = new ToolRequestHistoryModel();
        $notificationmastercontroller= new UsersNotificationController;

        $rules = [
            'reject_reason' => 'required',
            'qtm_id' => 'required',
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");

        if ($this->request->getVar("type") == 2) {
            $inData = [
                'qtm_rejected_reason' => $this->request->getVar("reject_reason"),
                'qtm_status_id' => 22,
                'qtm_updated_by' => $tokendata['uid'],
                'qtm_updated_on' => $date,
            ];

            $result = $quotemodel->update($this->request->getVar("qtm_id"), $inData);
            $upd_data2 = [
                'serm_status' => 22,
                'serm_updatedon' => $date,
                'serm_updatedby' => $tokendata['uid']
            ];
            $inst_data = [
                'srh_serm_id' => $this->request->getVar("serm_id"),
                'srh_status_id' => 22,
                'srh_created_on' => $date,
                'srh_created_by' => $tokendata['uid']
            ];

            $result_ipdt2 = $servicequestModel->update($this->request->getVar("serm_id"), $upd_data2);
            $servicehistoryModel->insert($inst_data);
            
        $serm_data = $serequestModel->where('serm_id', $this->request->getVar('serm_id'))->first();
        $us_id = $userModel
        ->where('us_delete_flag', 0)
        ->findAll();
        $ntf_data = [];
       
        foreach ($us_id as $eachurl) {
         
            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "Quotation Rejected",
                'content' => "Quote Rejected for  " . $serm_data['serm_number'] ,
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_request_type'=>0,
                'nt_type_id'=>$serm_data['serm_id'],
                'nt_type'=>1
            ];

            array_push($ntf_data, $indata);

        }
        } 
        else {
            $inData = [
                'qtm_rejected_reason' => $this->request->getVar("reject_reason"),
                'qtm_status_id' => 47,
                'qtm_created_by' => 1,
                'qtm_updated_by' => $tokendata['uid'],
                'qtm_updated_on' => $date,
            ];
            $result = $quotemodel->update($this->request->getVar("qtm_id"), $inData);
            $upd_data2 = [
                'tldt_status' => 47,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $result_ipdt2 = $toolrequestmasterModel->update($this->request->getVar("tr_id"), $upd_data2);
            $inst_data = [
                'trqh_tr_id' => $this->request->getVar("tr_id"),
                'trqh_status_id' => 47,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $toolrequesthistoryModel->insert($inst_data);

        $serm_data = $toolrequestmasterModel->where('tldet_id', $this->request->getVar('tr_id'))->first();
        $us_id = $userModel
        ->where('us_delete_flag', 0)
        ->findAll();
        $ntf_data = [];
       
        foreach ($us_id as $eachurl) {
         
            $indata = [
                'id' => $eachurl['us_id'],
                'headers' => "Quotation Rejected",
                'content' => "Quote Rejected for  " . $serm_data['tldt_number'] ,
                'sourceid' => $tokendata['uid'],
                'destid' => $eachurl['us_id'],
                'date' => $date,
                'nt_request_type'=>1,
                'nt_type_id'=>$serm_data['tldet_id'],
                'nt_type'=>1
            ];

            array_push($ntf_data, $indata);

        }
        }

        $notificationmastercontroller->create_us_notification($ntf_data);

        if ($result) {
            if ($result) {
                $response['ret_data'] = "success";
                return $this->respond($response, 200);
            } else {
                $response['ret_data'] = "fail";
                $response['Message'] = 'Cannot Reject';
                return $this->respond($response, 200);
            }
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'Cannot Reject';
            return $this->respond($response, 200);
        }
    }
    
    public function purchasequote_accept()
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
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        $toolrequesthistoryModel = new ToolRequestHistoryModel();

        $rules = [
            'qtm_id' => 'required',
            'tr_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $date = date("Y-m-d H:i:s");
        $inData = [
            'qtm_type' => 1,
            'qtm_status_id' => 48,
            'qtm_updated_by' => $tokendata['uid'],
            'qtm_updated_on' => $date,
        ];
        $result = $quotemodel->update($this->request->getVar("qtm_id"), $inData);
        if ($result) {

            $upd_data2 = [
                'tldt_status' => 48,
                'tldt_updated_on' => $date,
                'tldt_updated_by' => $tokendata['uid'],
            ];
            $result_ipdt2 = $toolrequestmasterModel->update($this->request->getVar("tr_id"), $upd_data2);
            $inst_data = [
                'trqh_tr_id' => $this->request->getVar("tr_id"),
                'trqh_status_id' => 46,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $toolrequesthistoryModel->insert($inst_data);
            $insts_data = [
                'trqh_tr_id' => $this->request->getVar("tr_id"),
                'trqh_status_id' => 48,
                'trqh_created_on' => $date,
                'trqh_created_by' => $tokendata['uid'],
            ];
            $toolrequesthistoryModel->insert($insts_data);
            $response['ret_data'] = "success";
            $response['result'] = $result;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'Cannot Update';
            return $this->respond($response, 200);
        }
    }
    public function getquotebytreqid()
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
        // $response['ret_data'] = 'error';
        $CustomerMasterModel = new CustomerMasterModel();
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $UsersModel = new UsersModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        // $id=base64_decode($id);
        // return $this->respond($id, 200);

        $result = $quotemodel->where('qtm_delete_flag', 0)
            ->where('qtm_serm_id', $this->request->getVar("serm_id"))
            ->where('qtm_type', 1)
            ->join('tool_request_details', 'tldet_id=qtm_serm_id')
            ->findAll();
        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No details for this service request';
            return $this->respond($response, 200);
        }
    }
    public function getquotebytreqid_formobile()
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
        // $response['ret_data'] = 'error';
        $CustomerMasterModel = new CustomerMasterModel();
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $UsersModel = new UsersModel();
        $toolrequestmasterModel = new ToolRequestDetailsModel();
        // $id=base64_decode($id);
        // return $this->respond($id, 200);

        $result = $quotemodel->where('qtm_delete_flag', 0)
            ->where('qtm_serm_id', $this->request->getVar("trid"))
            ->where('qtm_type', 1)
            ->join('tool_request_details', 'tldet_id=qtm_serm_id')
            ->findAll();
        if ($result) {
            $response['ret_data'] = "success";
            $response['result'] = $result;
            return $this->respond($response, 200);
        } else {
            $response['ret_data'] = "fail";
            $response['Message'] = 'No details for this service request';
            return $this->respond($response, 200);
        }
    }
    public function recent_quotes()
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
            'serm_id' => 'required'
        ];

        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $servicepackageModel = new ServiceRequestPackageModel();

        $quote_data = $quotemodel->where('qtm_delete_flag', 0)
            ->where('qtm_serm_id', $this->request->getVar("serm_id"))
            ->orderBy('qtm_id', 'desc')
            ->findAll();

        if ($quote_data) {
            for ($i = 0; $i < sizeof($quote_data); $i++) {

                $items_Data[$i] = $quoteitemsmodel
                    ->where('qti_qm_id', $quote_data[$i]['qtm_id'])
                    ->join('service_request_package', 'servpack_id=qti_item_id')
                    ->findAll();

                if ($items_Data[$i]) {
                    $quote_data[$i]['items_data'] = $items_Data[$i];
                }
            }
        }
        if ($quote_data) {
            $response = [
                'ret_data' => 'success',
                'Quote_Data' => $quote_data
            ];
        } else {
            $response['Message'] = 'no quotes';
        }

        return $this->respond($response, 200);
    }

    public function getquote_byroleid()
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
        $quotemodel = new QuoteMasterModel();
        $quoteitemsmodel = new QuoteItemsModel();
        $UsersModel = new UsersModel();

        $result = $quotemodel->where('qtm_delete_flag', 0)
            ->join('customer_master', 'cstm_id=qtm_custid')
            ->join('servicerequest_master', 'serm_id=qtm_serm_id', 'left')
            ->join('status_master', 'sm_id=qtm_status_id')
            ->join('users', 'us_id=qtm_created_by')
            ->where('serm_vendor_flag', 0)
            ->where('serm_assigne', $tokendata['uid'])
            ->Orwhere('serm_assigne', 0)
            ->orderBy('qtm_id', 'desc')
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
}
