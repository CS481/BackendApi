<?php
require_once('SimulationFactoryBackend/src/db/DBConnFactory.php');
require_once('SimulationFactoryBackend/src/util/check_method.php');
SimulationFactoryBackend\only_allow_method('POST');
$db_conn_class = SimulationFactoryBackend\DBConnFactory();
$data = json_decode(file_get_contents('php://input'), false);
$db_conn_class::createUserFromJson($data);
?>




