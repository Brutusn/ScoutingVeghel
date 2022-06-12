<?php

require_once("DB2.php");

/**
 * 
 */
function getTimeslots()
{
    $array = [];

    $mysqli = databaseMYSQLi();
    if($stmt = $mysqli->prepare("CALL GetTimeslots()")){
        $stmt->execute();
        $stmt->bind_result($time, $distance, $available);

        while ($stmt->fetch()) {
            $ar = [];//array('dayFrom', 'dayTo', 'bySV');
            $ar['timeslot'] = $time;
            $ar['distance'] = $distance;
            $ar['isAvailable'] = $available;
            $array[] = $ar;
        }
        
        $mysqli->close();
    }

    return $array;
}
