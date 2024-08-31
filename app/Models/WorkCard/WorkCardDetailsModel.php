<?php

namespace App\Models\WorkCard;

use CodeIgniter\Model;

class WorkCardDetailsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'work_card_details';
    protected $primaryKey       = 'wrkcard_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['wrkcard_id ','wrkcard_servrq_id  ','wrkcard_reqsts_id  ','wrkcard_servdet_id  ','wrkcard_created_on '];
}
