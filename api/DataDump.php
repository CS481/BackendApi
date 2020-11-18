<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
require_once('SimulationFactoryBackend/src/controller/DataDumpController.php');
SimulationFactoryBackend\util\only_allow_method('POST');
print_r($_POST);
$data = json_decode(file_get_contents('php://input'));
//print_r(json_encode($data));
exit;
$db_conn_class = SimulationFactoryBackend\db\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);
try {
  $conn->beginTransaction();
  $result = SimulationFactoryBackend\controller\download_responses($conn, $data->user, $data->simulation_id);
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
