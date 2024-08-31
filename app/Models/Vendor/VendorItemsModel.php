<?php

namespace App\Models\Vendor;

use CodeIgniter\Model;

class VendorItemsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'vendor_items';
    protected $primaryKey       = 'vitem_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [

        'vitem_id',
        'vitem_type', //0-for service 1 - for tool
        'vitem_assigne',
        'vitem_rq_id',
        'vitem_vm_id',
        'vitem_cost',
        'vm_cash_percent',
        'vitem_paid_status',
        'vitem_status_flag', //	0-work pending 1-work in progress 2-work completed 3-Tool Recommendation Pending 4-Tool Recommendation Accepted 5-Tool Recommendation Rejected -1=expert not yet accepted 6-Expert Job Complete
        'vitem_hold_flag', //	0-no hold, 1-hold
        'vitem_hold_reason',
        'vitem_reference',  //	1.For Tool Recommendation Pending number of days will be stored here 2. For tool Recommendation Aceepted,Request Id will be stored here
        'vitem_createdon',
        'vitem_createdby ',
        'vitem_updatedon',
        'vitem_updatedby '   ,
        'vitem_deleteflag'
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
