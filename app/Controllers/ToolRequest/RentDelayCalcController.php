<?php

namespace App\Controllers\ToolRequest;

use App\Controllers\BaseController;
use App\Models\Payment\PaymentTrackermasterModel;
use App\Models\ToolRequest\ToolRequestDetailsModel;
use App\Models\ToolRequest\ToolRequestHistoryModel;
use CodeIgniter\I18n\Time;

class RentDelayCalcController extends BaseController
{
    public function index()

    {
        //Function For fetching rent delay(if any) for tool rent request.
        $ToolRequestMasterModel = new ToolRequestDetailsModel();
    
        // Fetch tool request data with specific status and join related tables
        $trequest = $ToolRequestMasterModel

            ->distinct()
            ->select('tldet_id,tldt_due_date,tool_rent_cost,tldt_tool_quant,tool_delay_percentage,tool_cost,tldt_number,cstm_name')
            ->whereIn('tldt_status', [15, 11])
            ->join('request_payment_tracker', 'rpt_reqt_id=tldet_id', 'left')
            ->join('tool_details', 'tool_id=tldt_tool_id')
            ->join('customer_master', 'cstm_id=tldt_cstm_id')
            ->orderBy('tldet_id','ASC')
            ->findAll();
    
        // Ensure there are no duplicates initially
        $trequest = array_unique($trequest, SORT_REGULAR);
        $current_date = Time::now();
       
        // foreach ($trequest as &$request) {
        //     $dateString = trim($request['tldt_due_date']);
        //     if (!empty($dateString)) {
        //         $tldt_due_date = Time::createFromFormat('Y-m-d H:i:s', $dateString);
        //         $time_difference_seconds = $current_date->getTimestamp() - $tldt_due_date->getTimestamp();
        //         $time_difference_days = floor(abs($time_difference_seconds) / 86400); // 86400 seconds in a day
        //         if ($current_date->isAfter($tldt_due_date)) {
        //             $request['due_days'] = (string)$time_difference_days;
        //             $d_price = ($time_difference_days * $request['tool_rent_cost'] * $request['tldt_tool_quant']);
        //             $request['due_rent_price'] = (string)($d_price + ($d_price * $request['tool_delay_percentage']) / 100);
        //             $request['tr_updated_cost'] = $request['due_rent_price'] + $request['tool_cost'];
        //         } else {
        //             $request['expected_Days'] = (string)$time_difference_days;
        //         }
        //     }
        // }
        $ret_array=[];
        $o_id=0;
        $n_id=0;
        $j=0;
        for ($i = 0; $i < count($trequest); $i++) {
            $dateString = trim($trequest[$i]['tldt_due_date']);
            if (!empty($dateString)) {
                $n_id=$trequest[$i]['tldet_id'];
                if($o_id!=$n_id){
                    $ret_array[$j]=$trequest[$i];
                    $tldt_due_date = Time::createFromFormat('Y-m-d H:i:s', $dateString);
                    $time_difference_seconds = $current_date->getTimestamp() - $tldt_due_date->getTimestamp();
                    $time_difference_days = floor(abs($time_difference_seconds) / 86400); // 86400 seconds in a day
                    if ($current_date->isAfter($tldt_due_date)) {
                        $ret_array[$j]['due_days'] = (string)$time_difference_days;
                        $d_price = ($time_difference_days * $trequest[$i]['tool_rent_cost'] * $trequest[$i]['tldt_tool_quant']);
                        $ret_array[$j]['due_rent_price'] = (string)($d_price + ($d_price * $trequest[$i]['tool_delay_percentage']) / 100);
                        $ret_array[$j]['tr_updated_cost'] = $ret_array[$j]['due_rent_price'] + $trequest[$i]['tool_cost'];
                    } else {
                        // $ret_array[$j]['expected_Days'] = (string)$time_difference_days;
                    }
                    $o_id=$n_id;
                    $j++;
                }
                
            }
        }
        
        $ret_array=sizeof($ret_array)?$ret_array:[];
        return $ret_array;
    }
    

    public function calulate_rent()
    {
        // Initialize models
        $toolRequestModel = new ToolRequestDetailsModel();
        $toolrequesthistoryModel = new ToolRequestHistoryModel();
        $paymentTrackerModel = new PaymentTrackermasterModel();

        // Fetch all necessary data with joins
        $tldet_data = $toolRequestModel->join('status_master', 'sm_id=tldt_status')
            ->join('request_payment_tracker', 'rpt_reqt_id=tldet_id',)
            ->join('tool_details', 'tool_id=tldt_tool_id')
            ->join('customer_master', 'cstm_id=tldt_cstm_id')
            ->findAll();

        // Get current date once to use in the loop
        $current_date = Time::now();

        // Loop through each record
        foreach ($tldet_data as $i => $data) {
            // Check if there is a due date
            if ($data['tldt_due_date'] != null) {
                // Parse the due date
                $tldt_due_date = Time::createFromFormat('Y-m-d H:i:s', $data['tldt_due_date']);

                // Check if the current date is after the due date
                if ($current_date->isAfter($tldt_due_date)) {
                    // Calculate the time difference in days
                    $time_difference_days = floor(($current_date->getTimestamp() - $tldt_due_date->getTimestamp()) / 86400);

                    // Check if the time difference exceeds the allowed due days
                    if ($time_difference_days > $data['rpt_due_days']) {
                        // Calculate the due rent price based on delay
                        $d_price = ($time_difference_days * $data['tool_rent_cost'] * $data['tldt_tool_quant'] );
                        $due_rent_price=$d_price+($d_price*$data['tool_delay_percentage'])/ 100;
                        $tr_updated_cost = $due_rent_price + $data['rpt_amount'];

                        // Initialize payment data array
                        $payment_Data = [
                            'rpt_status' => 2,
                            'rpt_due_days' => $time_difference_days
                        ];

                        // Check if there is a deposit
                        if ($data['tool_deposit_id'] != 0) {
                            // Calculate the refund price
                            $refund_price = ($data['tool_rent_cost'] * $data['tldt_tool_duration'] * $data['tldt_tool_quant'] * $data['tool_deposit_price']) / 100;
                            $refund_updated_cost = $refund_price - $due_rent_price;

                            // Adjust payment data based on refund updated cost
                            if ($refund_updated_cost > 0) {
                                $payment_Data['rpt_amount'] = $data['rpt_amount'];
                            } else {
                                $payment_Data['rpt_amount'] = $data['rpt_amount'] - $refund_updated_cost;
                            }
                        } else {
                            // Set payment amount to the updated cost if no deposit
                            $payment_Data['rpt_amount'] = $tr_updated_cost;
                        }

                        // Check and update status if not already 15
                        if ($data['tldt_status'] != 15) {
                            $master = [
                                'tldt_status' => 15,
                                'tldt_paymt_flag' => 1
                            ];
                            $hist = [
                                'trqh_tr_id' => $data['tldet_id'],
                                'trqh_status_id' => 15
                            ];
                            // Update tool request status and history
                            $toolRequestModel->update($data['tldet_id'], $master);
                            $toolrequesthistoryModel->insert($hist);
                        }

                        // Update payment tracker with calculated data
                        $paymentTrackerModel->update($data['rpt_id'], $payment_Data);
                    }
                }
            }
        }
    }

 


    public function calculate_due_req($request)
    {
        

        $current_date = Time::now();

        
            // Trim and check if due date is set and non-empty
            $dateString = trim($request['tldt_due_date']);
            if (!empty($dateString)) {
                // Create date object from due date string
                $tldt_due_date = Time::createFromFormat('Y-m-d H:i:s', $dateString);

                // Calculate time difference in seconds between current date and due date
                $time_difference_seconds = $current_date->getTimestamp() - $tldt_due_date->getTimestamp();
                // Calculate the difference in days (absolute value)
                $time_difference_days = floor(abs($time_difference_seconds) / 86400); // 86400 seconds in a day

                $res=[];
                $res['due_days']=0;
                $res['due_rent_price']=0;
                $res['tr_updated_cost']=0;
                $res['expected_Days']=0;
                // Check if the current date is after the due date
                if ($current_date->isAfter($tldt_due_date)) {
                    
                    // Item is overdue, calculate overdue days and rent price
                    $res['due_days']= (string) $time_difference_days;
                    $d_price = ($time_difference_days * $request['tool_rent_cost'] * $request['tldt_tool_quant'] );
                    $res['due_rent_price']=(string) $d_price+($d_price*$request['tool_delay_percentage'])/ 100;
                    $res['tr_updated_cost']=$res['due_rent_price'] + $request['tool_cost'];
                } else {

                    
                    $res['expected_Days']=(string) $time_difference_days;
                }
            }

            $request['due_days'] = ($res['due_days']!=0)? $res['due_days']:[];
            $request['due_rent_price']=($res['due_rent_price']!=0)?$res['due_rent_price']:[];
            $request['tr_updated_cost'] = ($res['tr_updated_cost']!=0)? $res['tr_updated_cost']:[];
            $request['expected_Days'] = ($res['expected_Days']!=0)? $res['expected_Days']:[];

        // Return the processed $trequest array
        return $request;

    }
}
