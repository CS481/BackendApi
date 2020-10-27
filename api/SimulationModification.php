<?php
require_once('SimulationFactoryBackend/src/db/MongoConn.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$conn = SimulationFactoryBackend\MongoConn::constructFromJson($data);
try {
  $conn->beginTransaction();
  $search_for->username = $data->user->username;
  $search_for->_id = $data->simulation_id;
  $data->username = $data->user->username;
  unset($data->user);
  unset($data->simulation_id);
  $results = $conn->update('Simulations', $data, $search_for);
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>