<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
SimulationFactoryBackend\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);
try {
  $conn->beginTransaction();
  $search_for->username = $data->user->username;
  $search_for->_id = $data->frame_id;
  $data->username = $data->user->username;
  unset($data->user);
  unset($data->frame_id);
  $results = $conn->update('Frames', $data, $search_for);
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
