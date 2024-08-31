<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerProducts extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_products';
    protected $primaryKey       = 'cp_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cp_serial',
        'cp_id',
        'cp_cstm_id',
        'cp_pr_id',
        'cp_sr_id',
        'cp_status',
        'cp_created_by',
        'cp_created_on',
        'cp_updated_by',
        'cp_updated_on',
        'cp_delete_flag',
    ];


}
