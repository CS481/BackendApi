<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
require_once('SimulationFactoryBackend/src/controller/StateController.php');
SimulationFactoryBackend\util\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\db\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);
try {
  $conn->beginTransaction();
  $query = $conn->or((object)['player1' => $data->user->username], (object)['player2' => $data->user->username]);
  $query-> simulation_id = $data->simulation_id;
  $sim_instance = $conn->selectOne('SimulationInstances', $query);

  if ($sim_instance->player1 == $data->user->username) {
    $cur_user = 'player1';
    $other_user = 'player2';
  } else {
    $cur_user = 'player2';
    $other_user = 'player1';
  }

  $response_count = (int)($sim_instance->player1_waiting) + (int)($sim_instance->player2_waiting);
  if ($response_count == 1) {
    SimulationFactoryBackend\controller\update_response_record($conn, $sim_instance, $data->response, $cur_user, $other_user);
  } else {
    SimulationFactoryBackend\controller\create_response_record($conn, $sim_instance, $data->response, $cur_user);
  }
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
