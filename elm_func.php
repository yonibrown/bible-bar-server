<?php

// --------------------------------------------------------------------------------------
// ---- get element                                     
// --------------------------------------------------------------------------------------
function elm_get($id){
    return elm_prop($id,elm_get_basic($id));
}

// --------------------------------------------------------------------------------------
// ---- get element                                     
// --------------------------------------------------------------------------------------
function elm_get_basic($id){
    global $con;

    $sql = "SELECT type, name, description, opening_element,
                   position
            FROM a_proj_elements e
            WHERE e.project_id = ".$id['proj']."
              AND e.element_id = ".$id['elm'];
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 1 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $type = $row['type'];

    return $row;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function elm_prop($id,$prop){
    switch($prop['type']){
        case 'bar':
        case 'text':
            $spc_attr = elmseq_get($id);
            break;
        case 'link':
            $spc_attr = elmlnk_get($id);
            break;
        // case 'research':
        //     $spc_attr = elmres_get($id);
        //     break;
        case 'parts':
            $spc_attr = elmprt_get($id);
            break;
        default:
            $spc_attr = array();
    }

    return array(
        "id"=>(int)$id['elm'],
        "proj"=>(int)$id['proj'],
        "type"=>$prop['type'],
        "name"=>$prop['name'],
        "position"=>(float)$prop['position'],
        "attr"=>$spc_attr
    );
}

// --------------------------------------------------------------------------------------
// ---- create new element
// --------------------------------------------------------------------------------------
function elm_create($prop){
    global $con;

    $proj = $prop['proj'];
    $type = $prop['type'];

    $sql = "SELECT MAX(element_id) element_id
              FROM a_proj_elements
             WHERE project_id = ".$proj;
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 2 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $elm = $row['element_id']+1;

    $sql = "INSERT INTO a_proj_elements
                (project_id, element_id, type, 
                 name, description, position, 
                 show_props, opening_element) 
            VALUES(".$proj.", 
                ".$elm.", 
                '".$type."',
                '".$prop['name']."',' ',
                ".$prop['position'].", 
                0,0)";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 3 in elm_func.php: ' . mysqli_error($con));
    }

    $id = array('proj'=>$proj,'elm'=>$elm);

    if (array_key_exists('opening_element',$prop)){
        $sql = "INSERT INTO a_proj_link_elements
                    (project_id, link_id, element_id) 
                SELECT project_id, link_id, $elm
                  FROM a_proj_link_elements
                 WHERE project_id = ".$proj."
                   AND element_id = ".$prop['opening_element'];
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 21 in elm_func.php: ' . mysqli_error($con));
        }
    }

    if (array_key_exists('links',$prop)){
        foreach ($prop['links'] as $link_obj) {
            lnk_add_element($link_obj,array("elm"=>$elm));
        }
    }

    $res = null;
    switch($type){
        case 'bar':
            bar_create($id,$prop);
            break;
        case 'text':
            txt_create($id,$prop);
            break;
        case 'link':
            elmlnk_create($id,$prop);
            break;
        case 'research':
            elmres_create($id,$prop);
            break;
        case 'parts':
            $res = elmprt_create($id,$prop);
            break;
    }

    $rep = array("elm"=>elm_prop($id,$prop));
    if ($res != null){
        $rep['res'] = $res;
    }

    return $rep;
}

// --------------------------------------------------------------------------------------
// ---- set element's attributes
// --------------------------------------------------------------------------------------
function elm_set($id,$prop){
    global $con;

    $sql_set = '';
    $sep = '';
    foreach($prop as $attr => $val) {
        switch ($attr) {
            case "show_props":
                $sql_set .= $sep.$attr." = ".($val=='true'?1:0);
                $sep = ',';
                break;
                case "name":
                    $sql_set .= $sep.$attr." = '".$val."'";
                    $sep = ',';
                    break;
            }   
    }

    if ($sql_set != ''){
        $sql = "UPDATE a_proj_elements 
                SET ".$sql_set."  
                WHERE project_id = ".$id['proj']."
                AND element_id = ".$id['elm'];
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 4 in elm_func.php: ' . mysqli_error($con));
        }
    }

    $elm_prop = elm_get_basic($id);
    switch($elm_prop['type']){
        case 'bar':
            elmseq_set($id,$prop);
            break;
        case 'text':
            elmseq_set($id,$prop);
            // txt_set($id,$prop);
            break;
        case 'parts':
            elmprt_set($id,$prop);
            break;
        case 'link':
            elmlnk_set($id,$prop);
            break;
    }

    return elm_get($id);
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function elm_links_changed($id){
    $elm_prop = elm_get($id);
    switch($elm_prop['type']){
        case 'bar':
            elmseq_set($id,array('points_generated'=>FALSE));
            break;
    }
}

// --------------------------------------------------------------------------------------
// ---- get element's linked categories
// --------------------------------------------------------------------------------------
// function elm_get_categories($id,$prop){
//     global $con;

//     $filter = '';
//     if ($prop != null){
//         foreach($prop as $attr => $val) {
//             switch ($attr) {
//                 case "include_selection":
//                 case "exclude_selection":
//                     $selection_exists = "EXISTS(
//                             SELECT 1
//                               FROM a_res_parts rp
//                              WHERE rp.research_id = rc.research_id
//                                AND rp.collection_id = rc.collection_id
//                                AND rp.src_from_position = ".$val['from_position']."
//                                AND rp.src_from_word = ".$val['from_word']."
//                                AND rp.src_to_position = ".$val['to_position']."
//                                AND rp.src_to_word = ".$val['to_word'].")";
//                     if ($attr == "include_selection"){
//                         $filter .= " AND ".$selection_exists;
//                     } else {
//                         $filter .= " AND NOT ".$selection_exists;
//                     }
//                     break;
//                 case "research_id":
//                     $filter .= " AND ec.research_id = ".$val;
//                     break;
//                 // case "part_id":
//                 //     $filter .= " AND EXISTS( 
//                 //             SELECT 1
//                 //               FROM a_res_parts rp
//                 //              WHERE rp.research_id = rc.research_id
//                 //                AND rp.collection_id = rc.collection_id
//                 //                AND rp.part_id = ".$val.")";
//                 //     break;
//             }   
//         }
//     }

//     $sql = "SET SQL_BIG_SELECTS=1";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 999 in elm_func.php: ' . mysqli_error($con));
//     }

//     $sql = "SELECT ec.research_id,ec.collection_id,ec.division_id,ec.link_id,ec.color,ec.hilight,
//                    rd.name_heb div_name,rc.name_heb col_name,r.name_heb res_name
//               FROM view_proj_link_elm_col ec
//               JOIN a_researches r
//                 ON ec.research_id = r.research_id
//               JOIN a_res_collections rc
//                 ON ec.research_id = rc.research_id
//                AND ec.collection_id = rc.collection_id
//               LEFT JOIN a_res_idx_division rd
//                 ON ec.research_id = rd.research_id
//                AND ec.collection_id = rd.collection_id
//                AND ec.division_id = rd.division_id
//              WHERE ec.project_id = ".$id['proj']."
//                AND ec.element_id = ".$id['elm']."
//                ".$filter."
//              ORDER BY ec.research_id,ec.collection_id,ec.division_id";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 13 in elm_func.php: ' . mysqli_error($con));
//     }

//     $catArray = array();
//     $index = 0;
//     while($row = mysqli_fetch_array($result)) {
//         $catArray[$index] = array("link"=>$row['link_id'],
//                                 "color"=>$row['color'],
//                                 "hilight"=>($row['hilight'] == 1),
//                                 "res"=>$row['research_id'],
//                                 "res_name"=>$row['res_name'],
//                                 "col"=>$row['collection_id'],
//                                 "col_name"=>$row['col_name'],
//                                 "div"=>$row['division_id'],
//                                 "div_name"=>$row['div_name']);
//         $index++;
//     }

//     return $catArray;
// }

// --------------------------------------------------------------------------------------
// ---- get element's links
// --------------------------------------------------------------------------------------
// function elm_get_links($id){
//     global $con;

//     $sql = "SELECT link_id 
//               FROM a_proj_link_elements
//              WHERE project_id = ".$id['proj']."
//                AND element_id = ".$id['elm']."
//              ORDER BY link_id";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 14 in elm_func.php: ' . mysqli_error($con));
//     }

//     $lnkArray = array();
//     $index = 0;
//     while($row = mysqli_fetch_array($result)) {
//         $lnkArray[$index] = array(
//             "proj"=>$id['proj'],
//             "link"=>$row['link_id']
//         );
//         $index++;
//     }

//     return $lnkArray;
// }

// --------------------------------------------------------------------------------------
// ---- get link element                                     
// --------------------------------------------------------------------------------------
function elmlnk_get($id){
    global $con;

    $sql = "SELECT link_id,link_display
            FROM a_proj_elm_link
            WHERE project_id = ".$id['proj']."
              AND element_id = ".$id['elm'];
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 6 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $attr = array(
        'link_id'=>(int)$row['link_id'],
        'link_display'=>$row['link_display']
    );

    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- create new link element
// --------------------------------------------------------------------------------------
function elmlnk_create($id,$prop){
    global $con;

    // if (array_key_exists('link_id',$prop)){
    //     $link = $prop['link_id'];
    // } else {
    //     $link = lnk_create(array("proj"=>$id['proj']))['link'];
    // }

    $sql = "INSERT INTO a_proj_elm_link
                (project_id, element_id, link_id, link_display)
            VALUES(".$id['proj'].", 
                   ".$id['elm'].", 
                   ".$prop['link_id'].",
                   'list')";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 7 in elm_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- get research element                                     
// --------------------------------------------------------------------------------------
// function elmres_get($id){
//     global $con;

//     $sql = "SELECT research_id
//             FROM a_proj_elm_research
//             WHERE project_id = ".$id['proj']."
//               AND element_id = ".$id['elm'];
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 8 in elm_func.php: ' . mysqli_error($con));
//     }
//     $row = mysqli_fetch_array($result);
//     $attr = array(
//         'res'=>(int)$row['research_id']
//     );
//     return $attr;
// }

// --------------------------------------------------------------------------------------
// ---- create new research element
// --------------------------------------------------------------------------------------
function elmres_create($id,$prop){
    global $con;

    $sql = "INSERT INTO a_proj_elm_research
                (project_id, element_id, research_id)
            VALUES(".$id['proj'].", 
                   ".$id['elm'].", 
                   '".$prop['res']."')";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 9 in elm_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- get research points element                                     
// --------------------------------------------------------------------------------------
function elmprt_get($id){
    global $con;

    $sql = "SELECT research_id, sort,ordering
            FROM a_proj_elm_parts
            WHERE project_id = ".$id['proj']."
              AND element_id = ".$id['elm'];
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 10 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $attr = array(
        'res'=>(int)$row['research_id'],
        'sort'=>$row['sort'],
        'ordering'=>$row['ordering']
    );
    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- create new research points element
// --------------------------------------------------------------------------------------
function elmprt_create($id,$prop){
    global $con;

    if (array_key_exists('res',$prop)){
        $res = $prop['res'];
    } else {
        $resProp = array(
            'proj'=>$id['proj'],
            'name'=>$prop['name'],
            'desc'=>''
        );
        $resObj = res_create($resProp);
        $res = $resObj['id'];
    }

    $sql = "INSERT INTO a_proj_elm_parts
                (project_id, element_id, research_id, sort, ordering)
            VALUES(".$id['proj'].", 
                   ".$id['elm'].", 
                   ".$res.", 
                   'src','ASC')";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 11 in elm_func.php: ' . mysqli_error($con));
    }
    return $resObj;
}

// --------------------------------------------------------------------------------------
// ---- set attributes
// --------------------------------------------------------------------------------------
function elmprt_set($id,$prop){
    global $con;

    $sql_set = '';
    $sep = '';

    foreach($prop as $attr => $val) {
        switch ($attr) {
            case "sort":
            case "ordering":
                $sql_set .= $sep.$attr." = '".$val."'";
                $sep = ',';
                break;
        }   
    }

    if ($sql_set != ''){
        $sql = "UPDATE a_proj_elm_parts 
                SET ".$sql_set."  
                WHERE project_id = ".$id['proj']."
                AND element_id = ".$id['elm'];
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 16 in elm_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- set attributes
// --------------------------------------------------------------------------------------
function elmlnk_set($id,$prop){
    global $con;

    $row = elmlnk_get($id);

    $sql1_set = '';
    $sep1 = '';
    $sql2_set = '';
    $sep2 = '';

    foreach($prop as $attr => $val) {
        switch ($attr) {
            case "link_display":
                $sql1_set .= $sep1.$attr." = '".$val."'";
                $sep1 = ',';
                break;
            case "name":
                $sql2_set .= $sep2.$attr." = '".$val."'";
                $sep2 = ',';
                proj_objects_to_reload($id,array(
                    'object_type'=>'link_name',
                    "action"=>"update",
                    'link'=>$row['link_id'],
                    'name'=>$val
                ));
                break;
            }   
    }

    if ($sql1_set != ''){
        $sql1 = "UPDATE a_proj_elm_link 
                SET ".$sql1_set."  
                WHERE project_id = ".$id['proj']."
                AND element_id = ".$id['elm'];
        $result1 = mysqli_query($con,$sql1);
        if (!$result1) {
            exit_error('Error 19 in elm_func.php: ' . mysqli_error($con));
        }
    }

    if ($sql2_set != ''){
        $sql2 = "UPDATE a_proj_links l
                SET ".$sql2_set."  
                WHERE l.project_id = ".$id['proj']."
                AND l.link_id = ".$row['link_id'];
        $result2 = mysqli_query($con,$sql2);
        if (!$result2) {
            exit_error('Error 19 in elm_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
// function elm_link_to_cat($id,$prop){
//     global $con;

//     $sql = "SELECT link_id
//               FROM a_proj_link_collections 
//              WHERE project_id = ".$id['proj']."
//                AND research_id = ".$prop['res']."
//                AND collection_id = ".$prop['col'];
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 12 in link_func.php: ' . mysqli_error($con));
//     }
//     if ($row = mysqli_fetch_array($result)){
//         $linkId = $row['link_id'];
//     } else {
//         $linkId = lnk_create(array("proj"=>$id['proj']));
//         lnk_add_categories($linkId,array("type"=>"category","data"=>$prop));
//     }

//     $link_obj = array('proj'=>$id['proj'],'link'=>$linkId);
//     lnk_add_element($link_obj,$id);
// }

// --------------------------------------------------------------------------------------
// ---- get bar/text element
// --------------------------------------------------------------------------------------
function elmseq_get($id){
    global $con;

    $sql = "SELECT e.research_id, e.collection_id,
                   e.from_position, e.to_position, 
                   e.seq_index index_id, e.seq_level, e.color_level, 
                   e.anchor_position, e.anchor_word, 
                   e.segments_generated, e.points_generated, e.gen_total_words
            FROM a_proj_elm_sequence e
            WHERE e.project_id = ".$id['proj']." 
              AND e.element_id = ".$id['elm'];
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 15 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);

    $indexId = array(
        'res'=>$row['research_id'],
        'col'=>$row['collection_id'],
        'idx'=>$row['index_id']
    );

    $fromPos = $row['from_position'];
    $fromKey = residx_position_to_key($indexId,array('position'=>$fromPos))['list'];

    $toPos = $row['to_position'];
    $toKey = residx_position_to_key($indexId,array('position'=>$toPos))['list'];

    $attr = array(
        'research_id'=>(int)$row['research_id'],
        'collection_id'=>(int)$row['collection_id'],
        'from_position'=>(float)$fromPos,
        'to_position'=>(float)$toPos,
        'seq_index'=>(int)$row['index_id'],
        'seq_level'=>(int)$row['seq_level'],
        'color_level'=>(int)$row['color_level'],
        'from_key'=>$fromKey,
        'to_key'=>$toKey,
        'anchor_position'=>(float)$row['anchor_position'],
        'anchor_word'=>(int)$row['anchor_word'],
        'segments_generated'=>($row['segments_generated']=='1'),
        'points_generated'=>($row['points_generated']=='1'),
        'total_words'=>(int)$row['gen_total_words']
    );

    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- set text attributes
// --------------------------------------------------------------------------------------
function elmseq_set($id,$prop){
    global $con;

    $sql_set = '';
    $sep = '';

    if (array_key_exists('research_id',$prop) && array_key_exists('collection_id',$prop)){
        $res_pred = " AND research_id = ".$prop['research_id']." 
                        AND collection_id = ".$prop['collection_id'];
    } else {
        $res_pred = " AND (research_id,collection_id) = (
                        SELECT research_id,collection_id
                            FROM a_proj_elm_sequence
                            WHERE project_id = ".$id['proj']."
                            AND element_id = ".$id['elm']."
                            )";
    }

    $cancel_generated = FALSE;
    foreach($prop as $attr => $val) {
        switch ($attr) {
            case "division_id":
            case "from_div":
            case "to_div":
                $sql = "SELECT from_position,to_position,level
                            FROM a_res_idx_division
                            WHERE division_id = ".$val."
                            ".$res_pred;
                $result = mysqli_query($con,$sql);
                if (!$result) {
                    exit_error('Error 17 in elm_func.php: ' . mysqli_error($con));
                }
                $row = mysqli_fetch_array($result);

                if ($attr == "division_id" || $attr == "from_div"){
                    $sql_set .= $sep."from_position = ".$row['from_position'];
                    $sep = ',';
                }
                if ($attr == "division_id" || $attr == "to_div"){
                    $sql_set .= $sep."to_position = ".$row['to_position'];
                    $sep = ',';
                }
                if ($attr == "division_id"){
                    $level = $row['level']-1;
                    $sql_set .= $sep."seq_level = ".$level;
                    $sep = ',';
                }

                $cancel_generated = TRUE;
                break;

            case "research_id":
            case "collection_id":
            case "seq_index":
            case "seq_level":
                $sql_set .= $sep.$attr." = ".$val;
                $sep = ',';
                $cancel_generated = TRUE;
                break;

            case "segments_generated":
            case "points_generated":
                $sql_set .= $sep.$attr." = ".($val?"TRUE":"FALSE");
                $sep = ',';
                break;
                }   
    }

    if ($cancel_generated){
        $sql_set .= ",segments_generated = FALSE
                     ,points_generated = FALSE
                     ,gen_total_words = 0";
    }

    if ($sql_set != ''){
        $sql = "UPDATE a_proj_elm_sequence 
                SET ".$sql_set."  
                WHERE project_id = ".$id['proj']."
                AND element_id = ".$id['elm'];
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 18 in elm_func.php: ' . mysqli_error($con));
        }

        // if ($attr = "sequence_id"){
        //     txt_update_division_colors($id);
        // }
    }
}
?>