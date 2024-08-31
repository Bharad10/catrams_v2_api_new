<?php

namespace App\Models\Packages;

use CodeIgniter\Model;

class ServiceRequestPackageModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'service_request_package';
    protected $primaryKey       = 'servpack_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['servpack_id','servpack_name','servpack_desc','servpack_cost','servpack_active_flag','servpack_updated_on','servpack_updated_by','servpack_created_on ','servpack_created_by','servpack_delete_flag'];
}
