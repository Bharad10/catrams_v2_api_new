<?php

namespace App\Models\VehicleMaster;

use CodeIgniter\Model;

class CatVehicleDataModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'cat_vehicle_data';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id','make_name','model_name','variant_name'];
}
