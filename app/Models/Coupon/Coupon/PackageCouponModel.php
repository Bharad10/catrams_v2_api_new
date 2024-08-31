<?php

namespace App\Models\Coupon;

use CodeIgniter\Model;

class PackageCouponModel extends Model
{
    protected $table            = 'package_coupons';
    protected $primaryKey       = 'pc_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['pc_id','pc_coupon_id','pc_pack_id','pc_created_by','pc_updated_by','pc_deleteflag'];
}
