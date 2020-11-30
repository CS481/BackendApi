<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
require_once('SimulationFactoryBackend/src/controller/SimulationController.php');
require_once('simulation-schema/php/UserValidator.php');
SimulationFactoryBackend\util\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\db\DBConnFactory();
$conn = new $db_conn_class($data->username, $data->password);
try {
  $conn->beginTransaction();
  $response->id = SimulationFactoryBackend\controller\initialize_sim($conn, $data);
  $conn->submitTransaction();
  print_r(json_encode($response));
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
