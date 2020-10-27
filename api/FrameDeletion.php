  GNU nano 4.8                                                                                               FrameInitialization.php                                                                                                          
<?php
require_once('SimulationFactoryBackend/src/db/MongoConn.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$conn = SimulationFactoryBackend\MongoConn::constructFromJson($data);
try {
  $conn->beginTransaction();
  $frame_data->username = $data->user->username;
  $frame_data->_id = $data->frame_id;
  $conn->delete('Frames', $sim_data);
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>


