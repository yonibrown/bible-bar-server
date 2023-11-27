<?php
// --------------------------------------------------------------------------------------
// ---- create new textbox
// --------------------------------------------------------------------------------------
function txt_create($id,$prop){
    global $con;
    $attr = txt_gen_attr($id,$prop);

    $sql = "INSERT INTO a_proj_elm_sequence
                (project_id, element_id, 
                 research_id, collection_id, from_position, to_position, 
                 seq_index, seq_level, color_level, 
                 anchor_position, anchor_word)
            VALUES(".$id['proj'].",".$id['elm'].", 
                   ".$attr['src_research'].",".$attr['src_collection'].",".$attr['from_position'].",".$attr['to_position'].",
                   1,0,0,
                   ".$attr['anchor_position'].",0)";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 2 in text_func.php: ' . mysqli_error($con));
    }

    // if ($attr['prt_research'] != $attr['src_research']){
    //     // create link between prt_research and text element
    //     elm_link_to_cat($id,array("res"=>$attr['prt_research'],"col"=>$attr['prt_collection']));
    // }
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
// function txt_set($id,$prop){
//     global $con;

//     $attr = txt_gen_attr($id,$prop);

//     $sql = "UPDATE a_proj_elm_sequence
//                SET research_id = ".$attr['src_research'].", 
//                    collection_id = ".$attr['src_collection'].", 
//                    from_position = ".$attr['from_position'].", 
//                    to_position = ".$attr['to_position'].", 
//                    seq_index = 1, 
//                    seq_level = 0, 
//                    color_level = 0, 
//                    anchor_position = ".$attr['anchor_position'].", 
//                    anchor_word = 0
//              WHERE project_id = ".$id['proj']."
//                AND element_id = ".$id['elm'];
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 12 in text_func.php: ' . mysqli_error($con));
//     }
// }

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function txt_gen_attr($id,$prop){
    global $con;

    if (!array_key_exists('research_id',$prop)){
        $prop['research_id'] = 1;
        $prop['part_id'] = 1;
    }

    if (array_key_exists('division_id',$prop)){
        // get parameters by division_id
        $sql = "SELECT src.research_id src_research,src.collection_id src_collection,
                       src.from_position,src.to_position,
                       src.research_id prt_research,
                       IFNULL(anc.from_position,0) anchor_position
                  FROM a_res_idx_division src
                  LEFT JOIN a_res_idx_division anc
                    ON anc.research_id = src.research_id
                   AND anc.collection_id = src.collection_id
                   AND anc.division_id = ".$prop['anchor_div']."
                   AND anc.from_position BETWEEN src.from_position AND src.to_position
                 WHERE src.research_id = ".$prop['research_id']."
                   AND src.collection_id = ".$prop['collection_id']."
                   AND src.division_id = ".$prop['division_id'];
    } else {
        // get parameters by part_id
        $sql = "SELECT prt.src_research,prt.src_collection,
                       src.from_position,src.to_position,
                       prt.research_id prt_research,prt.collection_id prt_collection,
                       prt.src_from_position anchor_position
                  FROM a_res_parts prt
                  JOIN a_res_idx_division src
                    ON src.research_id = prt.src_research
                   AND src.collection_id = prt.src_collection
                   AND prt.src_from_position BETWEEN src.from_position AND src.to_position
                   AND src.index_id = 1
                   AND src.level = 1
                 WHERE prt.research_id = ".$prop['research_id']."
                   AND prt.part_id = ".$prop['part_id'];
    }
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 1 in text_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    return $row;
}

// --------------------------------------------------------------------------------------
// ---- get chapter                                     
// --------------------------------------------------------------------------------------
function txt_get_segment($id){
    global $con,$heb_num;

    $rep = array();

    // get text properties
    $prop = elmseq_get($id);

    $sql = "SET SQL_BIG_SELECTS=1";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 999 in text_func.php: ' . mysqli_error($con));
    }

    $sql1 = "SELECT seq.part_id seq_part,seq.div_name_heb name,seq.position seq_position,
                    src.position src_position,src.text,
 				    MAX(pts.linked) linked
 			  FROM a_res_parts seq
              JOIN a_res_parts src
                ON src.research_id = seq.src_research
               AND src.collection_id = seq.src_collection
               AND src.position BETWEEN seq.src_from_position AND seq.src_to_position
              LEFT JOIN 
                  (SELECT 1 linked,src_from_position,src_to_position
                     FROM a_res_parts rp
                     JOIN view_proj_link_elm_col ec
                       ON ec.research_id = rp.research_id
                      AND ec.collection_id = rp.collection_id
                      AND ec.project_id = ".$id['proj']."
                      AND ec.element_id = ".$id['elm']."
                    WHERE rp.src_research = ".$prop['research_id']."
                      AND rp.src_collection = ".$prop['collection_id'].") AS pts
				ON src.position BETWEEN pts.src_from_position AND pts.src_to_position
		     WHERE seq.research_id = ".$prop['research_id']."
               AND seq.collection_id = ".$prop['collection_id']."
               AND seq.position BETWEEN ".$prop['from_position']." AND ".$prop['to_position']."
             GROUP BY seq.position,src.position,
                      seq.part_id,seq.div_name_heb,src.text
             ORDER BY seq.position,src.position";       
    $result1 = mysqli_query($con,$sql1);
    if (!$result1) {
        exit_error('Error 5 in text_func.php: ' . mysqli_error($con));
    }

    $rep['part_list'] = array();
    while($row1 = mysqli_fetch_array($result1)) {
        $text = $row1['text'];
        $src_pos = $row1['src_position'];

        $anchor_part = ($row1['seq_position'] == $prop['anchor_position']);

        $plain = plain_text($text);
        $next_offset = -1;
        $word_no = 0;
        $txt_list = array();
        while ($next_offset < mb_strlen($plain)-1){
            $start_offset = $next_offset + 1;
            $next_offset = mb_strpos($plain," ",$start_offset);
            if (!$next_offset){
                $next_offset = mb_strlen($plain); 
            }
            $word_length = $next_offset - $start_offset;
            $text_word = mb_substr($text,$start_offset,$word_length);
            $text_space = mb_substr($text,$next_offset,1);

            $word_linked = false;
            $space_linked = false;
            if ($row1['linked'] == 1){
                $sql2 = "SELECT ec.research_id, ec.collection_id, ec.division_id, rp.part_id, rp.src_to_position, rp.src_to_word, ec.link_id
                           FROM a_res_parts rp
                           JOIN view_proj_link_elm_col ec
                             ON ec.research_id = rp.research_id
                            AND ec.collection_id = rp.collection_id
                           LEFT JOIN a_res_idx_division rd
                             ON ec.research_id = rd.research_id
                            AND ec.collection_id = rd.collection_id 
                            AND ec.division_id = rd.division_id
                          WHERE (".$src_pos." > rp.src_from_position OR (".$src_pos." = rp.src_from_position AND ".$word_no." >= rp.src_from_word)) 
                            AND (".$src_pos." < rp.src_to_position   OR (".$src_pos." = rp.src_to_position   AND ".$word_no." <= rp.src_to_word))
                            AND ec.project_id = ".$id['proj']."
                            AND ec.element_id = ".$id['elm']."
                            AND (ec.division_id = 0 OR rp.position BETWEEN rd.from_position AND rd.to_position)
                          ORDER BY rp.research_id,rp.collection_id,ec.link_id";

                $result2 = mysqli_query($con,$sql2);
                if (!$result2) {
                    exit_error('Error 6 in text_func.php: ' . mysqli_error($con));
                }
                if($row2 = mysqli_fetch_array($result2)){
                    $word_linked = true;
                    if ($src_pos < $row2['src_to_position'] || $word_no < $row2['src_to_word']){
                        $space_linked = true;
                    }
                }
            }

            $wordObj = array(
                "id"=>$word_no,
                "word"=>$text_word,
                "space"=>$text_space,
                "word_linked"=>$word_linked,
                "space_linked"=>$space_linked
            );
            if ($word_linked){
                $wordObj['link'] = (int)$row2['link_id'];
                $wordObj['res'] = $row2['research_id'];
                $wordObj['col'] = $row2['collection_id'];
                $wordObj['div'] = $row2['division_id'];
                $wordObj['prt'] = $row2['part_id'];
            }

            if ($anchor_part && $word_no == $prop['anchor_word']){
                $anchor_part = false;
                $wordObj['anchor'] = true;
            }

            array_push($txt_list,$wordObj);
            $word_no++;
        }

        $partObj = array(
            "part_id"=>$row1['seq_part'],
            "position"=>$row1['seq_position'],
            "part_name"=>$row1['name'],
            "txt_list"=>$txt_list
        );

        if ($anchor_part){
            $partObj['anchor'] = true;
        }

        array_push($rep['part_list'],$partObj);
    }

    $rep["title"] = txt_get_title($id);

    return $rep;
}

// function txt_get_segment_new($id){
//     global $con,$heb_num;

//     $rep = array();

//     // get text properties
//     $prop = elmseq_get($id);

//     $sql = "SET SQL_BIG_SELECTS=1";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 999 in text_func.php: ' . mysqli_error($con));
//     }

//     $sql1 = "SELECT seq.part_id seq_part,seq.div_name_heb name,seq.position seq_position,
//                     src.position src_position,src.text,
//  				    MAX(pts.linked) linked
//  			  FROM a_res_parts seq
//               JOIN a_res_parts src
//                 ON src.research_id = seq.src_research
//                AND src.collection_id = seq.src_collection
//                AND src.position BETWEEN seq.src_from_position AND seq.src_to_position
//               LEFT JOIN 
//                   (SELECT 1 linked,src_from_position,src_to_position
//                      FROM a_res_parts rp
//                      JOIN view_proj_link_elm_col ec
//                        ON ec.research_id = rp.research_id
//                       AND ec.collection_id = rp.collection_id
//                       AND ec.project_id = ".$id['proj']."
//                       AND ec.element_id = ".$id['elm']."
//                     WHERE rp.src_research = ".$prop['research_id']."
//                       AND rp.src_collection = ".$prop['collection_id'].") AS pts
// 				ON src.position BETWEEN pts.src_from_position AND pts.src_to_position
// 		     WHERE seq.research_id = ".$prop['research_id']."
//                AND seq.collection_id = ".$prop['collection_id']."
//                AND seq.position BETWEEN ".$prop['from_position']." AND ".$prop['to_position']."
//              GROUP BY seq.position,src.position,
//                       seq.part_id,seq.div_name_heb,src.text
//              ORDER BY seq.position,src.position";       
//     $result1 = mysqli_query($con,$sql1);
//     if (!$result1) {
//         exit_error('Error 5 in text_func.php: ' . mysqli_error($con));
//     }

//     $rep['part_list'] = array();
//     while($row1 = mysqli_fetch_array($result1)) {
//         $text = $row1['text'];
//         $src_pos = $row1['src_position'];

//         $anchor_part = ($row1['seq_position'] == $prop['anchor_position']);

//         $plain = plain_text($text);
//         $next_offset = -1;
//         $word_no = 0;
//         $txt_list = array();
//         while ($next_offset < mb_strlen($plain)-1){
//             $start_offset = $next_offset + 1;
//             $next_offset = mb_strpos($plain," ",$start_offset);
//             if (!$next_offset){
//                 $next_offset = mb_strlen($plain); 
//             }
//             $word_length = $next_offset - $start_offset;
//             $text_word = mb_substr($text,$start_offset,$word_length);
//             $text_space = mb_substr($text,$next_offset,1);

//             $word_linked = false;
//             $space_linked = false;
//             if ($row1['linked'] == 1){
//                 $sql2 = "SELECT ec.research_id, ec.collection_id, ec.division_id, rp.part_id, rp.src_to_position, rp.src_to_word, ec.link_id
//                            FROM a_res_parts rp
//                            JOIN view_proj_link_elm_col ec
//                              ON ec.research_id = rp.research_id
//                             AND ec.collection_id = rp.collection_id
//                            LEFT JOIN a_res_idx_division rd
//                              ON ec.research_id = rd.research_id
//                             AND ec.collection_id = rd.collection_id 
//                             AND ec.division_id = rd.division_id
//                           WHERE (".$src_pos." > rp.src_from_position OR (".$src_pos." = rp.src_from_position AND ".$word_no." >= rp.src_from_word)) 
//                             AND (".$src_pos." < rp.src_to_position   OR (".$src_pos." = rp.src_to_position   AND ".$word_no." <= rp.src_to_word))
//                             AND ec.project_id = ".$id['proj']."
//                             AND ec.element_id = ".$id['elm']."
//                             AND (ec.division_id = 0 OR rp.position BETWEEN rd.from_position AND rd.to_position)
//                           ORDER BY rp.research_id,rp.collection_id,ec.link_id";

//                 $result2 = mysqli_query($con,$sql2);
//                 if (!$result2) {
//                     exit_error('Error 6 in text_func.php: ' . mysqli_error($con));
//                 }
//                 if($row2 = mysqli_fetch_array($result2)){
//                     $word_linked = true;
//                     if ($src_pos < $row2['src_to_position'] || $word_no < $row2['src_to_word']){
//                         $space_linked = true;
//                     }
//                 }
//             }

//             $wordObj = array(
//                 "word_no"=>$word_no,
//                 "word"=>$text_word,
//                 "space"=>$text_space,
//                 "word_linked"=>$word_linked,
//                 "space_linked"=>$space_linked
//             );
//             if ($word_linked){
//                 $wordObj['link'] = $row2['link_id'];
//                 $wordObj['res'] = $row2['research_id'];
//                 $wordObj['col'] = $row2['collection_id'];
//                 $wordObj['div'] = $row2['division_id'];
//                 $wordObj['prt'] = $row2['part_id'];
//             }

//             if ($anchor_part && $word_no == $prop['anchor_word']){
//                 $anchor_part = false;
//                 $wordObj['anchor'] = true;
//             }

//             array_push($txt_list,$wordObj);
//             $word_no++;
//         }

//         $partObj = array(
//             "part_id"=>$row1['seq_part'],
//             "position"=>$row1['seq_position'],
//             "part_name"=>$row1['name'],
//             "txt_list"=>$txt_list
//         );

//         if ($anchor_part){
//             $partObj['anchor'] = true;
//         }

//         array_push($rep['part_list'],$partObj);
//     }

//     $rep["title"] = txt_get_title($id);

//     return $rep;
// }

// --------------------------------------------------------------------------------------
// ---- get linked points                                     
// --------------------------------------------------------------------------------------
// function txt_get_points($id,$dsp){
//     global $con,$heb_num;

//     $rep = array();

//     // get text properties
//     $prop = elmseq_get($id);

//     $filter = "";

//     // point filter
//     if (array_key_exists('point', $dsp)){
//         $filter .= " AND pt.research_id = ".$dsp['point']['res']."
//                      AND pt.part_id = ".$dsp['point']['prt'];
//     }

//     // get text
//     $sql = "SELECT pt.part_id pt_part,ec.link_id,pt.research_id pt_research,pt.collection_id pt_collection,
//                    pt.src_research, pt.src_collection,
//                    pt.src_from_position ,pt.src_from_word,
//                    pt.src_to_position ,pt.src_to_word
//               FROM a_res_parts seq
// 		      JOIN a_res_parts pt
//                 ON pt.src_research = seq.src_research
//                AND pt.src_collection = seq.src_collection 
//                AND (pt.src_from_position > seq.src_from_position OR (pt.src_from_position = seq.src_from_position AND pt.src_from_word >= seq.src_from_word)) 
//                AND (pt.src_to_position < seq.src_to_position OR (pt.src_to_position = seq.src_to_position AND pt.src_to_word <= seq.src_to_word)) 
//               JOIN view_proj_link_elm_col ec
//                 ON ec.research_id = pt.research_id
//                AND ec.collection_id = pt.collection_id
//              WHERE seq.research_id = ".$prop['research_id']."
//                AND seq.collection_id = ".$prop['collection_id']."
//                AND seq.position BETWEEN ".$prop['from_position']." AND ".$prop['to_position']."
//                AND ec.project_id = ".$id['proj']."
//                AND ec.element_id = ".$id['elm']."
//                ".$filter."
//              ORDER BY seq.position,pt.src_from_position,pt.src_from_word";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 7 in text_func.php: ' . mysqli_error($con));
//     }

//     $rep = array();
//     while($row = mysqli_fetch_array($result)) {
//         if ($row['src_from_position'] == $row['src_to_position']){
//             array_push($rep,array(
//                 "prt"=>$row['pt_part'],
//                 "link"=>$row['link_id'],
//                 "res"=>$row['pt_research'],
//                 "col"=>$row['pt_collection'],
//                 "src_pos"=>$row['src_from_position'],
//                 "src_from_word"=>$row['src_from_word'],
//                 "src_to_word"=>$row['src_to_word']
//             ));
//         } else {
//             $sql2 = "SELECT src.position,src.gen_word_count
//                        FROM a_res_parts src
//                       WHERE src.research_id = ".$row['src_research']."
//                         AND src.collection_id = ".$row['src_collection']."
//                         AND src.position BETWEEN ".$row['src_from_position']." AND pt.".$row['src_to_position']."
//                       ORDER BY src.position";
//             $result2 = mysqli_query($con,$sql2);
//             if (!$result2) {
//                 exit_error('Error 8 in text_func.php: ' . mysqli_error($con));
//             }
//             while($row2 = mysqli_fetch_array($result2)) {
//                 if ($row2['position'] == $row['src_from_position']){
//                     $fromPos = $row['src_from_word'];
//                 } else {
//                     $fromPos = 0;
//                 }
//                 if ($row2['position'] == $row['src_to_position']){
//                     $toPos = $row['src_to_word'];
//                 } else {
//                     $toWord = $row2['gen_word_count'];
//                 }
//                 array_push($rep,array(
//                     "prt"=>$row['pt_part'],
//                     "link"=>$row['link_id'],
//                     "res"=>$row['pt_research'],
//                     "col"=>$row['pt_collection'],
//                     "src_pos"=>$row2['position'],
//                     "src_from_word"=>$fromPos,
//                     "src_to_word"=>$toWord
//                 ));
//             }
//         }
//     }

//     return $rep;
// }

// --------------------------------------------------------------------------------------
// ---- get title                                     
// --------------------------------------------------------------------------------------
function txt_get_title($id){
    global $con,$heb_num;

    $sql = "SELECT d.level,d.division_id,d.name_heb name
               FROM a_res_idx_division d
               JOIN a_proj_elm_sequence s
                 ON d.research_id = s.research_id
                AND d.collection_id = s.collection_id
                AND d.index_id = s.seq_index
                AND d.from_position <= s.from_position
                AND d.to_position >= s.to_position
               JOIN a_res_idx_levels l
                 ON l.research_id = d.research_id
                AND l.collection_id = d.collection_id
                AND l.index_id = d.index_id
				AND l.level = d.level
              WHERE s.project_id = ".$id['proj']."
                AND s.element_id = ".$id['elm']."
                AND l.part_of_key = TRUE
              ORDER BY d.level desc";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 9 in text_func.php: ' . mysqli_error($con));
    }

    $title = "";
    $sep = "";
    while($row = mysqli_fetch_array($result)) {
        $title .= $sep.$row['name'];
        $sep = "/";
    }

    return $title;
}
?>