<?php
$adId = $_POST['adId'];
$userId = $_POST['userId'];
$ownerId = $_POST['ownerId'];
$clientId = $_POST['clientId'];
$type = $_POST['type'];

$db= mysqli_connect("localhost","son","teamnova","tentuad");

$persona = getPersona($db, $userId);

switch ($type) {
    case "adClick":
        $insert = $db->query("
                    INSERT into UserAdClick
                    (userid,ps,adid, owner_idx, isClick, actiondate)
                    VALUES
                    ('{$userId}', '{$persona}','{$adId}', '{$ownerId}', 1,DATE_ADD(NOW(), INTERVAL 9 HOUR))
                    ");

        $update = $db->query("
                        UPDATE `adList` SET `cost`=`cost`+100, `click`=`click`+1 WHERE `idx`= '{$adId}'
                        ");
        break;
    case "adImpression":
        $insert = $db->query("
                    INSERT into UserAdClick
                    (userid, ps, adid, owner_idx, isClick, actiondate)
                    VALUES
                    ('{$userId}', '{$persona}','{$adId}', '{$ownerId}', 0,DATE_ADD(NOW(), INTERVAL 9 HOUR))
                    ");

        $update = $db->query("
                        UPDATE `adList` SET `imp`=`imp`+1 WHERE `idx`= '{$adId}'
                        ");

        break;
}

function getPersona($dbConnect, $userId){
    $personaResults = $dbConnect->query("
                    SELECT * FROM userPersona WHERE userId = '{$userId}'
                    ");
    $row = mysqli_fetch_array($personaResults);
    return $row['persona'];
}
?>
