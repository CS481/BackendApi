<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
SimulationFactoryBackend\util\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\db\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);
try {
  $conn->beginTransaction();
  $sim_data->username = $data->user->username;
  $response->simulation_id = $conn->insert('Simulations', $sim_data);
  $conn->submitTransaction();
  print_r(json_encode($response));
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
