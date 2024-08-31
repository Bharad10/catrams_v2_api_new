<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerBankModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_bank_details';
    protected $primaryKey       = 'cb_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [


        'cb_id',
        'cb_cstm_id',
        'cb_acc_no',
        'cb_ifsc_no',
        'cb_upi_id',
        'cb_delete_flag'
        
    ];

}
