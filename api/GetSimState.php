<?php
require_once('SimulationFactoryBackend/src/db/MongoConn.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$conn = SimulationFactoryBackend\MongoConn::constructFromJson($data);

try {
  $conn->beginTransaction();
  $search_for = (object)['$or' => [['player1' => $data->user->username], ['player2' => $data->user->username]],
                         'simulation_id' => $data->simulation_id
                        ];
  $sim_instances = $conn->select('SimulationInstances', $search_for);
  // As far as I can tell, the mongodb cursor is broken, so this is the best way to get one
  // This may not set sim_instance, because race conditions
  foreach($sim_instances as $instance) {
    $sim_instance = $instance;
    break;
  }
  $response->user_waiting = true;

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
  $frames = $conn->select('Frames', $frame_search);
  // Cursor is broken, so we have to do this
  foreach($frames as $f) {
    $frame = $f;
  }

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
