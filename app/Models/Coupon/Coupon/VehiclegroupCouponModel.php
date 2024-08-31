<?php

namespace App\Models\Coupon;

use CodeIgniter\Model;

class VehiclegroupCouponModel extends Model
{
    protected $table            = 'vehicle_group_coupons';
    protected $primaryKey       = 'vgc_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['vgc_id','vgc_coupon_id','vgc_vgroup_id','vgc_created_by','vgc_updated_by','vgc_deleteflag'];

}
