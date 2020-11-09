<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
require_once('SimulationFactoryBackend/src/controller/FrameController.php');
SimulationFactoryBackend\util\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\db\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);
try {
  $conn->beginTransaction();
  SimulationFactoryBackend\controller\modify_frame($conn, $data->user, $data, $data->frame_id);
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
