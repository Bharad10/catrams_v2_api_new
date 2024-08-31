<?php

namespace App\Models\Payment;

use CodeIgniter\Model;

class ToolRequestPaymentModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tool_request_payment';
    protected $primaryKey       = 'tlrq_pay_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['tlrq_pay_id ','tlrq_reqsts_id  ','tlrq_pay_amount ','tlrq_adv_pay_amount ','tlrq_payment_created_on '];
}
