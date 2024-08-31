<?php

namespace App\Models\Request;

use CodeIgniter\Model;

class RequesMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'request_master';
    protected $primaryKey       = 'rm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['rm_id','rm_name','rm_desc','rm_created_on','rm_created_by','rm_updated_on','rm_updated_by','rm_delete_flag'];
}
