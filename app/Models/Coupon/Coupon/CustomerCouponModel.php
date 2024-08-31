<?php

namespace App\Models\Coupon;

use CodeIgniter\Model;

class CustomerCouponModel extends Model
{
    protected $table            = 'customer_specific_coupons';
    protected $primaryKey       = 'csc_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['csc_id','csc_coupon_id','csc_cust_id','csc_created_by','csc_updated_by','csc_deleteflag'];


}
