<?php
$adId = $_POST['adId'];
$userId = $_POST['userId'];

$db= mysqli_connect("localhost","son","teamnova","tentuad");

$data = getPopUpAdData( $userId, $adId, $db);
echo json_encode($data);



//광고판에 나왔던 광고의 정보를 바탕으로 팝업 광고 정보 추출하기
function getPopUpAdData ($userId , $adId, $dbConnect){
    //1. 광고판에 나온 광고 adId로부터 ad의 정보를 추출해서 등록하기
    $adIdResults = $dbConnect->query("
                    SELECT * FROM adList WHERE idx = '{$adId}'
                    ");
    $row = mysqli_fetch_array($adIdResults);

    $style = $row['f_style'];
    $persona = getPersona($dbConnect, $userId);
    $categoryBig = $row['ctgr_b'];
    $categoryMid = $row['ctgr_m'];

    //2. 페르소나, 패션스타일, 광고판카테고리 고려해서 랜덤으로 광고 하나 선택하기
    // 광고판에 있던 광고의 카테고리와 다른 카테고리 선택하기
    $data = getData($categoryBig, $categoryMid, $dbConnect, $style, $persona, "yes");
    // 만약 알맞은 광고가 없다면 페르소나 고려하지 않고 추천
    if($data === "noData"){
        $data = getData($categoryBig, $categoryMid, $dbConnect, $style, $persona, "no");
    }

    return $data;
}

//페르소나, 패션스타일, 광고판카테고리 고려해서 랜덤으로 광고 하나 선택해서 추출
function getData($categoryBig,$categoryMid, $dbConnect, $style, $persona, $considerPersona){
    $results = 1;
    switch($categoryBig){
        case "head" :
        case "man_shoes" :
        case "woman_shoes" :
            return;
        case "woman_clothes":
        case "man_clothes":
            switch($categoryMid){
                case "top":
                    if($considerPersona == "yes"){
                        $results = $dbConnect->query("
                    SELECT * FROM adList WHERE f_style = '{$style}' AND persona = '{$persona}' AND ctgr_b = '{$categoryBig}' AND (ctgr_m = 'outer' OR ctgr_m = 'bottom')
                    ");
                    }else{
                        $results = $dbConnect->query("
                    SELECT * FROM adList WHERE f_style = '{$style}' AND ctgr_b = '{$categoryBig}' AND (ctgr_m = 'outer' OR ctgr_m = 'bottom')
                    ");
                    }
                    break;
                case "bottom":
                    if($considerPersona == "yes"){
                        $results = $dbConnect->query("
                    SELECT * FROM adList WHERE f_style = '{$style}' AND persona = '{$persona}' AND ctgr_b = '{$categoryBig}' AND (ctgr_m = 'outer' OR ctgr_m = 'top')
                    ");
                    }else{
                        $results = $dbConnect->query("
                    SELECT * FROM adList WHERE f_style = '{$style}'AND ctgr_b = '{$categoryBig}' AND (ctgr_m = 'outer' OR ctgr_m = 'top')
                    ");
                    }
                    break;
                case "outer":
                    if($considerPersona == "yes"){
                        $results = $dbConnect->query("
                        SELECT * FROM adList WHERE f_style = '{$style}' AND persona = '{$persona}' AND ctgr_b = '{$categoryBig}' AND (ctgr_m = 'bottom' OR ctgr_m = 'top')
                        ");
                    }else{
                        $results = $dbConnect->query("
                        SELECT * FROM adList WHERE f_style = '{$style}' AND ctgr_b = '{$categoryBig}' AND (ctgr_m = 'bottom' OR ctgr_m = 'top')
                        ");
                    }
                    break;
            }
            break;
    }

    //추출한 광고들 배열에 담아서 랜덤으로 하나를 선택한다.
    $randVal = 0;
    if(mysqli_num_rows($results) != 0){
        //광고들 배열에 담기
        $resultsArr = array();
        $i = 0;
        while($row = mysqli_fetch_array($results)){
            $resultsArr[$i] = $row;
            $i = $i + 1;
        }
        //랜덤 값 추출 후 랜덤광고 선택
        $randVal = rand(0, mysqli_num_rows($results) - 1);

        $row = $resultsArr[$randVal];

        //추출할 데이터에 담기
        $data['adId'] = $row['idx'];
        $data['productUrl'] = $row['adurl'];
        $data['ownerId'] = $row['owner_idx'];
        if($row['img11'] !== "none"){
            $data['imageRatio'] = "img11";
            $data['adSourceUrl'] = $row['img11'];
        }else if($row['img43'] !== "none"){
            $data['imageRatio'] = "img43";
            $data['adSourceUrl'] = $row['img43'];
        }else{
            $data['imageRatio'] = "img34";
            $data['adSourceUrl'] = $row['img34'];
        }
        return $data;
    }else{
        return "noData";
    }
}

//광고판에 나온 광고 adId로부터 이 ad의 정보를 추출하기
function getPanelAdData($adId,$dbConnect){
    $adIdResults = $dbConnect->query("
                    SELECT * FROM adList WHERE idx = '{$adId}'
                    ");
    $row = mysqli_fetch_array($adIdResults);

    $adData['style'] = $row['f_style'];
    $adData['persona'] = $row['persona'];
    $adData['categoryBig'] = $row['ctgr_b'];
    $adData['categorySmall'] = $row['ctgr_s'];
    return $adData;
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
