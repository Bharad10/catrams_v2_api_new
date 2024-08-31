<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerTypeModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_type';
    protected $primaryKey       = 'cstp_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cstp_id ','cstp_name ','cstp_desc ','cstp_created_on ','cstp_created_by ','cstp_updated_on ','cstp_updated_by ','cstp_delete_flag '];
}
