<?php

namespace App\Models\Payment;

use CodeIgniter\Model;

class PaymentHistoryModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'request_payment_history';
    protected $primaryKey       = 'rph_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
    'rph_id',
    'rph_by_type', //		 0-from customer 1-from user
    'rph_type',     //	 0-service request 1-tool request 2-purchase order
    'rph_rq_id',   //Request ID for the particular type
    'rph_transaction_id',  
    'rph_status',  //	0-Payment pending 1-Payment initiated 2-Payment success 3-Payment failed 4-Refund initiated 5-Refund success 6-Refund failed
    'rph_amount',
    'rph_created_on',
    'rph_created_by',
    'rph_delete_flag'
];
}
