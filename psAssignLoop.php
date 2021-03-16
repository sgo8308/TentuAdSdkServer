<?php
$db= mysqli_connect("localhost","son","teamnova","tentuad");

$results = $db->query("
                    SELECT * FROM adList WHERE persona = 'default'
                    ");

//DB에서 페르소나 아직 default인 애들 갖고 와서 광고 처음 시작일로부터 일주일 지났는지 확인 후 일주일 넘었으면 클릭률 가장 높은 페르소나로 업데이트
while($row = mysqli_fetch_array($results)){
    $period_s = substr($row['period_s'],0,10);
    $period_e = date("Y-m-d");

    $firstDate  = new DateTime($period_s);
    $secondDate = new DateTime($period_e);
    $inteval = $firstDate->diff($secondDate);

    $intervalDay = $inteval->d;

    $idx = $row['idx'];
    $personaArr = ['inssa', 'assa', 'lonelyGuy'];
    if($intervalDay > 6){
        //1.클릭률 1등의 페르소나를 가져온다.
        $persona = getFirstPersona($db,$row['idx']);
        echo $persona;
        //2.adList의 페르소나를 이 페르소나로 업데이트한다.
        if($persona != 'noPersona'){
            $update = $db->query("
                             UPDATE adList SET `persona` = '$persona' WHERE (`idx` = '$idx');
                        ");
        }
    }
}

//어떤 페르소나의 클릭률이 제일 높은지 구해주는 메소드
function getFirstPersona($db, $adId){
    $personaArr = ['inssa', 'assa', 'lonelyGuy'];  //페르소나 바뀌면 여기 페르소나 배열에 추가해줘야 함
    $clickRateArr = array();

    foreach($personaArr as $persona){
        $impResults = $db->query("
                    SELECT * FROM UserAdClick WHERE adId = '$adId' and isClick = 0 and ps = '$persona'
                    ");

        $clickResults = $db->query("
                    SELECT * FROM UserAdClick WHERE adId = '$adId' and isClick = 1 and ps = '$persona'
                    ");

        if(mysqli_num_rows($impResults) != 0){
            $clickRate = (mysqli_num_rows($clickResults))/mysqli_num_rows($impResults);
        }else{
            $clickRate = 0;
        }

        if($clickRate != 0){
            $clickRateArr[$persona] = $clickRate;
        }
    }

    arsort($clickRateArr);
    $personaOrderArr = array_keys($clickRateArr);
    var_dump($personaOrderArr);
    if(count($clickRateArr) == 0){
        return 'noPersona';
    }
    return $personaOrderArr[0];
}
?>