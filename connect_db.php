<?php

header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');

$heb_num = array("0","א","ב","ג","ד","ה","ו","ז","ח","ט","י",
               "יא","יב","יג","יד","טו","טז","יז","יח","יט","כ",
               "כא","כב","כג","כד","כה","כו","כז","כח","כט","ל",
               "לא","לב","לג","לד","לה","לו","לז","לח","לט","מ",
               "מא","מב","מג","מד","מה","מו","מז","מח","מט","נ",
               "נא","נב","נג","נד","נה","נו","נז","נח","נט","ס",
               "סא","סב","סג","סד","סה","סו","סז","סח","סט","ע",
               "עא","עב","עג","עד","עה","עו","עז","עח","עט","פ",
               "פא","פב","פג","פד","פה","פו","פז","פח","פט","צ",
               "צא","צב","צג","צד","צה","צו","צז","צח","צט","ק",
               "קא","קב","קג","קד","קה","קו","קז","קח","קט","קי",
               "קיא","קיב","קיג","קיד","קטו","קטז","קיז","קיח","קיט","קכ",
               "קכא","קכב","קכג","קכד","קכה","קכו","קכז","קכח","קכט","קל",
               "קלא","קלב","קלג","קלד","קלה","קלו","קלז","קלח","קלט","קמ",
               "קמא","קמב","קמג","קמד","קמה","קמו","קמז","קמח","קמט","קנ",
               "קנא","קנב","קנג","קנד","קנה","קנו","קנז","קנח","קנט","קס",
               "קסא","קסב","קסג","קסד","קסה","קסו","קסז","קסח","קסט","קע",
               "קעא","קעב","קעג","קעד","קעה","קעו");

$reply = array();

// connect to infinityfree MySql server
// $host = "sql203.epizy.com";
// $uname = "epiz_34309968";
// $pwd = "sl9bnZLv7Fu0";
// $database = "epiz_34309968_bibar";

// connect to Hostinger MySql server
$host = "localhost";
$uname = "u825158041_bibar";
$pwd = "rtavabv465A@";
$database = "u825158041_bibar";

// connect to local MySql server
// $host = "localhost";
// $uname = "root";
// $pwd = "";
// $database = "bibar";

// connect to db
$con = mysqli_connect($host, $uname, $pwd, $database);

// Check connection
if (mysqli_connect_errno()){
    exit_error("Failed to connect to MySQL: " . mysqli_connect_error());
}

// Change character set to utf8
$result = mysqli_set_charset($con,"utf8");
if (!$result){
    exit_error('Error 1 in connect_db.php: '. mysqli_error($con));
}



function exit_error($txt){
    global $con;
    mysqli_rollback($con);
    die(json_encode(array('error'=> $txt)));
}

function sub_words($text,$from_word,$to_word){
    // if ($from_word == 0){
    //     return array("text"=>$text,"start"=>0,"end"=>mb_strlen($text));
    // }

    $plain = plain_text($text);
    // $from_word--;
    $words_count = $to_word - $from_word + 1;
    $start_pos = 0;
    for ($i = 0; $i < $from_word; $i++) {
        $start_pos = mb_strpos($plain," ",$start_pos)+1;
    } 
    $end_pos = $start_pos;
    for ($i = 0; $i < $words_count; $i++) {
        $end_pos = mb_strpos($plain," ",$end_pos+1);
    } 
    return array("text"=>mb_substr($text,$start_pos,$end_pos - $start_pos),
                "start"=>$start_pos,"end"=>$end_pos);
}


function plain_text($text){
    return strtr($text,array("׃" => " ", "־" => " ", " " => " "));
}

/*
function csv_to_array($csv){
    $arr = explode(',',$csv);
    for ($csv_index = 0;$csv_index<count($arr);$csv_index++){
        if (substr($arr[$csv_index],0,1)=='"' && substr($arr[$csv_index],-1)=='"'){
            $arr[$csv_index] = trim($arr[$csv_index],'"');
            $arr[$csv_index] = str_replace('""','"',$arr[$csv_index]);
        }
    }
    return $arr;
}
*/

function inList($arr,$itemType='num'){
    function wrapString($str){
        return "'".$str."'";
    }
    if (!is_null($itemType)){
        if ($itemType == 'string'){
            $arr = array_map("wrapString",$arr);
        } 
    }

    function reduceFunc($carry,$item)
    {
      if (!is_null($carry)){
          return $carry.",".$item;
      } else {
          return $item;
      }
    }
    return " IN(".array_reduce($arr,"reduceFunc").") ";
}
?>