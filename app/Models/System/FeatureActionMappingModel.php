<?php

namespace App\Models\System;

use CodeIgniter\Model;

class FeatureActionMappingModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'feature_action_mapping';
    protected $primaryKey       = 'fam_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['fam_id ','fam_frm_id ','fam_fa_id','fam_created_on','fam_created_by','fam_updated_on','fam_updated_by','fam_delete_flag'];

}
