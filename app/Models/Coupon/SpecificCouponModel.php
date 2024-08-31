<?php

namespace App\Models\Coupon;

use CodeIgniter\Model;

class SpecificCouponModel extends Model
{
    protected $table            = 'specific_coupons';
    protected $primaryKey       = 'sc_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['sc_id','sc_coupon_id','sc_type','sc_item_id','sc_created_by','sc_updated_by','sc_deleteflag'];
}
