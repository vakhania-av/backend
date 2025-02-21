<?php

/**
 * Проигрыватель дашбордов
 */
class Melissa_BusinessLogic_Procedures_Reloaded_DashboardPlayer extends Melissa_BusinessLogic_Procedures_Procedure implements Melissa_BusinessLogic_CommonInterface {

    public function execute() {
        $this->initialize();

        $request = Melissa_Service_Http_Request::createFromGlobals();

        if ($request->getHttpMethod() === 'POST') {
            $inputData = $request->getInputData();
            $data = Melissa_Service_Json::decode($inputData['data']);
            
            return $this->handleRequest($_POST);
            //return $this->handleCommandRequest($data);
        }
        parent::execute();
    }

    protected function handleRequest($data) {
       if ($command = filter_input(INPUT_GET, 'command') === 'savePanel')  {
            $img = filter_input(INPUT_POST, 'img');
            $nameImg = filter_input(INPUT_POST, 'nameImg');
            if ($img){
              $this->responseData =  array(
                'resultSave' => $this->createPdf($img,$nameImg),              
              );
            };          
         
         return $this->responseData;
       }
        $request = Melissa_Service_Http_Request::createFromGlobals();
        if(isset($_GET['id'])){
          if ($id = intval($_GET['id'])) {
              $dashboardData = $this->getDashboardData($id);
              $name = '';
              if (isset($dashboardData["Наименование"])) {
                  $name = $dashboardData['Наименование'];
              }
              
              $translator = Melissa_Translator_Translator::instance();
              $q = 'SELECT Параметры.Наименование, Параметры.Наименованиеполное, Параметры.ЗначениеПоУмолчаниюСырое, Параметры.ТипДанныхСырое, '
                      . 'Параметры.Массив, Параметры.СоставнойТип, Параметры.Обязательный FROM Справочники.Дашборды.Параметры AS Параметры '
                      . 'WHERE Параметры.Ссылка.id = '.$id;
              $response = $translator->getResponse(
                $q , Melissa_Translator_Const::FL_ONLYSELECTEXECUTE, []
              );
              if($response->hasError())
              {
                $q = 'SELECT Параметры.Наименование, Параметры.ЗначениеПоУмолчаниюСырое, Параметры.ТипДанныхСырое, '
                      . 'Параметры.Массив, Параметры.СоставнойТип, Параметры.Обязательный FROM Справочники.Дашборды.Параметры AS Параметры '
                      . 'WHERE Параметры.Ссылка.id = '.$id;
                $response = $translator->getResponse(
                  $q , Melissa_Translator_Const::FL_ONLYSELECTEXECUTE, []
                );
              }
              
              if($response->hasError())
              {
                throw new Melissa_Error(
                  sprintf('При выборе параметров для аналитической панели произошла ошибка: %s', 
                    $response->getError()
                  )
                );
              }
              $params = Melissa_Data_MetaQueryParamsTable_ParamsTable::covFromPartTableToParamsTable(
                $response->getData()
              );
              
              if (isset($data['data'])) {
                $data = $data['data'];
                //print_r(json_decode(base64_decode($data))); die('<<');
              }
              
              $this->responseData = array(
                  'entity_name' => $this->procedureName,
                  //дерево всех объектов систем
                  'dashboard' => $dashboardData,
                  'postData'  => $data,
                  // Параметры
                  'user_data' => $this->prepareUserData(),
                  'params'    => $params,
                  'nobreadcrumbs'    => isset($_GET['nobreadcrumbs'])?$_GET['nobreadcrumbs']:null,
                  //Имя для хлебных крошек
                  'name'      => $name,
                  'paramsTypeList' => Melissa_Data_MetaQueryParamsTable_ParamsTable::paramsTypeList(),
                  'functionCodeList' => Melissa_BusinessLogic_Dashboards_FunctionsCodeList::loadFunctionsCodeList()
              );
              
              return $this->responseData;
          }

        }
        if( isset($_GET['uuid'])){
          if ($uuid = $_GET['uuid']) {
              
              $dashboardData = $this->getDashboardData($uuid);
              $name = '';
              if (isset($dashboardData["Наименование"])) {
                  $name = $dashboardData['Наименование'];
              }
              
              $translator = Melissa_Translator_Translator::instance();
              $q = 'SELECT Параметры.Наименование, Параметры.Наименованиеполное, Параметры.ЗначениеПоУмолчаниюСырое, Параметры.ТипДанныхСырое, '
                      . 'Параметры.Массив, Параметры.СоставнойТип, Параметры.Обязательный FROM Справочники.Дашборды.Параметры AS Параметры '
                      . "WHERE Параметры.Ссылка.uuid = '".$uuid."'";
              
              $response = $translator->getResponse(
                  $q , Melissa_Translator_Const::FL_ONLYSELECTEXECUTE, []
                );
              if($response->hasError())
              {
                $q = 'SELECT Параметры.Наименование, Параметры.ЗначениеПоУмолчаниюСырое, Параметры.ТипДанныхСырое, '
                      . 'Параметры.Массив, Параметры.СоставнойТип, Параметры.Обязательный FROM Справочники.Дашборды.Параметры AS Параметры '
                      . "WHERE Параметры.Ссылка.uuid = '".$uuid."'";
                $response = $translator->getResponse(
                  $q , Melissa_Translator_Const::FL_ONLYSELECTEXECUTE, []
                );
              }
              
              if($response->hasError())
              {
                throw new Melissa_Error(
                  sprintf('При выборе параметров для аналитической панели произошла ошибка: %s', 
                    $response->getError()
                  )
                );
              }
              
              $params = Melissa_Data_MetaQueryParamsTable_ParamsTable::covFromPartTableToParamsTable(
                $response->getData()
              );
              
              if (isset($data['data'])) {
                $data = $data['data'];
                //print_r(json_decode(base64_decode($data))); die('<<');
              }
              
              $this->responseData = array(
                  'entity_name' => $this->procedureName,
                  //дерево всех объектов систем
                  'dashboard' => $dashboardData,
                  'postData'  => $data,
                  // Параметры
                  'user_data' => $this->prepareUserData(),
                  'params'    => $params,
                  'nobreadcrumbs'    => isset($_GET['nobreadcrumbs'])?$_GET['nobreadcrumbs']:null,
                  //Имя для хлебных крошек
                  'name'      => $name,
                  'paramsTypeList' => Melissa_Data_MetaQueryParamsTable_ParamsTable::paramsTypeList(),
                  'functionCodeList' => Melissa_BusinessLogic_Dashboards_FunctionsCodeList::loadFunctionsCodeList()
              );
              
              return $this->responseData;
          }
        }
        
    }
    /**
     * Обрабатывает запрос на получение элементов процедуры
     */
    protected function handleGetRequest() {
      return $this->handleRequest(null);
    }
            
    private function createPdf($img,$nameImg){
      $dataBaseConnect = new Melissa_Service_Database_Pgsql();
      $sqlQuery = "INSERT INTO s_content.tmp_data (name_full, data) VALUES ('".$nameImg."', '".$img."') RETURNING id";            
      $res = $dataBaseConnect->get_result($sqlQuery);     
    }
    
    protected function prepareUserData() 
    { 
        $dbSettings = null;
        if (class_exists( "MelissaApp_BusinessLogic_Dashboards_Settings", true )) {
          $dbSettings = MelissaApp_BusinessLogic_Dashboards_Settings::getSettings();
        } else {
          $dbSettings = Melissa_BusinessLogic_Dashboards_Settings::getSettings();
        }
        return array(
            'paramsTypeList' => Melissa_Data_MetaQueryParamsTable_ParamsTable::paramsTypeList(),
            'dbSettings' => $dbSettings
        );
    }

    private function getDashboardData($id) {
        $translator = Melissa_Translator_Translator::instance();
        
        if (is_numeric($id)) {
          $response = $translator->getResponse(
              "SELECT 
                  Дашборды.id AS id, 
                  Дашборды.Данные AS Данные, 
                  Дашборды.Наименование AS Наименование
              FROM 
                  Справочники.Дашборды AS Дашборды WHERE id=".$id, 
              Melissa_Translator_Const::FL_ONLYSELECTEXECUTE
          );
        }
        else {
          $response = $translator->getResponse(
              "SELECT 
                  Дашборды.id AS id, 
                  Дашборды.uuid AS uuid, 
                  Дашборды.Данные AS Данные, 
                  Дашборды.Наименование AS Наименование
              FROM 
                  Справочники.Дашборды AS Дашборды WHERE uuid='".$id."'", 
              Melissa_Translator_Const::FL_ONLYSELECTEXECUTE
          );
        }
        
        $data = $response->getData();
        $data = $data['data'][0];
        return $data;
    }
}
