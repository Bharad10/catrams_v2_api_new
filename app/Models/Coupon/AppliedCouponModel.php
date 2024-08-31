<?php

namespace App\Models\Coupon;

use CodeIgniter\Model;

class AppliedCouponModel extends Model
{
    protected $table            = 'applied_coupon_master';
    protected $primaryKey       = 'acm_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['acm_id','acm_custid','acm_coupon_id','acm_bookid','acm_discountamount','acm_applied_on','acm_created_by','acm_updated_by','acm_deleteflag'];

}
