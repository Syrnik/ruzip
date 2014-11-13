<?php

class ruzipDbaseFile
{
    /** @var int */
    private $handler;
    
    public function open($filename)
    {
        if(!$filename || !file_exists($filename))
        {
            throw new Exception ("Не могу открыть файл '{$filename}'");
        }   
        
        $file = dbase_open($filename, 0);
        
        if(!$file)
        {
            throw new Exception ("Не могу открыть файл '{$filename}'");
        }
        
        $this->handler = $file;
        
        return $file;
    }
    
    public function close()
    {
        if(!dbase_close($this->handler))
            throw new Exception('Ошибка закрытия DBF-файла');
    }
    
    /**
     * @return int
     */
    public function count()
    {
        return dbase_numrecords($this->handler);
    }
    
    public function getAssoc($index)
    {
        return dbase_get_record_with_names($this->handler, $index);
    }
    
    public function header_info()
    {
        return dbase_get_header_info($this->handler);
    }
}