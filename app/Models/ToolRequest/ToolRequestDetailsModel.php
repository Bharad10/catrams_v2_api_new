<?php

namespace App\Models\ToolRequest;

use CodeIgniter\Model;

class ToolRequestDetailsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tool_request_details';
    protected $primaryKey       = 'tldet_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [

    'tldet_id',
    'tldt_pmd_flag',//0-Premium Discount Not Added 1-Premium Discount Added
    'tldt_reference',//1.For Tool Recommneded from service request ,service request id will be stored here.	
    'tldt_damaged',//0-no damage 1-Damaged
    'tldt_R_date',
    'tldt_due_date',
    'tldt_adv_cost',
    'tldt_purchase_flag',//0-default 1-pending 2-quote created 3-rejected 4-purchase complete 5-quote rejected	
    'tldt_number',
    'tldt_cstm_id',
    'tldt_tool_id',
    'tldt_paymt_flag', //0-Payment completed 1-Payment Not Completed
    'tldt_tool_duration',
    'tldt_hold_flag',//0-no hold 1-hold
    'tldt_hold_by',
    'tldet_reqsts_id',
    'tldt_tool_quant',
    'tldt_cost',
    'tldet_invm_id',
    'tldt_status',
    'tldt_delivery_address',
    'tldt_created_on',
    'tldt_created_by',
    'tldt_updated_on',
    'tldt_updated_by',
    'tldt_delete_flag',
    'tldt_active_flag',//0-Not Active 1-Active
    'tldt_advpaymt_flag'//	0-No advance payment 1-Advance Payment
    
];
}
