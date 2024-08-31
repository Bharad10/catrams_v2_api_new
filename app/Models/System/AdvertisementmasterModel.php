<?php

namespace App\Models\System;

use CodeIgniter\Model;

class AdvertisementmasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'advertisement_details';
    protected $primaryKey       = 'ads_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ads_id','ads_name','ads_desc','ads_image','ads_type','ads_active_flag','ads_created_by','ads_created_on','ads_updated_by','ads_updated_on','ads_delete_flag'];

}
