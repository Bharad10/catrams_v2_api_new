<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerActionsMappingModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_actions_mapping';
    protected $primaryKey       = 'cactionsmapping_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cactionsmapping_id',
        'cactionsmapping_roleId',
        'cactionsmapping_custId',
        'cactionsmapping_actionId',
        'cactionsmapping_refernce',
        'cactionsmapping_featureId'
    ];
}
