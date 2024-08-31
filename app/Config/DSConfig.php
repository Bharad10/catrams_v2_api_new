<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class DSConfig extends BaseConfig
{
    public $name = "Digital Service";
    public $tagline = "We promise we do the best";
	public $author = "Team LIFE";
    public $email = "akhil.raj@logicinfeel.com";

	public function get_info(){
		return $this->name. " - ". $this->author. " - ". $this->tagline. " - ". $this->email;
	}
}
