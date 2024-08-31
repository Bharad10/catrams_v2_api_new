<?php

namespace App\Models\System;

use CodeIgniter\Model;

class CustomerDiscountModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_discount';
    protected $primaryKey       = 'cd_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cd_type',
        'cd_id',
        'cd_rate',
        'cd_request_type',
        'cd_created_on',
        'cd_created_by',
        'cd_active_flag',
        'cd_updated_on',
        'cd_updated_by',
        'cd_delete_flag',

    ];
}
