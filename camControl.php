<?php

class Melissa_BusinessLogic_Procedures_Reloaded_CamControl
    extends Melissa_BusinessLogic_Procedures_Procedure
    implements Melissa_BusinessLogic_CommonInterface
{
    
    protected function handleGetRequest()
    {
        $cities = $this->getCities();

        $this->responseData = array(
            'name' => 'Камеры наблюдения',
            'entity_name' => $this->procedureName,
            'breadcrumbs' => 0,
            'cities' => $cities,
            'objects' => $this->getObjects(['cityID' => $cities[0]['value']])
        );

        return $this->responseData;
    }

    public function execute() {
 
        $this->initialize();
        
        $request = Melissa_Service_Http_Request::createFromGlobals();
        $method = $request->getHttpMethod();

        if ($method === 'POST') {
            $data = Melissa_Service_Json::decode(file_get_contents("php://input"));
            return $this->handleCommandRequest($data);
        }

        parent::execute();
    }

    protected function handleCommandRequest($data) { 

        $method = $data['method'];

        try {
            if (!method_exists($this, $method)) {
                throw new ErrorException(sprintf('Не найден метод "%s" ', $method));
            }

            $result = $this->$method($data['data']);

        } catch(Exception $ex) { 
            throw $ex;
        }

        $this->responseData = $result;

        return $this->responseData;
    }

    protected function getCities($data = []) {
        $query = "SELECT 
                M_RAW(Города.Код) AS value,
                M_RAW(Города.Наименование) AS label
            FROM Справочники.Города AS Города
            ORDER BY Города.ПорядокВывода ASC";

        return $this->executeQuery($query);
    }

    protected function getObjects($data) {
        $query = "SELECT DISTINCT
                M_RAW(ОбъектыСтроительства.Код) AS id,
                M_RAW(ОбъектыСтроительства.Наименование) AS title,
                M_RAW(ОбъектыСтроительства.Описание) AS description,
                M_RAW(ОбъектыСтроительства.Адрес) AS address,
                M_RAW(ОбъектыСтроительства.Фото) AS photo
            FROM Справочники.ОбъектыСтроительства AS ОбъектыСтроительства
            INNER JOIN Справочники.КамерыНаблюдения AS КамерыНаблюдения ON КамерыНаблюдения.Объект = ОбъектыСтроительства.Код
            WHERE M_RAW(ОбъектыСтроительства.Город) = ".$data['cityID'].
            ($data['value'] ? " AND LOWER(ОбъектыСтроительства.Наименование) LIKE '%".mb_strtolower($data['value'])."%'" : '').
            " ORDER BY ОбъектыСтроительства.Наименование ASC";

        return $this->executeQuery($query);
    }

    protected function loadVideos($data) {
        $query = "SELECT
                M_RAW(КамерыНаблюдения.Наименование) AS title,
                M_RAW(КамерыНаблюдения.СсылкаНаКамеру) AS video
            FROM Справочники.КамерыНаблюдения AS КамерыНаблюдения
            WHERE M_RAW(КамерыНаблюдения.Объект) = ".$data['id']."
            ORDER BY КамерыНаблюдения.Наименование ASC";

        return $this->executeQuery($query);
    }

    protected function playVideo($data) {
        $this->stopVideo();

        $appFolder = '/app/streams/'.$this->guidv4();
        $folder = '/var/opt/opvf/ddr_web'.$appFolder;

        mkdir($folder, 0777);
        
        $command = 'ffmpeg -rtsp_transport tcp -i '.$data['link'].' -filter:v scale=1024:-2 -vcodec h264 -y '.$folder.'/index.m3u8';

        $descriptorspec = [  
            0 => ["pipe", "r"],
            1 => ["pipe", "w"]
        ];
        
        if (is_resource($prog = proc_open("nohup ".$command, $descriptorspec, $pipes))) {
            $ppid = proc_get_status($prog);  
            $pid = $ppid['pid'];  
  
            $pid = $pid + 1;  
        }

        return $appFolder.'/index.m3u8';
    }

    protected function stopVideo($data = []) {
        $os = php_uname('s');

        if($os == "Linux") { 
            exec("killall ffmpeg");
        }
        
        return true;
    }

    private function guidv4($data = null) {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);
    
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
    }

    private function executeQuery($q) {
        $translator = Melissa_Translator_Translator::instance();
        $response = $translator->getResponse($q);
        if ($response->hasError()) throw new ErrorException($response->getError());
        $result = $response->getData();

        return $result['data'];
    }

}
