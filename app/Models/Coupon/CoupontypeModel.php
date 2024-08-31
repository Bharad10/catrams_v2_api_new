<?php

namespace App\Models\Coupon;

use CodeIgniter\Model;

class CoupontypeModel extends Model
{
    protected $table            = 'coupon_type_master';
    protected $primaryKey       = 'coupon_type_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['coupon_type_id','coupon_type_name','coupon_type_delete_flag'];
}
