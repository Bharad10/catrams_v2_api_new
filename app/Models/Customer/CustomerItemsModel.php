<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerItemsModel extends Model
{

    protected $DBGroup          = 'default';
    protected $table            = 'customer_items';
    protected $primaryKey       = 'citems_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'citems_id',
        'citems_custmasterId',
        'citems_custroleId',
        'citems_name',
        'citems_email',
        'citems_password',
        'citems_phone',
        'citems_statusflagId',
        'citems_countrycodeId',
        'citems_fcm_token',
        'citems_vendorflagId',
        'citems_delete_flag'
    ];
    
}
