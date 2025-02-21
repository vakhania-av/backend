<?php

/**
 * @author Вахания Антон
 */

 class Melissa_BusinessLogic_Procedures_Reloaded_CustomPortal
 extends Melissa_BusinessLogic_Procedures_Procedure
 implements Melissa_BusinessLogic_CommonInterface
 {
  /**
   * Функция обработки запроса на получение данных
   * @return array responseData - полученные данные
   */
  protected function handleGetRequest() {

    $this->responseData = array(
      'name' => 'Портал Губернатора',
      'entity_name' => $this->procedureName,
      'portals' => $this->getPortals(),
      'menu' => $this->getMenu(),
      'dashboardMenuItems' => $this->getSubsystemById(12)
    );

    return $this->responseData;
  }

  /**
   * Служебная функция инициализации и выполнения команды на запрос данных от сервера
   */
  public function execute() {
    $this->initialize();

    $request = Melissa_Service_Http_Request::createFromGlobals();
    $method = $request->getHttpMethod();

    if ($method === "POST") {
      $data = Melissa_Service_Json::decode(file_get_contents("php://input"));

      return $this->handleCommandRequest($data);
    }

    parent::execute();
  }

  /**
   * Функция-обработчик командного запроса
   * @param object $data - массив данных
   * @return array responseData - полученные данные
   */
  protected function handleCommandRequest($data) {
    $method = $data["method"];

    try {
      if (!method_exists($this, $method)) {
        throw new ErrorException(sprintf("Не найден метод '%s' ", $method));
      }

      $result = $this->$method($data["data"]);
    } catch (Exception $exception) {
      throw $exception;
    }

    $this->responseData = $result;

    return $this->responseData;
  }

  /**
   * Функция получения данных из справочника "Порталы"
   * @return array $data - массив полученных данных
   */
  protected function getPortals() {
    $query_name = "getPortals.sql";
    return $this->execQueryWithResult($query_name);
  }

  /**
   * Функция-запрос на получение списка элементов подсистемы (WEB sybsystem)
   * @return array object $result - перечень элементов текущей подсистемы (задана в conf_spo)
   */
  protected function getMenu() {
    $dataController = Melissa_Data_Subsystems_MainMenu::getInstance();
    $interface = $dataController->getTotalInterfaceData();
    $result = [];

    foreach ($interface as $item) {
      $result[] = array(
        "title" => $item["data"]["name"],
        "url" => $item["data"]["url"],
        "image" => $item["data"]["image"]
      );
    }

    return $result;
  }

  /**
   * Функция-исполнитель запроса, возвращающая результат в виде массива элементов
   * @param string $query_title - наименование файла запроса с расширением *.sql
   * @return array $data - массив полученных данных
   */
  private function execQueryWithResult($query_title) {
    if (!$query_title) {
      throw new ErrorException(sprintf("Не найден путь к запросу '%s '", $query_title));
    }

    $query_path = '/queries'.'/'.$query_title;

    $response = Melissa_Service_MPDOStateStore::doStatementMPDO(dirname(__FILE__).$query_path);

    if ($response->hasError()) {
      throw new ErrorException($result->getError());
    }

    return $response->getData()["data"];
  }

  private function getSubsystemById($subsystem_id = 0) {
    $query_name = '/queries/getSubsystemById.sql';

    if (!$subsystem_id) {
      throw new Melissa_Error("Не задан параметр для '%s '", $subsystem_id);
    }

    $file_query_name = dirname(__FILE__).$query_name;
    $query = file_get_contents($file_query_name);

    $statement = Melissa_Service_Database_Postgres::prepare($query);
    $statement->bindValue('subsystemId', $subsystem_id);
    $statement->execute();

    return $statement->fetchAll();
  }

 }

?>
