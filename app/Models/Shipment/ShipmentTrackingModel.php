<?php

namespace App\Models\Shipment;

use CodeIgniter\Model;

class ShipmentTrackingModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'shipment_tracking';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [

        'shtrack_id',
        'shtrack_shm_id',
        'shtrack_status',
        'shtrack_created_on',
        'shtrack_created_by',
        'shtrack_delete_flag'
    ];


}
