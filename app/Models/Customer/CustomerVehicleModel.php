<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerVehicleModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_vehicle';
    protected $primaryKey       = 'custveh_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['custveh_id','custveh_datacard_url','custveh_regnumber','custveh_odometer','custveh_vinnumber','custveh_veh_id','custveh_cust_id','custveh_created_on','custveh_created_by','custveh_updated_on','custveh_updated_by','custveh_status_flag','custveh_delete_flag'];
}
