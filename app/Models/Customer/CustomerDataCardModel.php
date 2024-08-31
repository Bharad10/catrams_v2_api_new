<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerDataCardModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_vehicle_datacard';
    protected $primaryKey       = 'cvehcard_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cvehcard_id',
        'cvehcard_custveh_id',
        'cvehcard_url',
        'cvehcard_created_on',
        'cvehcard_created_by',
        'cvehcard_updated_on',
        'cvehcard_updated_by',
        'cvehcard_delete_flag'
    ];
}
