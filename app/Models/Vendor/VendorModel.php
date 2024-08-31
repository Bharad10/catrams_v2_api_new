<?php

namespace App\Models\Vendor;

use CodeIgniter\Model;

class VendorModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'vendor_master';
    protected $primaryKey       = 'vm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [

        'vm_id',
        'vm_cstm_id',
        'vm_serm_id',
        'vm_status',      //0-pending 1-Accepted 2-Rejected 3-quote pending 4-quote created 5-quote approval pending 5-quote approval accepted 6-quote approval rejected 7-work due 8-work started 9-work in progress 10-work completed 11-work in hold 12-Assigne Changed
        'vm_reject_reason',
        'vm_created_by',
        'vm_created_on',
        'vm_updated_by',
        'vm_updated_on',
        'vm_active_flag',//0-active 1-not active
        'vm_delete_flag',
        'vm_payment_status',     //0-Payment Pending 1-Part-Payment Completed   2-Payment-Completed
        'vm_total_cost',
        'vm_cash_percent',
        'vm_paid_amount',
        'vm_txn_id'
        
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
