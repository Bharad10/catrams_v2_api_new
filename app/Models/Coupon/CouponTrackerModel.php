<?php

namespace App\Models\Coupon;

use CodeIgniter\Model;

class CouponTrackerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'coupoun_tracker';
    protected $primaryKey       = 'ct_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ct_coup_id','ct_id','ct_coup_rqtype','ct_coup_rqid','ct_cstm_id','ct_cost','ct_bf_cost','ct_af_cost','ct_created_by','ct_created_on','ct_updated_by','ct_updated_on','ct_delete_flag'];


}
