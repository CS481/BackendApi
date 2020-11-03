<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
SimulationFactoryBackend\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);

try {
  $conn->beginTransaction();
  $search_for = $conn->or((object)['player1' => $data->user->username], (object)['player2' => $data->user->username]);
  $search_for->simulation_id = $data->simulation_id;
  $sim_instance = $conn->selectOne('SimulationInstances', $search_for);
  $response->user_waiting = true;

  // $sim_instance may not be set, because race conditions
  if (!isset($sim_instance) || ($sim_instance->player1_waiting == true && $sim_instance->player2_waiting == true)) {
    print_r(json_encode($response));
    exit;
  }

  if ($sim_instance->player1 == $data->user->username) {
    $user = 'player1';
  } else {
    $user = 'player2';
  }
  $user_waiting_key = $user.'_waiting';
  if ($sim_instance->$user_waiting_key) {
    print_r(json_encode($response));
    exit;
  }

  $frame_search = (object)['simulation_id' => $data->simulation_id,
                           'rounds' => $sim_instance->turn_number
                          ];
  $frame = $conn->selectOne('Frames', $frame_search);

  $response = (object)['user_waiting' => false,
                       'resources' => $sim_instance->resources,
                       'active_frame' => (object)['prompt' => $frame->prompt, 'responses' => $frame->responses]
                      ];
  print_r(json_encode($response));
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
