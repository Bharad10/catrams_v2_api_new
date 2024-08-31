<?php

namespace App\Models\Shipment;

use CodeIgniter\Model;

class ShipmentMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'shipment_master';
    protected $primaryKey       = 'shm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'shm_id',
        'shm_type', //	0-Rent 1-Purchase 2-Services
        'shm_request_id',
        'shm_status', //	0-Pre transit 1-In Transit 2-Out for delivery 3-Failed attempt 4-Delivered 5-Returned	
        'shm_track_id',
        'shm_track_url',
        'shm_created_on',
        'shm_created_by',
        'shm_updated_on',
        'shm_updated_by',
        'shm_delete_flag',
        'shm_reference',
        'shm_by_type' //	0-user, 1-customer
    ];


}
