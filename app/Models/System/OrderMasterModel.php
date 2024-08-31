<?php

namespace App\Models\System;

use CodeIgniter\Model;

class OrderMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'order_master';
    protected $primaryKey       = 'order_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_id',
        'order_number',
        'order_status',
        'order_total_cost',
        'order_discount',
        'order_rq_id',
        'order_pay',
        'order_created_on',
        'order_created_by',
        'order_updated_on',
        'order_updated_by',
        'order_delete_flag',
        'order_est_days',
        'order_act_days',
        'order_address'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
