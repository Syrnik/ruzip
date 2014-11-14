<?php
/**
 * Контроллер для импорта DBF-файла почты России
 */

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
        $truncate_table = waRequest::param('truncate', 0, waRequest::TYPE_INT);
        
        if(!$filename)
            throw new Exception("Не указано имя файла");

        $dbf = new ruzipDbaseFile();
        $dbf->open($filename);

        $start_time = time();
        
        $num_records = $dbf->count();
        
        if($num_records && $truncate_table) {
            $this->Zip->truncate();
        }

        echo "Records: $num_records\n";

        for($i = 1; $i <= $num_records; $i++)
        {
            $row = $dbf->getAssoc($i);

            if($row && (!isset($row['deleted']) || !$row['deleted']))
            {
                $row = $this->convertEncoding($row);

                $data =array();

                $data['zip'] = $row['INDEX'];

                $find_region = (empty($row['REGION']) ? $row['AUTONOM'] : $row['REGION']);

                if($find_region == 'Северная Осетия-Алания Республика')
                    $find_region = 'Северная Осетия-Алания';

                if($row['AUTONOM'] == 'Ханты-Мансийский-Югра автономный округ')
                    $find_region = $row['AUTONOM'];

                $wa_region = $this->Region->getByField(['country_iso3'=>'rus', 'name'=>$find_region]);
                
                if(empty($wa_region) && ($find_region == 'Крым Республика')) {
                    $wa_region['code'] = 91;
                }

                if(empty($wa_region) && ($find_region == 'Севастополь')) {
                    $wa_region['code'] = 92;
                }

                if(!empty($wa_region))
                {
                    $data['region_code'] = $wa_region['code'];
                    $data['city'] = empty($row['CITY']) ? $row['REGION'] : $row['CITY'];
                    $data['actdate'] = date("Y-m-d", strtotime($row['ACTDATE']));

                    $this->Zip->insert($data);
                } else {
                    waLog::log('Необработано: "' . implode('|', $row) . '"', 'ruszipimport.log');
                }
            }
        }

        $dbf->close();
        
        echo "\n=========================================================";
        echo "\n Time: " . $this->printTimeDiff(time() - $start_time);
        echo "\n=========================================================";
        echo "\n";

    }

    /**
     * Convert all rows from cp866 то UTF-8
     * @param array $row
     * @return array
     */
    private function convertEncoding($row) {

        $row['OPSNAME'] = trim(mb_convert_encoding($row['OPSNAME'], 'UTF-8', 'CP866'));
        $row['OPSTYPE'] = trim(mb_convert_encoding($row['OPSTYPE'], 'UTF-8', 'CP866'));
        $row['REGION'] = mb_convert_case(trim(mb_convert_encoding($row['REGION'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');
        $row['AUTONOM'] = mb_convert_case(trim(mb_convert_encoding($row['AUTONOM'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');
        $row['AREA'] = mb_convert_case(trim(mb_convert_encoding($row['AREA'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');
        $row['CITY'] = mb_convert_case(trim(mb_convert_encoding($row['CITY'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');
        $row['CITY_1'] = mb_convert_case(trim(mb_convert_encoding($row['CITY_1'], 'UTF-8', 'CP866')), MB_CASE_TITLE, 'UTF-8');

        return $row;
    }
    
    private function printTimeDiff($time) {
        
        $hrs = floor($time/3600);
        $time -= $hrs*3600;
        $mins = floor($time/60);
        $time -= $mins*60;
        
        return sprintf("%02d:%02d:%02d", $hrs, $mins, $time);
    }
}