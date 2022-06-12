<?php

require_once("DB2.php");

function getTimeslots()
{
    $array = [];

    $mysqli = databaseMYSQLi();
    if ($stmt = $mysqli->prepare("CALL GetTimeslots()")) {
        $stmt->execute();
        $stmt->bind_result($slotid, $time, $distance, $available);

        while ($stmt->fetch()) {
            $ar = [];
            $ar['slotid'] = $slotid;
            $ar['timeslot'] = $time;
            $ar['distance'] = $distance;
            $ar['available'] = $available;
            $array[] = $ar;
        }

        $mysqli->close();
    }

    return $array;
}

function getSlotData($slotid)
{
    $array = [];

    $mysqli = databaseMYSQLi();
    if ($stmt = $mysqli->prepare("CALL GetSlotData(?)")) {
        $stmt->bind_param("i", $slotid);
        $stmt->execute();
        $stmt->bind_result($time, $distance);

        while ($stmt->fetch()) {
            $ar = [];
            $ar['timeslot'] = $time;
            $ar['distance'] = $distance;
            $array[] = $ar;
        }

        $mysqli->close();
    }

    return $array[0];
}

function getAmountOfAvailableWalkers($slotid)
{
    $array = [];
    // Make sure it is always set to 0
    $array[0] = 0;

    $mysqli = databaseMYSQLi();
    if ($stmt = $mysqli->prepare("CALL GetAvailableWalkers(?)")) {
        $stmt->bind_param("i", $slotid);
        $stmt->execute();
        $stmt->bind_result($available);

        while ($stmt->fetch()) {
            $ar = [];
            $ar['available'] = $available;
            $array[] = $ar;
        }

        $mysqli->close();
    }

    // Expect only one result (return the last added result or if none were added, the default result)
    return $array[count($array) - 1]['available'];
}

function createParticipant($name, $mail)
{
    $participantId = -1;

    $mysqli = databaseMYSQLi();
    if ($stmt_ip = $mysqli->prepare("CALL InsertParticipant(?, ?)")) {
        $stmt_ip->bind_param("ss", $name, $mail);
        $stmt_ip->execute();
        //Fetch the next result set (as part of the MYSQL protocol of stored procedures)
        mysqli_next_result($mysqli);
        $stmt_ip->close();
    }

    if ($stmt = $mysqli->prepare("CALL GetParticipant(?, ?)")) {
        $stmt->bind_param("ss", $name, $mail);
        $stmt->execute();
        $stmt->bind_result($id);

        while ($stmt->fetch()) {
            $participantId = $id;
        }
        $stmt->close();
    }

    $mysqli->close();
    return $participantId;
}

function createRegistration($participantId, $slotid, $walkers)
{
    $mysqli = databaseMYSQLi();
    if ($stmt_iv = $mysqli->prepare("CALL InsertRegistration(?, ?, ?)")) {
        $stmt_iv->bind_param("iii", $participantId,  $slotid, $walkers);
        $stmt_iv->execute();
    }
    $mysqli->close();
}
