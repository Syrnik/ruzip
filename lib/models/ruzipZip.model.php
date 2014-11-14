<?php

class ruzipZipModel extends waModel
{
    protected $table = 'ruzip_zip';
    
    public function truncate()
    {
        $this->exec("TRUNCATE {$this->table}");
    }
    
}