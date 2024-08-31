<?php

namespace App\Models\System;

use CodeIgniter\Model;

class OrderHistoryModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'order_history';
    protected $primaryKey       = 'ohist_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ohist_id',
        'ohist_order_id',
        'ohist_order_status',
        'ohist_created_on',
        'ohist_created_by',
        'ohist_updated_on',
        'ohist_updated_by',
        'ohist_delete_flag'
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
