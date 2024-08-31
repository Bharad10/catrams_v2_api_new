<?php

namespace App\Controllers\DashBoard;

use App\Controllers\ServiceRequest\ServiceRequestMasterController;
use App\Controllers\ToolRequest\RentDelayCalcController;
use App\Controllers\ToolRequest\ToolRequestMasterController;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\ServiceRequest\ServiceRequestHistoryModel;
use App\Models\ServiceRequest\ServiceRequestMasterModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use App\Models\User\UsersModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\RESTful\ResourceController;
use Config\Commonutils;
use Config\Validation;
use DateTime;

class DashBoardController extends ResourceController
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
        // $response['Message']='No data Found';

        // return $this->respond($response, 200);
        $toolRequestMasterController = new ToolRequestMasterController;
        $servicerequestMasterController = new ServiceRequestMasterController;
        $RentDelayCalController= new RentDelayCalcController;
        $userModel = new UsersModel();

        $rent_Details=$toolRequestMasterController->fetch_dashb_details();
        $tool_data=array();
        $tool_data= $rent_Details['tool_data'];
        $t_rent= is_array($tool_data)?sizeof($tool_data):0;
        $service_details = $servicerequestMasterController->fetch_dashb_details();
        $serm_data=array();
        $serm_data= $service_details['serm_data'];
        $t_service= is_array($serm_data)?sizeof($serm_data):0;

       
        $holded_tickets = $service_details['total_serm_hold'] + $rent_Details['total_hold_tool'];

        
        $open_tickets = array_merge($rent_Details['open_ticket_tool'], $service_details['open_ticket_service']);
        $total_tickets = $t_rent + $t_service;
        $total_open = $rent_Details['tool_open'] + $service_details['service_open'];
        $total_closed = $rent_Details['tool_closed'] + $service_details['service_closed'];
        $total_pending = $rent_Details['inprogress_tool'] + $service_details['inprogress_service'];


        
       $trequest=$RentDelayCalController->index();


   


        if ($serm_data || $tool_data) {
            $result = [

                'total_tickets' => $total_tickets,
                'total_closed' => $total_closed,
                'total_open' => $total_open,
                'tool_total' => $t_rent,
                'service_total' => $t_service,
                'tool_open' => $rent_Details['tool_open'],
                'service_open' => $service_details['service_open'],
                'tool_closed' => $rent_Details['tool_closed'],
                'service_closed' => $service_details['service_closed'],
                'tool_pay_pend' => $rent_Details['tool_pay_pend'],
                'serv_pay_pend' => $service_details['serv_pay_pend'],
                'inprogress_tool' => $rent_Details['inprogress_tool'],
                'inprogress_service' => $service_details['inprogress_service'],
                'inprogress_total' => $total_pending,
                'open_tickets' => $open_tickets,
                'serm_pend_usr' => $service_details['pending_by_user'],
                'holded_tickets' => $holded_tickets,
                'serm_hold' => $service_details['total_serm_hold'],
                'tool_hold' => $rent_Details['total_hold_tool'],
                'due_array' => $trequest,
                'monthly_data'=>$service_details['monthly_data'],
                'monthly_cmpl_data'=>$service_details['monthly_cmpl_data']

            ];
            $response = [


                'ret_data' => 'success',
                'user_list' => $result
            ];
        } else {

            $response['Message'] = 'No data Found';
            $response['code'] = 6;
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


}
