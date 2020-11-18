<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
require_once('SimulationFactoryBackend/src/controller/DataDumpController.php');
SimulationFactoryBackend\util\only_allow_method('POST');
$user->username = $_POST['username'];
$user->password = $_POST['password'];
$data->user = $user;
$data->simulation_id = $_POST['sim_id'];
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
