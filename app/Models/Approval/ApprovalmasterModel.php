<?php

namespace App\Models\Approval;

use CodeIgniter\Model;

class ApprovalmasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'approvalmaster';
    protected $primaryKey       = 'am_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['am_id','am_url','am_reason','am_reqid','am_type','am_requestedby','am_referenceid','am_status','am_createdby','am_createdon','am_updatedby','am_updatedon','am_deleteflag'];

}
