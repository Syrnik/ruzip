<?php

class ruzipImportCli extends waCliController
{
    
    /** @var ruzipZipModel Модель */
    protected $Zip;
    
    /** @var waRegionModel Модель региона */
    protected $Region;

    public function __construct() {
        $this->Zip = new ruzipZipModel();
        $this->Region = new waRegionModel();
        
        if(!extension_loaded('dbase'))
            throw new Exception("Не загружено расширение dbase");
    }
    
    public function execute() {
        $filename = waRequest::param('file', '', waRequest::TYPE_STRING_TRIM);
        
        if(!$filename)
            throw new Exception("Не указано имя файла");
        
        $dbf = new ruzipDbaseFile();
        $dbf->open($filename);
        
        $num_records = $dbf->count();
        
        echo "Records: $num_records\n";
        
//        var_dump($dbf->header_info());
        
        for($i = 1; $i <= $num_records; $i++)
        {
            $row = $dbf->getAssoc($i);
            
            if($row && (!isset($row['deleted']) || !$row['deleted']))
            {
                $row['OPSNAME'] = trim(mb_convert_encoding($row['OPSNAME'], 'UTF-8', 'CP866'));
                $row['OPSTYPE'] = trim(mb_convert_encoding($row['OPSTYPE'], 'UTF-8', 'CP866'));
                $row['REGION'] = mb_convert_case(trim(mb_convert_encoding($row['REGION'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');
                $row['AUTONOM'] = mb_convert_case(trim(mb_convert_encoding($row['AUTONOM'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');
                $row['AREA'] = mb_convert_case(trim(mb_convert_encoding($row['AREA'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');
                $row['CITY'] = mb_convert_case(trim(mb_convert_encoding($row['CITY'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');
                $row['CITY_1'] = mb_convert_case(trim(mb_convert_encoding($row['CITY_1'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');
                
                $data =array();
                
                $data['zip'] = $row['INDEX'];
                
                $find_region = (empty($row['REGION']) ? $row['AUTONOM'] : $row['REGION']);
                
                if($find_region == 'Северная Осетия-Алания Республика')
                    $find_region = 'Северная Осетия-Алания';
                
                $wa_region = $this->Region->getByField(['country_iso3'=>'rus', 'name'=>$find_region]);
                
                if(!empty($wa_region))
                {
                    $data['region_code'] = $wa_region['code'];
                    $data['city'] = empty($row['CITY']) ? $row['REGION'] : $row['CITY'];
                    $data['actdate'] = date("Y-m-d", strtotime($row['ACTDATE']));
                    
                    $this->Zip->insert($data);
                }
                
//                if(empty($wa_region))
//                {
//                
//                if(empty($row["CITY"]))
//                {
//                    echo "{$row["INDEX"]} : {$row['OPSNAME']} : {$row['OPSTYPE']} : {$row['REGION']} : {$row['AUTONOM']}: {$row['AREA']} : {$row['CITY']} : {$row['CITY_1']}";
//                    echo date("d-M-Y", strtotime($row['ACTDATE']));
//                    echo "\n";
//                }
            }
        }
        
        $dbf->close();
        
    }
    
}