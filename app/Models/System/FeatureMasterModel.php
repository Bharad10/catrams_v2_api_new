<?php

namespace App\Models\System;

use CodeIgniter\Model;

class FeatureMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'feature_list';
    protected $primaryKey       = 'ft_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ft_id','ft_name','ft_description','	ft_created_on','ft_created_by','ft_updated_on','ft_updated_by','ft_delete_flag'];
}
