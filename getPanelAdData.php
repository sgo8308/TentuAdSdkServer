<?php
$userId = $_POST['userId'];
$adPanelShape = $_POST['adPanelShape'];
$clientId = $_POST['clientId'];

$db= mysqli_connect("localhost","son","teamnova","tentuad");

$adData = getAdData($clientId, $userId, $adPanelShape, $db);
echo json_encode($adData);

function getAdData ($clientId , $userId, $adPanelShape, $dbConnect){ // producturl 하드코딩 해놓음
    //1. DB에서 이 게임의 유저 ID의 카테고리를 선호도 순서대로 가져와서 배열안에 넣는다.
    $prefOrderArr = getPrefOrderArr($dbConnect, $userId);

    //2. 이 유저의 페르소나를 구한 후 이 유저의 페르소나의 광고 모음을 가져와서 배열안에 넣는다.
    $userPersona = getPersona($dbConnect, $userId);

    //3. 유저의 페르소나 광고 모음에 속한 광고들 불러온 후에 선호도 1등 카테고리에 속한 광고 모음으로 분류하고 없으면 그 다음 카테고리로 분류하기
    $adGroup = getAdGroupByPref($dbConnect, $userPersona, $prefOrderArr, $adPanelShape);

    //4. 광고 모음에서 랜덤으로 골라서 adId 가져오기
    $targetAdId = getAdId($adGroup);

    //5. adId에 맞는 광고데이터 가져오기
    $adIdResults = $dbConnect->query("
                    SELECT * FROM adList WHERE idx = '{$targetAdId}'
                    ");
    $row = mysqli_fetch_array($adIdResults);
    $data['adId'] = $row['idx'];
    $data['adSourceUrl'] = $row[$adPanelShape];
    $data['productUrl'] = $row['adurl'];
    $data['ownerId'] = $row['owner_idx'];
    return $data;
}

function getPrefOrderArr($dbConnect, $userId){
    //카테고리 이름 갖고와서 배열 안에 넣어주기
    $colName = $dbConnect->query("
                    SELECT  COLUMN_NAME
                    FROM    INFORMATION_SCHEMA.COLUMNS
                    WHERE   TABLE_NAME = 'UserPreference';
                    ");

    $colNameArr = array();

    $i = 0;
    while($row1 = mysqli_fetch_array($colName)){
        $colNameArr[$i] = $row1[0];
        $i = $i + 1;
    };

    array_shift($colNameArr);//카테고리 이름 중 앞에 userid 제거

    //카테고리 선호도 순서대로 카테고리 이름 정렬하기
    $categoryVal = $dbConnect->query("
                    SELECT * FROM UserPreference WHERE userid = '{$userId}'
                    ");

    $categoryArr = array();

    $row2 = mysqli_fetch_array($categoryVal);

    foreach($colNameArr as $colName ){
        $categoryArr[$colName] = $row2[$colName];
    }

    arsort($categoryArr);

    $resultArr = array_keys($categoryArr);

    return $resultArr;
}

function getPersona($dbConnect, $userId){
    $personaResults = $dbConnect->query("
                    SELECT * FROM userPersona WHERE userId = '{$userId}'
                    ");
    $row = mysqli_fetch_array($personaResults);
    return $row['persona'];
}

function getAdGroup($dbConnect, $persona, $category, $adPanelShape){
    $adGroupResults = $dbConnect->query("
                    SELECT * FROM adList WHERE (persona = '{$persona}' OR persona = 'default') and ctgr_s = '{$category}' and {$adPanelShape} != 'none'
                    ");
    $adGroup = array();

    $i = 0;
    while($row = mysqli_fetch_array($adGroupResults)){
        $adGroup[$i] = $row['idx'];
        $i = $i + 1;
    }

    return $adGroup;
}

function getAdGroupByPref($dbConnect, $persona, $categoryArr, $adPanelShape){
    $adGroup = array();
    foreach($categoryArr as $cg){
        $adGroup = getAdGroup($dbConnect, $persona, $cg, $adPanelShape);
        if(!empty($adGroup)){
            break;
        }
    }

    return $adGroup;
}

function getAdId($adGroup){
    if(count($adGroup) != 0){
        $randVal = rand(0, count($adGroup) - 1);
        return $adGroup[$randVal];
    }else{
        return "noId";
    }
}
?>
