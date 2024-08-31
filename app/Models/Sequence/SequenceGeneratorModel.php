<?php

namespace App\Models\Sequence;

use CodeIgniter\Model;

class SequenceGeneratorModel extends Model
{
    protected $table            = 'sequence_data';
    protected $primaryKey       = 'seq_id';
    protected $allowedFields    = ['seq_id','service_sequence','request_sequence','quote_sequence','toolreq_sequence','order_sequence'];


}
