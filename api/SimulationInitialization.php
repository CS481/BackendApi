<?php
require_once('SimulationFactoryBackend/src/db/MongoConn.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$conn = SimulationFactoryBackend\MongoConn::constructFromJson($data);
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
