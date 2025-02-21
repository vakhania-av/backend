<?php
/**
 * Этот файл - часть программной платформы веб-разработки "Мелисса"
 * © 2014 ЗНПАО ОПВЭиФ
 */



/**
 * Класс для генерации бизнес логики. 
 * Случай, когда нужно сгенерировать ответ для процедуры ArmAnalyticAnalytics
 * 
 * @author Вахания А.В.
 */
class MelissaApp_BusinessLogic_Procedures_AtAsPlanning
    extends Melissa_BusinessLogic_Procedures_Procedure 
    implements Melissa_BusinessLogic_CommonInterface
{ 
    /**
     * Возвращает список АП
     * @return array
     */

    public function handleGetRequest()
    {
        parent::handleGetRequest();
        $this->Controller('getAtAsPlanningList');
        return array(
            // набор сервисных данных вроде списка адресов для обращения к api
            'service_date' => '',
            'entity_name' => 'AtAsPlanning',
            'name' => 'Программа создания АТ (АС)',
        );
    }

    protected function Controller($command)
    {
        if ( $command == '' ) {
            $request = Melissa_Service_Http_Request::createFromGlobals();
            $method = $request->getHttpMethod();
            $inputData = $request->getInputData()['data'];
            if ( $method === 'POST' && isset($inputData) ) {
                $data = Melissa_Service_Json::decode($inputData);
                $command = $data['command'];
            }
        }

        $translatorV2 = Melissa_Translator_Translator::instance();
        switch($command)
        {
            // Получим данные из справочника "Программы создания АТ (АС) для построения дерева
            case('getAtAsPlanningList'): {
                $request_text = 
                '
                SELECT 
                    ПереченьПрограмм.id AS id, 
                    ПереченьПрограмм.НаименованиеПлана AS Наименование, 
                    ПереченьПрограмм.ПлановыйСрокНачала AS ПлановыйСрокНачала, 
                    ПереченьПрограмм.ПлановыйСрокОкончания AS ПлановыйСрокОкончания
                FROM 
                    Справочники.ПереченьПрограмм AS ПереченьПрограмм
                ';
                break;
            }

            // Получим данные по Ганту для построения дерева
            case('loadTable_GantData'): {
                //$planId = filter_input(INPUT_GET, 'planId');
                $request_text = 
                '
                SELECT 
                    ПрограммыСозданияАтАс.id AS id, 
                    ПрограммыСозданияАтАс.ИдентификаторРодительскойЗаписи.id AS ИдентификаторРодительскойЗаписи, 
                    ПрограммыСозданияАтАс.IdИсполнителя AS Исполнитель, 
                    ПрограммыСозданияАтАс.IdКонтролирующего AS Контролирующий, 
                    ПрограммыСозданияАтАс.НеблагоприятноеПоследствие AS НеблагоприятноеПоследствие, 
                    ПрограммыСозданияАтАс.ОтметкаОВыполнении AS ОтметкаОВыполнении, 
                    ПрограммыСозданияАтАс.ОценкаУровняРиска AS ОценкаУровняРиска, 
                    ПрограммыСозданияАтАс.ПлановыйСрокНачала AS ПлановыйСрокНачала, 
                    ПрограммыСозданияАтАс.ПлановыйСрокОкончания AS ПлановыйСрокОкончания, 
                    ПрограммыСозданияАтАс.ПредшествующийЭтапПроцессОперация.id AS Порядок, 
                    ПрограммыСозданияАтАс.Программа AS План, 
                    ПрограммыСозданияАтАс.IdПроектируемогоТипаАтАс AS ПроектируемыйТипВС, 
                    ПрограммыСозданияАтАс.IdЛси AS ЛСИ,
                    ПрограммыСозданияАтАс.Угроза AS Угроза, 
                    ПрограммыСозданияАтАс.ФактическийСрокНачала AS ФактическийСрокНачала, 
                    ПрограммыСозданияАтАс.ФактическийСрокОкончания AS ФактическийСрокОкончания, 
                    ПрограммыСозданияАтАс.НаименованиеЭлемента AS ЭтапПроцессОперация
                FROM 
                    Справочники.ПрограммыСозданияАтАс AS ПрограммыСозданияАтАс 
                WHERE
                    ПрограммыСозданияАтАс.Программа.id = '.$data['planId'].' 
                ORDER BY ПрограммыСозданияАтАс.ПлановыйСрокНачала';
                break;
            }

            default: {
                $request_text = '';
                break;
            }     
        }

        if ( $request_text != '' ) {
            $trResp = $translatorV2->getResponse($request_text);
            // Если возникли ошибки трансляции в новом трансляторе
            if ( $trResp->hasError() === true ) {
                return 'Ошибка выборки данных!';
            }
            else {
                $queryResult = $this->prepareServiceData($trResp->getData());
            }
        }
        return $queryResult;
    }

    /**
     * Подготавливает набор сервисных данных, которые нужно включить в ответ бизнес-логики
     * 
     *  @return array
     */

    protected function prepareServiceData($tableData)
     {
         $request = Melissa_Service_Http_Request::createFromGlobals();
         $virtualRoot = $request->getVirtualProjectRoot();

         // Для новых элементов справочника необходимо обращаться к API-блоку справочника
         $elementExists = $this->itemId !== 'new';
         $apiAddress = $virtualRoot . 'api/procedures/AtAsPlanning';

         return array(
             'form_address' => $virtualRoot . 'procedures/AtAsPlanning',
             'api_address' => $apiAddress,
             'element_exists' => 0,
             'tableData' => $tableData,
         );
    }

    /**
      * Инициализирует объект бизнес-логики. Возвращает сам объект.
      *
      * @return Melissa_BusinessLogic_Base 
    */

    protected function initialize()
    {
        $this->isInitialized = true;
        return $this;
    }

    /**
     * Запуск обработки пользовательского запроса. Возвращает ссылку на сам объект.
     * 
     */

    public function execute()
    {
        // Если объект ещё не был инициализирован, сделаем это сейчас
        !$this->isInitialized && $this->initialize();

        if ( $this->isApiCall ) {
            $this->responseData = $this->Controller(filter_input(INPUT_GET, 'command'));
        }
        else {
            $this->responseData = $this->handleGetRequest();
        }

        return $this->responseData;
    }
}

?>
