<?php

class Melissa_BusinessLogic_Procedures_Reloaded_FaultTreeSubad
extends Melissa_BusinessLogic_Procedures_Procedure
implements Melissa_BusinessLogic_CommonInterface
{
    protected function handleGetRequest() {
        $this -> responseData = array(
            'name' => 'Дерево отказов СУБАД',
            'entity_name' => $this -> procedureName,
        );
        return $this -> responseData;
    }

    public function execute() {
        $request = Melissa_Service_Http_Request::createFromGlobals();
        $method = $request -> getHttpMethod();
        if ($method === 'POST') {
            $inputData = $request -> getInputData();
            $data = Melissa_Service_Json::decode($inputData['data']);
            return $this -> handleCommandRequest($data);
        }

        $this -> handleGetRequest();
        return $this;
    }

    // Обработка команд
     
    protected function handleCommandRequest($data) {    
        $this -> responseData = $this -> getTitlesList();
        return $this -> responseData;
    }
    
    // Получаем справочник "Дерево отказов"
    private function getTitlesList() {
        $response = Melissa_Service_MPDOStateStore::doStatementMPDO(dirname(__FILE__).'/queries/getFullTree.sql');

        if ($response -> hasError()) {
            throw New ErrorException($result -> getError());
        }
        $elems = $response->getData()['data'];
        $parents = self::getParents($elems);
        if (!empty($parents) && is_array($parents)) {
            $main_parent = 0;
            $result = self::createTree($parents, $main_parent);
        }
        return $result;
    }

    // Сформируем массив $parents
    protected function getParents($data) {
        if (is_array($data) && !empty($data)) {
            $parents = array();
            foreach($data as $item) {
                $key = ($item['parentId'] == null || $item['parentId'] == 'null') ? 0 : $item['parentId'];
                if (!array_key_exists($key, $parents)) {
                    $parents[$key] = array();
                }
                array_push($parents[$key], $item);
            }
        }
        return $parents;
    }

    // Сформируем иерархическое дерево
    protected function createTree($arr, $parentId) {
        if (is_array($arr) && $arr[$parentId]) {
            $i = 0;
            foreach($arr[$parentId] as $key => $el) {
                $parent_id = ($el['parentId'] == null) ? 0 : $el['parentId'];
                $event_value = ($el['isGroup'] == true) ? $el['result'] : $el['eventValue'];
                $tree[$i] = array(
                    'id' => $el['id'],
                    'parentId' => $parent_id,
                    'title' => $el['title'],
                    'isGroup' => $el['isGroup'],
                    'trigger' => $el['trigger'],
                    'eventValue' => $event_value
                );
                $children = self::createTree($arr, $el['id']);
                $tree[$i]['children'] = count($children) ? $children : array();
                $i++;
            }
        }
        return $tree;
    }
}
?>
