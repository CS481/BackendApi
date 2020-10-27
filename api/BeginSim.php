<?php
require_once('SimulationFactoryBackend/src/db/MongoConn.php');
require_once('SimulationFactoryBackend/src/db/DBOpException.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
only_allow_method('POST');
$data = json_decode(file_get_contents('php://input'), false);
$conn = SimulationFactoryBackend\MongoConn::constructFromJson($data);
$sim_instance_collection = 'SimulationInstances';
try {
  $conn->beginTransaction();
  // Easier to ask forgiveness than permission
  try {
    $query = (object)['player2' => ['$exists' => false],
                      'simulation_id' => $data->simulation_id
                     ];
    $update_data = (object)['player2' => $data->user->username,
                            'player1_waiting' => false,
                            'player2_waiting' => false
                           ];
    $conn->update($sim_instance_collection, $update_data, $query);
  } catch (SimulationFactoryBackend\DBOpException $e) {
    $simulations = $conn->select('Simulations', (object)['_id' => $data->simulation_id]);
    foreach($simulations as $sim) { //Mongodb cursor is broken, so we have to do it this way
      $simulation = $sim;
      break;
    }
    $insert_data = (object)['player1' => $data->user->username,
                            'player1_waiting' => true,
                            'player2_waiting' => true,
                            'simulation_id' => $data->simulation_id,
                            'turn_number' => 0,
                            'resources' => $simulation->resources
                           ];
   $conn->insert($sim_instance_collection, $insert_data);
  }
  $conn->submitTransaction();
} catch (Exception $e)  {
  $conn->abortTransaction();
  throw $e;
}
?>
