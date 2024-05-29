<?php
// --------------------------------------------------------------------------------------
// ---- get chapter                                     
// --------------------------------------------------------------------------------------
function b_txt_get_segment($id){
    global $con,$heb_num;

    $rep = array();

    // get text properties
    $prop = b_elmseq_get($id);

    // $sql = "SET SQL_BIG_SELECTS=1";
    // $result = mysqli_query($con,$sql);
    // if (!$result) {
    //     exit_error('Error 999 in text_func.php: ' . mysqli_error($con));
    // }

    $sql1 = "SELECT seq.part_id, seq.book_id, seq.chapter, seq.verse, seq.text
 			   FROM b_source_parts seq
		      WHERE seq.source_id = ".$prop['source_id']."
                AND seq.part_id BETWEEN ".$prop['from_part']." AND ".$prop['to_part']."
              ORDER BY seq.source_id, seq.part_id";       
    $result1 = mysqli_query($con,$sql1);
    if (!$result1) {
        exit_error('Error 5 in text_func.php: ' . mysqli_error($con));
    }

    $rep['part_list'] = array();
    while($row1 = mysqli_fetch_array($result1)) {
        $text = $row1['text'];
        $txt_list = verse_to_list($text,FALSE,0);
        $partObj = array(
            "part_id"=>$row1['part_id'],
            "book_id"=>$row1['book_id'],
            "chapter"=>$row1['chapter'],
            "verse"=>$row1['verse'],
            "verse_heb"=>$heb_num[$row1['verse']],
            "txt_list"=>$txt_list['list']
        );
        array_push($rep['part_list'],$partObj);
    }

    // $rep["title"] = txt_get_title($id);

    return $rep;
}

?>