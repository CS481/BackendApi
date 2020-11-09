<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/db/DBOpException.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
require_once('SimulationFactoryBackend/src/controller/StateController.php');
SimulationFactoryBackend\util\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\db\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);

try {
  $conn->beginTransaction();
  $search_for = $conn->or((object)['player1' => $data->user->username], (object)['player2' => $data->user->username]);
  $search_for->simulation_id = $data->simulation_id;
  $sim_instance = $conn->selectOne('SimulationInstances', $search_for);

  if (isset($sim_instance->player2) && time() > $sim_instance->deadline) {
    submit_default_responses($conn, $sim_instance);
  }

  $response = (object)['user_waiting' => true,
                       'response_deadline' => get_deadline($sim_instance)
                      ];

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

  try {
    $frame_search = (object)['simulation_id' => $data->simulation_id,
                             'rounds' => $sim_instance->turn_number
                            ];
    $frame = $conn->selectOne('Frames', $frame_search);
  } catch (SimulationFactoryBackend\db\DBOpException $_) {
    $sim_instance->turn_number = -1;
    $conn->update('SimulationInstances', $sim_instance, (object)['_id' => $sim_instance->_id]);
    $frame_search = (object)['simulation_id' => $data->simulation_id,
                             'rounds' => $sim_instance->turn_number
                            ];
    $frame = $conn->selectOne('Frames', $frame_search);
  }

  $response = (object)['user_waiting' => false,
                       'resources' => $sim_instance->resources,
                       'active_frame' => (object)['prompt' => $frame->prompt, 'responses' => $frame->responses],
                       'response_deadline' => get_deadline($sim_instance)
                      ];

  print_r(json_encode($response));
  $conn->submitTransaction();
} catch (Exception $e) {
  $conn->abortTransaction();
  throw $e;
}

function submit_default_responses($conn, $sim_instance) {
  $frame_search = (object)['simulation_id' => $sim_instance->simulation_id,
                           'rounds' => $sim_instance->turn_number
                          ];
  $frame = $conn->selectOne('Frames', $frame_search);

  $waiting_count = (int)($sim_instance->player1_waiting) + (int)($sim_instance->player2_waiting);
  if ($waiting_count == 2) {
    SimulationFactoryBackend\controller\create_response_record($conn, $sim_instance, $frame->default_action, 'player1');
    SimulationFactoryBackend\controller\update_response_record($conn, $sim_instance, $frame->default_action, 'player2', 'player1');
  } else if ($waiting_count == 1) {
    if ($sim_instance->player1_waiting) {
      SimulationFactoryBackend\controller\update_response_record($conn, $sim_instance, $frame->default_action, 'player2', 'player1');
    } else {
      SimulationFactoryBackend\controller\update_response_record($conn, $sim_instance, $frame->default_action, 'player1', 'player2');
    }
  }
}

function get_deadline($sim_instance) {
  return gmdate(DateTime::ISO8601, $sim_instance->deadline);
}
?>
