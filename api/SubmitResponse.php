<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
SimulationFactoryBackend\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);
try {
  $conn->beginTransaction();
  $sim_instance = $conn->selectOne('SimulationInstances', get_sim_instance_query($data));

  if ($sim_instance->player1 == $data->user->username) {
    $cur_user = 'player1';
    $other_user = 'player2';
  } else {
    $cur_user = 'player2';
    $other_user = 'player1';
  }

  $response_count = (int)($sim_instance->player1_waiting) + (int)($sim_instance->player2_waiting);
  if ($response_count == 1) {
    update_response_record($conn, $sim_instance, $data, $cur_user, $other_user);
  } else {
    create_response_record($conn, $sim_instance, $cur_user, $data);
  }
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}

function update_response_record($conn, $sim_instance, $post_data, $cur_user, $other_user) {
  $search_for = (object)['rounds' => $sim_instance->turn_number,
                         'simulation_id' => $sim_instance->simulation_id
                        ];
  $frame = $conn->selectOne('Frames', $search_for);

  $search_for = (object)[$other_user => $sim_instance->$other_user,
                         'simulation_id' => $sim_instance->simulation_id,
                         'round' => $sim_instance->turn_number
                        ];
  $log_entry = $conn->selectOne('ResponseRecords', $search_for);

  $log_update = (object)[$cur_user => $sim_instance->$cur_user,
                         $cur_user.'_response' => $post_data->response
                        ];

  foreach((array)($sim_instance->resources) as $resource => $value) {
    if ($cur_user == 'player1') {
      $player1_response_index = get_index($post_data->response, $frame->responses);
      $player2_response_key = $other_user.'_response';
      $player2_response_index = get_index($log_entry->$player2_response_key, $frame->responses);
    } else {
      $player2_response_index = get_index($post_data->response, $frame->responses);
      $player1_response_key = $other_user.'_response';
      $player1_response_index = get_index($log_entry->$player1_response_key, $frame->responses);
    }

    $sim_instance->resources->$resource += $value * $frame->effects->$resource[$player1_response_index][$player2_response_index];
    $log_update->resources->$resource = $sim_instance->resources->$resource;
  }
  $sim_instance->turn_number++;
  $sim_instance->player1_waiting = false;
  $sim_instance->player2_waiting = false;
  $sim_instance->deadline = time()+$sim_instance->response_timeout;

  $conn->update('ResponseRecords', $log_update, $search_for);
  $conn->update('SimulationInstances', $sim_instance, get_sim_instance_query($post_data));
}

function create_response_record($conn, $sim_instance, $cur_user, $post_data) {
  $insert = (object)[$cur_user => $sim_instance->$cur_user,
                     $cur_user.'_response' => $post_data->response,
                     'simulation_id' => $sim_instance->simulation_id,
                     'round' => $sim_instance->turn_number
                    ];
  $conn->insert('ResponseRecords', $insert);
  $user_waiting = $cur_user.'_waiting';
  $sim_instance->$user_waiting = true;
  $conn->update('SimulationInstances', $sim_instance, get_sim_instance_query($post_data));
}

function get_sim_instance_query($post_data) {
    return (object)['$or' => [['player1' => $post_data->user->username], ['player2' => $post_data->user->username]],
                    'simulation_id' => $post_data->simulation_id
                    ];
}

function get_index($value, $arr) {
  foreach($arr as $index => $arr_val) {
    if ($arr_val == $value) {
      return $index;
    }
  }
}
?>
