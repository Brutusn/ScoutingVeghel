<?php
require_once("./db_layer.php");

header('HTTP/1.1 200 OK');
echo json_encode(getTimeSlots());
exit;
