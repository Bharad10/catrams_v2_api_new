<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerTypeDetailModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_type_detail';
    protected $primaryKey       = 'cstd_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cstd_id ','cstd_cstp_id ','cstd_file','cstd_created_on','cstd_created_by','cstd_updated_on','cstd_updated_by','cstd_delete_flag'];
}
