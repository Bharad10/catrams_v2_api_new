<?php

namespace App\Models\System;

use CodeIgniter\Model;

class FeatureActionModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'feature_actions';
    protected $primaryKey       = 'fa_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['fa_id','fa_name','fa_created_by','fa_created_on','fa_updated_by','fa_updated_on','fa_delete_flag'];

}
