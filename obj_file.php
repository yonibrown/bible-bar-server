<?php

include 'connect_db.php';
include 'res_func.php';
include 'link_func.php';

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
                $reply['data'] = res_DICTA_upload($id,$file);
                break;
        }
        break;    
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_DICTA_upload($id,$file){
    global $con;

    $residx_id = array(
        "res"=>1,
        "col"=>1,
        "idx"=>1
    );
    $colObj = res_new_collection($id,array("name"=>"מקובץ"));

    $part_id = 0;
    // $fileArr = explode('תנך/',$file);
    $fileArr = preg_split("/\n/", $file);
    for ($file_i=0;$file_i<count($fileArr);$file_i++){
        // $lineArr = preg_split("/\n|\/|\)|\,/", $fileArr[$file_i]);
        $lineArr = preg_split("/\/|\,/", $fileArr[$file_i]);
        if (count($lineArr) >= 4){
            $bibleRange = array("from"=>0,"to"=>999999999);

            //source
            $tanah = array_shift($lineArr);

            //division
            $division_heb = array_shift($lineArr);

            //book
            $book_heb = str_replace('ספר ','',array_shift($lineArr));
            $bookRange = residx_get_level_range($residx_id,$book_heb,2,$bibleRange);

            //chapter
            $chapter_heb = str_replace('פרק ','',array_shift($lineArr));
            $chapterRange = residx_get_level_range($residx_id,$chapter_heb,1,$bookRange);
            // $chapter = array_search($chapter_heb,$heb_num);

            //verses
            while(count($lineArr)>0){
                $nxt = array_shift($lineArr);
                if (str_contains($nxt, 'פסוק ')){
                    $verse_heb = str_replace('פסוק ','',$nxt);
                    if ($verse_heb != ''){
                        // $verse = array_search($verse_heb,$heb_num);
                        $verseRange = residx_get_level_range($residx_id,$verse_heb,0,$chapterRange);
                        $text = array_shift($lineArr);
                        $text = str_replace('־',' ',$text);
                        $text = str_replace('׀',' ',$text);
                        $text = str_replace('* *',' ',$text);
                        $textArr = explode('*',$text);
        
                        $toWord = 0;
                        while(count($textArr)>0){
                            $wordsBefore = preg_split("/\s+/", array_shift($textArr));
                            // $wordsBefore = explode(' ',array_shift($textArr));
                            // exit_error(count($wordsBefore));
                            $fromWord = $toWord + count($wordsBefore) - 1;
    
                            if (count($textArr)>0){
                                $wordsPart = explode(' ',array_shift($textArr));
                                $toWord = $fromWord + count($wordsPart) - 1;
        
                                // add_verse($book,$chapter,$verse,$text,$part_id);
        
                                res_new_part($id,array(
                                    "collection_id"=>$colObj['id'],
                                    "src_research"=>1,
                                    "src_collection"=>1,
                                    "src_from_position"=>$verseRange['from'],
                                    "src_from_word"=>$fromWord,
                                    "src_to_position"=>$verseRange['to'],
                                    "src_to_word"=>$toWord
                                ));
                            }
                        }
                    }
                }
            }
        }
    }

    $newParts = res_parts_prop($id,array(
        "collection_id"=>$colObj['id']
    ));

    return array(
        "new_collection"=>$colObj,
        "new_parts"=>$newParts
    );
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