<?php

include 'connect_db.php';
include 'res_func.php';

$type = $_POST['type'];
$id = json_decode($_POST['id'],true);
$oper = $_POST['oper'];
$prop =  json_decode($_POST['prop'],true);
$reload = array();

// exit_error($id['res']);

$file = file_get_contents($_FILES['file']['tmp_name']);
$file = str_replace(array('"','('),'',$file);
// $file = preg_replace('/\s+|\"|\(/','',$file);

$reply = array();

switch ($type){
    case "research":
        switch ($oper) {
            // // set research attributes
            case "upload_parts":
                res_DICTA_upload($id,$file);
                break;
        }
        break;    
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_DICTA_upload($id,$file){
    global $con;

    $colObj = res_new_collection($id,array("name"=>"מקובץ"));

    $part_id = 0;
    $fileArr = explode('תנך/',$file);
    for ($file_i=0;$file_i<count($fileArr);$file_i++){
        $lineArr = preg_split("/\n|\/|\)|\,/", $fileArr[$file_i]);
        if (count($lineArr) >= 4){
            $bibleRange = array("from"=>0,"to"=>999999999);

            //division
            $division_heb = array_shift($lineArr);

            //book
            $book_heb = str_replace('ספר ','',array_shift($lineArr));
            $bookRange = res_DICTA_get_range($book_heb,2,$bibleRange);

            //chapter
            $chapter_heb = str_replace('פרק ','',array_shift($lineArr));
            $chapterRange = res_DICTA_get_range($chapter_heb,1,$bookRange);
            // $chapter = array_search($chapter_heb,$heb_num);

            //verses
            while(count($lineArr)>0){
                $nxt = array_shift($lineArr);
                if (str_contains($nxt, 'פסוק ')){
                    $verse_heb = str_replace('פסוק ','',$nxt);
                    if ($verse_heb != ''){
                        // $verse = array_search($verse_heb,$heb_num);
                        $verseRange = res_DICTA_get_range($verse_heb,0,$chapterRange);
                        // $text = array_shift($lineArr);
                        // add_verse($book,$chapter,$verse,$text,$part_id);

                        if(is_null($verseRange)){
                            exit_error($verse_heb);
                        }

                        res_new_part($id,array(
                            "collection_id"=>$colObj['id'],
                            "src_research"=>1,
                            "src_collection"=>1,
                            "src_from_position"=>$verseRange['from'],
                            "src_from_word"=>0,
                            "src_to_position"=>$verseRange['to'],
                            "src_to_word"=>999
                        ));
                    }
                }
            }
        }
    }
}

function res_DICTA_get_range($name,$level,$posRange){
    global $con;
    $sql = "SELECT division_id,from_position,to_position
            FROM a_res_idx_division
            WHERE research_id = 1
                AND collection_id = 1
                AND index_id = 1
                AND level = ".$level."
                AND from_position >= ".$posRange['from']."
                AND to_position <= ".$posRange['to']."
                AND name_heb = '".$name."'";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error description2: ' . mysqli_error($con));
    }
    if($row = mysqli_fetch_array($result)){
        return array(
            "from"=>$row['from_position'],
            "to"=>$row['to_position']
        );
    }

    return null;
}

// function add_verse($book,$chapter,$verse,$text,$part_id){
//     global $con,$heb_num;

//     $reduced_text = trim(preg_replace("/\s+/", " ",plain_text($text)));
//     $text_arr = explode('*',$reduced_text);
//     $to = 1;
//     while (count($text_arr)>=2){
//         $from = $to + substr_count(array_shift($text_arr),' ');
//         $to = $from + substr_count(array_shift($text_arr),' ');
//         $part_id++;

// //        echo $part_id.'-'.$book.'-'.$chapter.'-'.$verse.'-'.$from.'-'.$to.'----------';

//         $sql = "INSERT INTO research_points
//                 (research_id, 
//                 category_id, 
//                 point_id, 
//                 vrskey, 
//                 from_word, to_word, 
//                 description) 
//                 VALUES (".$_POST['res'].", 
//                 ".$_POST['cat'].",
//                 ".$part_id.",
//                 CONCAT(LPAD('".$book."',2,'0'),'_',LPAD('".$chapter."',3,'0'),'_',LPAD('".$verse."',3,'0')),
//                 ".$from.",".$to.", 
//                 '')";
//         $result = mysqli_query($con,$sql);
//         if (!$result) {
//             if (strtok(mysqli_error($con), " ") != 'Duplicate'){
//                 exit_error('Error description3: ' . mysqli_error($con));
//             }
//         }
//     }
// }


echo json_encode($reply);
include 'disconnect_db.php';
?>