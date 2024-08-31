<?php

namespace App\Models\Order;

use CodeIgniter\Model;

class OrderrequesttrackerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'order_request_tracker';
    protected $primaryKey       = 'ort_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ort_id',
        'ort_order_id',
        'ort_url',
        'ort_type',
        'ort_created_on',
        'ort_created_by',
        'ort_updated_on',
        'ort_updated_by',
        'ort_delete_flag'
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
