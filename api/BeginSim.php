<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/db/DBOpException.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
require_once('simulation-schema/php/SimModValidator.php');
SimulationFactoryBackend\util\only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class = SimulationFactoryBackend\db\DBConnFactory();
$conn = $db_conn_class::constructFromJson($data);
$sim_instance_collection = 'SimulationInstances';
try {
  $conn->beginTransaction();
  // Easier to ask forgiveness than permission
  try {
    $query = (object)['player2' => $conn->not_set(),
                      'simulation_id' => $data->simulation_id
                     ];
    $update_data = (object)['player2' => $data->user->username,
                            'player1_waiting' => false,
                            'player2_waiting' => false,
                            'deadline' => time()+$simulation->response_timeout
                           ];
    $conn->update($sim_instance_collection, $update_data, $query);
  } catch (SimulationFactoryBackend\db\DBOpException $e) {
    $simulation = $conn->selectOne('Simulations', (object)['_id' => $data->simulation_id]);
    $insert_data = (object)['player1' => $data->user->username,
                            'player1_waiting' => true,
                            'player2_waiting' => true,
                            'simulation_id' => $data->simulation_id,
                            'turn_number' => 0,
                            'resources' => $simulation->resources,
                            'response_timeout' => $simulation->response_timeout
                           ];
   $conn->insert($sim_instance_collection, $insert_data);
  }
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
