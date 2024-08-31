<?php

namespace App\Models\Coupon;

use CodeIgniter\Model;

class CouponModel extends Model
{
    protected $table            = 'coupon_master';
    protected $primaryKey       = 'coupon_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['coupon_id','coupon_code','coupon_description','coupon_type_id','coupon_discount','coupon_discount_type','coupon_valid_from','coupon_valid_to','coupon_min_amount','coupon_max_discount','coupon_total_usage','coupon_created_by','coupon_updated_by','coupon_delete_flag'];
}
