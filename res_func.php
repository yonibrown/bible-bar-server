<?php
// --------------------------------------------------------------------------------------
// ---- get list of researches in the system
// --------------------------------------------------------------------------------------
// function res_get_list(){
//     global $con;

//     $list = array();
//     $sql = "SELECT research_id id,name_heb name,description
//               FROM a_researches
//              WHERE project_id = 0 
//              ORDER BY research_id";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 1 in res_func.php: ' . mysqli_error($con));
//     }
//     while($row = mysqli_fetch_array($result)) {
//         array_push($list,array(
//             "id"=>$row['id'],
//             "name"=>$row['name'],
//             "desc"=>$row['description']
//         ));
//     }

//     return $list;
// }

// --------------------------------------------------------------------------------------
// ---- get research                                     
// --------------------------------------------------------------------------------------
function res_get($id){
    global $con;

    $res = $id['res'];
    $sql = "SELECT name_heb name,description
            FROM a_researches
            WHERE research_id = ".$res;
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 2 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $attr = array(
        'name'=>$row['name'],
        'desc'=>$row['description']
    );
    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- set research attributes
// --------------------------------------------------------------------------------------
// function res_set($id,$prop){
//     global $con;

//     $res = $id['res'];
//     $sql_set = '';
//     $sep = '';
//     foreach($prop as $attr => $val) {
//         switch ($attr) {
//             default:
//                 $name = $val;

//                 $sql_set = $sql_set.$sep.$attr." = ".$name;
//                 $sep = ',';
//                 break;
//         }   
//     }

//     $sql = "UPDATE a_researches 
//             SET ".$sql_set."  
//             WHERE research_id = ".$res;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 3 in res_func.php: ' . mysqli_error($con));
//     }
// }

// --------------------------------------------------------------------------------------
// ---- create new research
// --------------------------------------------------------------------------------------
function res_create($prop){
    global $con;

    $sql = "SELECT MAX(research_id) research_id
                FROM a_researches";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 4 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $res = $row['research_id']+1;

    if (isset($prop['proj'])){
        $proj = $prop['proj'];
    } else {
        $proj = 0;
    }

    $sql = "INSERT INTO a_researches
                (research_id, 
                name_heb, 
                description,
                project_id) 
            VALUES(".$res.", 
                '".$prop['name']."', 
                '".$prop['desc']."', 
                ".$proj.")";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 5 in res_func.php: ' . mysqli_error($con));
    }

    return array("res"=>$res);
}

// --------------------------------------------------------------------------------------
// ---- get category list for research                                   
// --------------------------------------------------------------------------------------
function res_get_col_list($id){
    global $con;

    $res = $id['res'];
    $list = array();
    $sql = "SELECT collection_id id,name_heb name,description
            FROM a_res_collections
            WHERE research_id = ".$res."
            ORDER BY collection_id";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 6 in res_func.php: ' . mysqli_error($con));
    }
    while($row = mysqli_fetch_array($result)) {
        array_push($list,array(
            "id"=>(int)$row['id'],
            "name"=>$row['name'],
            "description"=>$row['description']
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- get point list for research                                   
// --------------------------------------------------------------------------------------
function res_get_prt_list($id,$prop){
    global $con,$heb_num;

    $res = $id['res'];

    $sort_key = array(
        'col'=>"CONCAT(LPAD(prt.collection_id,5,0),'_',LPAD(prt.position,11,0))",
        "src"=>"CONCAT(LPAD(prt.src_from_position,11,0),'_',LPAD(prt.src_from_word,5,0),'_',LPAD(src.position,11,0))"
    );

    if (array_key_exists('ordering', $prop)){
        $order = $prop['ordering'];
    } else {
        $order = 'ASC';
    }


    $order_by = "prt.src_from_position ".$order.",prt.src_from_word ".$order;
    if (array_key_exists('sort', $prop)){
        if ($prop['sort'] == 'col'){
            $order_by = "prt.collection_id ".$order.",".$order_by;
        }
    }
    
    $filter = '';

    if (array_key_exists('part_id', $prop)){
        $filter .= 'AND prt.part_id = '.$prop['part_id'];
    }

    $sql = "SET SQL_BIG_SELECTS=1";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 999 in res_func.php: ' . mysqli_error($con));
    }

    $sql = "SELECT prt.part_id, prt.collection_id, c.name_heb col_name, 
                   prt.src_from_position, prt.src_to_position, prt.src_from_word, prt.src_to_word, 
                   src.abs_name_heb src_name,src.text src_text,
                   ".$sort_key['src']." src_sort_key,
                   ".$sort_key['col']." col_sort_key
            FROM a_res_parts prt
            JOIN a_res_parts src
              ON src.research_id = prt.src_research
             AND src.collection_id = prt.src_collection
             AND src.position BETWEEN prt.src_from_position AND prt.src_to_position
            JOIN a_res_collections c
              ON c.research_id = prt.research_id
             AND c.collection_id = prt.collection_id
           WHERE prt.research_id = ".$res."
           ".$filter."
           ORDER BY ".$order_by;
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 7 in res_func.php: ' . mysqli_error($con));
    }

    $list = array();
    while($row = mysqli_fetch_array($result)) {
        $mark = sub_words($row['src_text'],$row['src_from_word'],$row['src_to_word']);

        array_push($list,array(
            "id"=>(int)$row['part_id'],
            "col"=>(int)$row['collection_id'],
            "col_name"=>$row['col_name'],
            "text_before"=>mb_substr($row['src_text'],0,$mark['start']),
            "text_part"=>$mark['text'],
            "text_after"=> mb_substr($row['src_text'],$mark['end']),
            "src_name" => $row['src_name'],
            "sort_key" => array(
                "col"=>$row['col_sort_key'],
                "src"=>$row['src_sort_key']
            )
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- create new category in research
// --------------------------------------------------------------------------------------
function res_new_category($id,$prop){
    global $con;

    $res = $id['res'];
    $sql = "SELECT IFNULL(MAX(collection_id),0) col,
                    IFNULL(MAX(position),0) pos
                FROM a_res_collections 
                WHERE research_id = ".$res;
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 8 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $col = $row['col']+1;
    $pos = $row['pos']+1;
    
    $desc = '';
    if (array_key_exists('desc',$prop)){
        $desc = $prop['desc'];
    }
    

    $sql = "INSERT INTO a_res_collections
                (research_id, collection_id, type, position, name_eng, name_heb, description)
            VALUES (
                ".$res.", 
                ".$col.", 
                'list',
                ".$pos.", 
                '".$prop['name']."', 
                '".$prop['name']."', 
                '".$desc."')";
            
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 9 in res_func.php: ' . mysqli_error($con));
    }

    return $col;
}

// --------------------------------------------------------------------------------------
// ---- create new part in research
// --------------------------------------------------------------------------------------
// function res_new_part($id,$prop){
//     global $con;

//     $res = $id['res'];
//     $sql = "SELECT IFNULL(MAX(part_id),0) part,
//                    IFNULL(MAX(position),0) pos
//               FROM a_res_parts
//              WHERE research_id = ".$res;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 10 in res_func.php: ' . mysqli_error($con));
//     }
//     $row = mysqli_fetch_array($result);
//     $part = $row['part']+1;
//     $pos = $row['pos']+1;

//     $sql = "INSERT INTO a_res_parts
//                 (research_id, part_id, type, 
//                  collection_id, position,
//                  div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
//                  src_research, src_part, src_collection, 
//                  src_from_position, src_from_word, src_to_position, src_to_word, 
//                  gen_word_count, 
//                  text, comment) 
//             VALUES(
//             ".$res.",".$part.",'pointer',
//             ".$prop['collection_id'].",".$pos.",
//             '','','','',
//             ".$prop['src_research'].",0,".$prop['src_collection'].",
//             ".$prop['src_from_position'].",".$prop['src_from_word'].",
//             ".$prop['src_to_position'].",".$prop['src_to_word'].",
//             0,'','')";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 11 in res_func.php: ' . mysqli_error($con));
//     }

//     res_update_generated_columns($res,$part);

//     return $pt;
// }

// --------------------------------------------------------------------------------------
// ---- delete part from research
// --------------------------------------------------------------------------------------
// function res_del_part($id,$prop){
//     global $con;

//     $res = $id['res'];
//     $where = '';
//     foreach($prop as $attr => $val) {
//         switch ($attr) {
//             default:
//                 $where .= " AND ".$attr." = ".$val;
//                 break;
//         }   
//     }
            
//     $sql = "DELETE FROM a_res_parts
//             WHERE research_id = ".$res."
//             ".$where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 12 in res_func.php: ' . mysqli_error($con));
//     }
// }

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_upd_parts($id,$prop){
    global $con;

    $res = $id['res'];

    $partArr = $prop['partList'];
    $updAttr = $prop['updAttr'];

    $sql_set = '';
    $sep = '';
    foreach($updAttr as $attr => $val) {
        switch ($attr) {
            case 'collection_id':
                if ($val == 0){
                    $cat = res_new_category($id,array('name'=>$updAttr['collection_name']));
                } else {
                    $cat = $val;
                }
                $sql_set = $sql_set.$sep.$attr." = ".$cat;
                $sep = ',';
                break;
        }   
    }

    if ($sql_set != ''){
        $sql = "UPDATE a_res_parts 
                SET ".$sql_set."  
                WHERE research_id = ".$res."
                  AND part_id ".inList($partArr);
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 33 in res_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_duplicate($id,$prop){
    global $con;

    $attr = res_get($id);
    $attr['name'] = substr($attr['name'],1,15)."-COPY";
    $newId = res_create($attr)['res'];

    // copy parts
    $partArr = $prop['partList'];
    $sql = "INSERT INTO  a_res_parts 
                (research_id, part_id, type, collection_id, position, 
                div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
                text, comment, src_research, src_part, src_collection, 
                src_from_position, src_from_word, src_to_position, 
                src_to_word, gen_word_count, gen_text)
            SELECT ".$newId.",ROW_NUMBER() OVER (ORDER BY part_id), 
                type, collection_id, position, 
                div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
                text, comment, src_research, src_part, src_collection, 
                src_from_position, src_from_word, src_to_position, 
                src_to_word, gen_word_count, gen_text 
            FROM a_res_parts
            WHERE research_id = ".$id['res']."
              AND part_id ".inList($partArr);
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 35 in res_func.php: ' . mysqli_error($con));
    }
      
    //copy collections
    $sql = "INSERT INTO a_res_collections
                (research_id, collection_id, type, position, 
                name_eng, name_heb, description)
            SELECT ".$newId.",collection_id, type, position, 
                name_eng, name_heb, description
            FROM a_res_collections
            WHERE research_id = ".$id['res'];
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 36 in res_func.php: ' . mysqli_error($con));
    }

    return array('new_res_id'=>$newId);

    // $newPartId = 0;
    // $sql1 = "SELECT type, collection_id, position, 
    //             div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
    //             text, comment, src_research, src_part, src_collection, 
    //             src_from_position, src_from_word, src_to_position, 
    //             src_to_word, gen_word_count, gen_text 
    //         FROM a_res_parts
    //         WHERE research_id = ".$id['res']."
    //           AND part_id ".inList($partArr)."
    //         ORDER BY part_id";
    // $result1 = mysqli_query($con,$sql1);
    // if (!$result1) {
    //     exit_error('Error 34 in res_func.php: ' . mysqli_error($con));
    // }
    // while($row1 = mysqli_fetch_array($result1)) {
    //     $sql2 = "INSERT INTO  a_res_parts 
    //         (research_id, part_id, type, collection_id, position, 
    //         div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
    //         text, comment, src_research, src_part, src_collection, 
    //         src_from_position, src_from_word, src_to_position, 
    //         src_to_word, gen_word_count, gen_text )
    //         VALUES (research_id, part_id, type, collection_id, position, 
    //         div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
    //         text, comment, src_research, src_part, src_collection, 
    //         src_from_position, src_from_word, src_to_position, 
    //         src_to_word, gen_word_count, gen_text )"
    // }




}

// --------------------------------------------------------------------------------------
// ---- update point in a research
// --------------------------------------------------------------------------------------
// function res_upd_point($id,$prop){
//     global $con;

//     $res = $id['res'];

//     $ptId = $prop['pt_id'];
//     $ptAttr = $prop['pt_attr'];

//     $sql_set = '';
//     $sep = '';
//     foreach($ptAttr as $attr => $val) {
//         switch ($attr) {
//             case 'collection_id':
//                 $sql1 = "SELECT MAX(position)+1 pos
//                            FROM a_res_parts
//                           WHERE research_id = ".$res."
//                             AND collection_id = ".$val;
//                 $result1 = mysqli_query($con,$sql1);
//                 if (!$result1) {
//                     exit_error('Error 13 in res_func.php: ' . mysqli_error($con));
//                 }
//                 $row1 = mysqli_fetch_array($result1);
//                 $sql_set = $sql_set.$sep."collection_id = ".$val.",position = ".$row1['pos'];
//                 $sep = ',';
//                 break;
//             default:
//                 $sql_set = $sql_set.$sep.$attr." = ".$val;
//                 $sep = ',';
//                 break;
//         }   
//     }

//     $sql = "UPDATE a_res_parts 
//             SET ".$sql_set."  
//             WHERE research_id = ".$res."
//               AND part_id = ".$ptId['part_id'];
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 14 in res_func.php: ' . mysqli_error($con));
//     }
// }

// --------------------------------------------------------------------------------------
// ---- remove categories from research
// --------------------------------------------------------------------------------------
// function res_del_categories($id,$prop){
//     global $con;

//     $res = $id['res'];
//     $catList = $prop['list'];
//     $catList_str = implode(",",$catList);
//     $delete_where = "WHERE research_id = ".$res." AND collection_id IN(".$catList_str.")";

//     $sql = "DELETE FROM a_res_collections ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 15 in res_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_res_parts ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 16 in res_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_link_collections ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 17 in res_func.php: ' . mysqli_error($con));
//     }
// }

// --------------------------------------------------------------------------------------
// ---- update the automatic generated columns of a part (and its related parts)
// --------------------------------------------------------------------------------------
// function res_update_generated_columns($res,$part){
//     global $con;

//     $sql = "SELECT type,src_research,src_collection,src_from_position,src_to_position,src_from_word,src_to_word
//               FROM a_res_parts
//              WHERE research_id = ".$res."
//                AND part_id = ".$part;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 18 in res_func.php: ' . mysqli_error($con));
//     }
//     $row = mysqli_fetch_array($result);

//     if ($row['type'] == 'pointer'){
//         $sql1 = "SELECT src.gen_word_count - ".$row['src_to_word']." - 1 words_from_end
//                   FROM a_res_parts src
//                  WHERE src.research_id = ".$row['src_research']."
//                    AND src.collection_id = ".$row['src_collection']."
//                    AND src.position = ".$row['src_to_position'];
//         $result1 = mysqli_query($con,$sql1);
//         if (!$result1) {
//             exit_error('Error 19 in res_func.php: ' . mysqli_error($con));
//         }
//         $row1 = mysqli_fetch_array($result1);

//         $sql2 = "SELECT SUM(src.gen_word_count) - ".$row['src_from_word']." - ".$row1['words_from_end']." word_count
//                   FROM a_res_parts src
//                  WHERE src.research_id = ".$row['src_research']."
//                    AND src.collection_id = ".$row['src_collection']."
//                    AND src.position BETWEEN ".$row['src_from_position']." AND ".$row['src_to_position'];
//         $result2 = mysqli_query($con,$sql2);
//         if (!$result2) {
//             exit_error('Error 20 in res_func.php: ' . mysqli_error($con));
//         }
//         $row2 = mysqli_fetch_array($result2);

//         $sql3 = "UPDATE a_res_parts 
//                     SET gen_word_count = ".$row2['word_count']."
//                  WHERE research_id = ".$res."
//                    AND part_id = ".$part;
//         $result3 = mysqli_query($con,$sql3);
//         if (!$result3) {
//             exit_error('Error 21 in res_func.php: ' . mysqli_error($con));
//         }
//     }
// }

// --------------------------------------------------------------------------------------
// ---- get collection properties
// --------------------------------------------------------------------------------------
// function rescol_get($id){
//     global $con;

//     $sql = "SELECT type, name_heb name, description desc
//               FROM a_res_collections
//              WHERE research_id = ".$id['res']."
//                AND collection_id = ".$id['col'];
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 22 in res_func.php: ' . mysqli_error($con));
//     }
//     $row = mysqli_fetch_array($result);
//     $name = $row['name'];

//     $attr = array(
//         'type'=>$type,
//         'name'=>$name,
//         'desc'=>$desc
//     );
//     return $attr;
// }

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
// function rescol_get_indexes($id){
//     global $con;

//     $list = array();
//     $sql = "SELECT index_id, name_heb name, description_heb description
//               FROM a_res_indexes
//              WHERE research_id = ".$id['res']."
//                AND collection_id = ".$id['col']."
//              ORDER BY index_id";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 23 in res_func.php: ' . mysqli_error($con));
//     }
//     while($row = mysqli_fetch_array($result)) {
//         array_push($list,array(
//             "id"=>$row['index_id'],
//             "name"=>$row['name'],
//             "desc"=>$row['description']
//         ));
//     }
//     return $list;
// }

// --------------------------------------------------------------------------------------
// ---- get index properties
// --------------------------------------------------------------------------------------
function residx_get($id){
    global $con;

    $sql = "SELECT name_heb name
              FROM a_res_indexes
             WHERE research_id = ".$id['res']."
               AND collection_id = ".$id['col']."
               AND index_id = ".$id['idx'];
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 22 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $name = $row['name'];

    // $sql = "SELECT COUNT(*) key_levels
    //           FROM a_res_idx_levels
    //          WHERE research_id = ".$id['res']."
    //            AND collection_id = ".$id['col']."
    //            AND index_id = ".$id['idx']."
    //            AND part_of_key = TRUE";
    // $result = mysqli_query($con,$sql);
    // if (!$result) {
    //     exit_error('Error 22 in res_func.php: ' . mysqli_error($con));
    // }
    // $row = mysqli_fetch_array($result);
    // $key_levels = $row['key_levels'];


    $level_list = residx_get_levels($id,array('dummy'=>''));

    $attr = array(
        'name'=>$name,
        'levels'=>$level_list
    );

    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- get levels in index                                    
// --------------------------------------------------------------------------------------
function residx_get_levels($id,$prop){
    global $con;

    $key_pred = '';
    if (array_key_exists('levels',$prop)){
        if ($prop['levels'] == 'key'){
            $key_pred = 'AND part_of_key = TRUE';
        }
    }

    $list = array();
    $sql = "SELECT level,whole_name_heb whole_name,unit_name_heb unit_name,part_of_key
              FROM a_res_idx_levels
             WHERE research_id = ".$id['res']."
               AND collection_id = ".$id['col']."
               AND index_id = ".$id['idx']."
               ".$key_pred."
             ORDER BY level desc";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 28 in res_func.php: ' . mysqli_error($con));
    }
    while($row = mysqli_fetch_array($result)) {
        array_push($list,array(
            "id"=>(int)$row['level'],
            "whole_name"=>$row['whole_name'],
            "unit_name"=>$row['unit_name'],
            "part_of_key"=>($row['part_of_key'] == 1)
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ----                
// --------------------------------------------------------------------------------------
function residx_get_max_level($id){
    global $con;

    $sql = "SELECT MAX(level) level
              FROM a_res_idx_levels
             WHERE research_id = ".$id['res']."
               AND collection_id = ".$id['col']."
               AND index_id = ".$id['idx'];
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 32 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    return $row['level'];
}

// --------------------------------------------------------------------------------------
// ----                                   
// --------------------------------------------------------------------------------------
function residx_get_divisions($id,$prop){
    global $con;

    $key = $prop['key'];
    $divs = array();

    $parent_div = -999;
    foreach ($key as $level){
        $level_prop = array(
            'level'=>$level['level'],
            'selected_div'=>$level['division_id']
        );
        if ($parent_div != -999){
            $level_prop['parent_div'] = $parent_div;
        }
        $divisions = residx_get_level_divisions($id,$level_prop)['list'];

        $selected_div = $level['division_id'];
        if ($selected_div == 0){
            $selected_div = $divisions[0]['id'];
        } elseif ($selected_div == -1){
            $selected_div = end($divisions)['id'];
        }

        $level_divs = array(
            'level'=>$level['level'],
            'divisions'=>$divisions,
            'selected_div'=>$selected_div
        );
        array_push($divs,$level_divs);

        $parent_div = $selected_div;
    }

    return $divs;
}

// --------------------------------------------------------------------------------------
// ---- get divisions in level of index                                   
// --------------------------------------------------------------------------------------
function residx_get_level_divisions($id,$prop){
    global $con;

    $parent_div = "";
    if (array_key_exists('parent_div',$prop)){
        $sql = "SELECT from_position,to_position
                  FROM a_res_idx_division
                 WHERE research_id = ".$id['res']." 
                   AND division_id = ".$prop['parent_div'];
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 24 in res_func.php: ' . mysqli_error($con));
        }
        $row = mysqli_fetch_array($result);
        $parent_div = " AND from_position >= ".$row['from_position']." AND to_position <= ".$row['to_position'];
    }

    $list = array();
    $sql = "SELECT division_id,name_heb name 
              FROM a_res_idx_division
             WHERE research_id = ".$id['res']." 
               AND collection_id = ".$id['col']." 
               AND index_id = ".$id['idx']." 
               AND level = ".$prop['level']."
               ".$parent_div."
             ORDER BY from_position";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 29 in res_func.php: ' . mysqli_error($con));
    }
    while($row = mysqli_fetch_array($result)) {
        array_push($list,array(
            "id"=>(int)$row['division_id'],
            "name"=>$row['name'],
            "selected"=>($row['division_id'] == $prop['selected_div'])
        ));
    }
    return array('list'=>$list);
}

// --------------------------------------------------------------------------------------
// ---- convert position to index key
// --------------------------------------------------------------------------------------
function residx_position_to_key($id,$prop){
    global $con;

    $list = array();
    if ($prop['position'] > 0){
        $sql = "SELECT d.level,d.division_id
                  FROM a_res_idx_division d
                  JOIN a_res_idx_levels l
                    ON l.research_id = d.research_id
                   AND l.collection_id = d.collection_id
                   AND l.index_id = d.index_id
                   AND l.level = d.level
                   AND l.part_of_key = TRUE
                 WHERE d.research_id = ".$id['res']." 
                   AND d.collection_id = ".$id['col']." 
                   AND d.index_id = ".$id['idx']." 
                   AND ".$prop['position']." BETWEEN d.from_position AND d.to_position
                 ORDER BY d.level DESC";
    } else {
        if ($prop['position'] == 0){
            $group_func = 'MIN';
        } else {
            $group_func = 'MAX';
        }
        $sql = "SELECT d.level,".$group_func."(d.division_id) division_id
                  FROM a_res_idx_division d
                  JOIN a_res_idx_levels l
                    ON l.research_id = d.research_id
                   AND l.collection_id = d.collection_id
                   AND l.index_id = d.index_id
                   AND l.level = d.level
                   AND l.part_of_key = TRUE
                 WHERE d.research_id = ".$id['res']." 
                   AND d.collection_id = ".$id['col']." 
                   AND d.index_id = ".$id['idx']." 
                 GROUP BY d.level
                 ORDER BY d.level DESC";
    }

    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 30 in res_func.php: ' . mysqli_error($con));
    }
    while($row = mysqli_fetch_array($result)) {
        // array_push($list,$row['division_id']);
        array_push($list,array(
            "level"=>(int)$row['level'],
            "division_id"=>(int)$row['division_id']
        ));
    }
    return array('list'=>$list);
}

// --------------------------------------------------------------------------------------
// ---- get list of collections of type 'sequence' in all researches
// --------------------------------------------------------------------------------------
// function res_get_sequences_list(){
//     global $con;

//     $list = array();
//     $sql = "SELECT research_id,collection_id,name_heb name
//               FROM a_res_collections 
//              WHERE type = 'sequence'";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 25 in res_func.php: ' . mysqli_error($con));
//     }
//     while($row = mysqli_fetch_array($result)) {
//         array_push($list,array(
//             "research_id"=>$row['research_id'],
//             "collection_id"=>$row['collection_id'],
//             "name"=>$row['name']
//         ));
//     }

//     return $list;
// }

?>