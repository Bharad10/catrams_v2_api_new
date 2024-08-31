<?php

namespace App\Models\Payment;

use CodeIgniter\Model;

class PaymentTrackermasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'request_payment_tracker';
    protected $primaryKey       = 'rpt_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['rpt_id',
    'rpt_reference',
    'rpt_due_days', //	for how many due days in rent tool
    'rpt_reqt_id',
    'rpt_type', //	1-service 2-tool 3-order
    'rpt_amount',
    'rpt_transaction_id',
    'rpt_cust_id',
    'rpt_status', //	0-Not Paid,1-paid,2-Payment Due,3-Refund	
    'rpt_created_on',
    'rpt_created_by',
    'rpt_updated_on',
    'rpt_updated_by',
    'rpt_delete_flag'
];
}

//rpt_type: 1-service 2-tool 3-order	
//rpt_status: 0-Not Paid,1-paid,2-Payment Due,3-Refund

//<-----------------Easebuzz Payment variables----------------->
//udf1-Request Type -{0-Service Request,1-Tool Request,2-Order Request}
//udf2-Request ID
//udf3=Payment Intitiated User-{0-Customer,1-Cat User}
//udf4-User ID