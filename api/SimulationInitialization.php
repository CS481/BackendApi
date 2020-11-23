<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
require_once('SimulationFactoryBackend/src/controller/SimulationController.php');
require_once('simulation-schema/php/UserObjValidator.php');
SimulationFactoryBackend\util\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\db\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);
try {
  $conn->beginTransaction();
  $response->simulation_id = SimulationFactoryBackend\controller\initialize_sim($conn, $data->user);
  $conn->submitTransaction();
  print_r(json_encode($response));
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
