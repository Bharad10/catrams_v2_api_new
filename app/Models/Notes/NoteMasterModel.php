<?php

namespace App\Models\Notes;

use CodeIgniter\Model;

class NoteMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'notemasters';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [

        'notes_id',
        'notes_description',
        'notes_assigne_id',
        'notes_type',
        'notes_created_by',
        'notes_created_on',
        'notes_updated_by',
        'notes_updated_on',
        'notes_delete_flag',




    ];
}
