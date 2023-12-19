<?php
include 'connect_db.php';

exit_error($_POST['oper']);

$sql = "DELETE FROM research_points
        WHERE research_id =  ".$_POST['res']."
          AND category_id =  ".$_POST['cat'];
$result = mysqli_query($con,$sql);
if (!$result) {
    exit_error('Error description1: ' . mysqli_error($con));
}


$file = file_get_contents($_FILES['pts']['tmp_name']);

$file = str_replace(array('"','('),'',$file);
// $file = preg_replace('/\s+|\"|\(/','',$file);

$pt_id = 0;

$fileArr = explode('תנך/',$file);
for ($file_i=0;$file_i<count($fileArr);$file_i++){
    $lineArr = preg_split("/\n|\/|\)|\,/", $fileArr[$file_i]);
    if (count($lineArr) >= 4){
        //division
        $division_heb = array_shift($lineArr);

        //book
        $book_heb = str_replace('ספר ','',array_shift($lineArr));
        $book = get_book($book_heb);

        //chapter
        $chapter_heb = str_replace('פרק ','',array_shift($lineArr));
        $chapter = array_search($chapter_heb,$heb_num);

        //verses
        while(count($lineArr)>0){
            $verse_heb = str_replace('פסוק ','',array_shift($lineArr));
            if ($verse_heb != ''){
                $verse = array_search($verse_heb,$heb_num);
                $text = array_shift($lineArr);
                add_verse();
            }
        }
    }
}

function get_book($book_heb){
    global $con;
    $sql = "SELECT book_id
            FROM bible_index
            WHERE hebrew_name = '".$book_heb."'";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error description2: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    return $row['book_id'];
}

function add_verse(){
    global $book,$chapter,$verse,$text,$heb_num,$con,$pt_id;

    $reduced_text = trim(preg_replace("/\s+/", " ",plain_text($text)));
    $text_arr = explode('*',$reduced_text);
    $to = 1;
    while (count($text_arr)>=2){
        $from = $to + substr_count(array_shift($text_arr),' ');
        $to = $from + substr_count(array_shift($text_arr),' ');
        $pt_id++;

//        echo $pt_id.'-'.$book.'-'.$chapter.'-'.$verse.'-'.$from.'-'.$to.'----------';

        $sql = "INSERT INTO research_points
                (research_id, 
                category_id, 
                point_id, 
                vrskey, 
                from_word, to_word, 
                description) 
                VALUES (".$_POST['res'].", 
                ".$_POST['cat'].",
                ".$pt_id.",
                CONCAT(LPAD('".$book."',2,'0'),'_',LPAD('".$chapter."',3,'0'),'_',LPAD('".$verse."',3,'0')),
                ".$from.",".$to.", 
                '')";
        $result = mysqli_query($con,$sql);
        if (!$result) {
            if (strtok(mysqli_error($con), " ") != 'Duplicate'){
                exit_error('Error description3: ' . mysqli_error($con));
            }
        }
    }
}

include 'disconnect_db.php';
?>