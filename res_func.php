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
function res_get_basic($id)
{
    global $con;

    $res = $id['res'];
    $sql = "SELECT name_heb name,description
            FROM a_researches
            WHERE research_id = " . $res;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 2 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $attr = array(
        'name' => $row['name'],
        'desc' => $row['description']
    );
    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_get($id)
{
    $row = res_get_basic($id);
    return res_prop($id, $row);
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_prop($id, $prop)
{
    return array(
        "id" => (int) $id['res'],
        "name" => $prop['name'],
        "collections" => res_get_col_list($id),
        "parts" => res_parts_prop($id, array())
    );
}

// --------------------------------------------------------------------------------------
// ---- set research attributes
// --------------------------------------------------------------------------------------
function res_set($id, $prop)
{
    global $con;

    $res = $id['res'];
    $sql_set = '';
    $sep = '';
    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "name":
                $sql_set = $sql_set . $sep . "name_heb = '" . $val . "'";
                $sep = ',';
                break;
        }
    }

    $sql = "UPDATE a_researches 
            SET " . $sql_set . "  
            WHERE research_id = " . $res;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 3 in res_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- create new research
// --------------------------------------------------------------------------------------
function res_create($prop)
{
    global $con;

    $sql = "SELECT MAX(research_id) research_id
                FROM a_researches";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 4 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $res = $row['research_id'] + 1;

    if (isset($prop['proj'])) {
        $proj = $prop['proj'];
    } else {
        $proj = 0;
    }

    $sql = "INSERT INTO a_researches
                (research_id, 
                name_heb, 
                description,
                project_id) 
            VALUES(" . $res . ", 
                '" . $prop['name'] . "', 
                '" . $prop['desc'] . "', 
                " . $proj . ")";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 5 in res_func.php: ' . mysqli_error($con));
    }
    return array(
        "id" => $res,
        "name" => $prop['name'],
        "desc" => $prop['desc'],
        "collections" => array()
    );
}

// --------------------------------------------------------------------------------------
// ---- get category list for research                                   
// --------------------------------------------------------------------------------------
function res_get_col_list($id)
{
    global $con;

    $res = $id['res'];
    $list = array();
    $sql = "SELECT collection_id id,name_heb name,description
            FROM a_res_collections
            WHERE research_id = " . $res . "
            ORDER BY collection_id";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 6 in res_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        array_push($list, array(
            "res" => (int) $res,
            "id" => (int) $row['id'],
            "name" => $row['name'],
            "description" => $row['description']
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_get_default_col($id)
{
    global $con;

    $res = $id['res'];
    $sql = "SELECT collection_id
            FROM a_res_collections
            WHERE research_id = " . $res . "
            ORDER BY collection_id
            LIMIT 1";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 6 in res_func.php: ' . mysqli_error($con));
    }
    if ($row = mysqli_fetch_array($result)) {
        $col = $row['collection_id'];
    } else {
        $col = res_new_collection($id)['id'];
    }

    return $col;
}

// --------------------------------------------------------------------------------------
// ---- get point list for research                                   
// --------------------------------------------------------------------------------------
function res_get_prt_list($id, $prop)
{
    global $con;

    $res = $id['res'];

    $sort_key = array(
        "CONCAT(LPAD(prt.collection_id,5,0),'_',LPAD(prt.position,11,0))",
        "CONCAT(LPAD(prt.src_from_position,11,0),'_',LPAD(prt.src_from_word,5,0))"
    );

    if (array_key_exists('ordering', $prop)) {
        $order = $prop['ordering'];
    } else {
        $order = 'ASC';
    }


    $order_by = "prt.src_from_position " . $order . ",prt.src_from_word " . $order;
    if (array_key_exists('sort', $prop)) {
        if ($prop['sort'] == 'col') {
            $order_by = " prt.collection_id " . $order . "," . $order_by;
        }
    }

    $filter = '';

    if (array_key_exists('part_id', $prop)) {
        $filter .= ' AND prt.part_id = ' . $prop['part_id'];
    }

    if (array_key_exists('collection_id', $prop)) {
        $filter .= ' AND prt.collection_id = ' . $prop['collection_id'];
    }

    $sql = "SET SQL_BIG_SELECTS=1";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 999 in res_func.php: ' . mysqli_error($con));
    }

    $sql = "SELECT prt.part_id, prt.collection_id, c.name_heb col_name, 
                   prt.src_research, prt.src_collection, 
                   prt.src_from_position, prt.src_to_position, prt.src_from_word, prt.src_to_word, 
                   prt.gen_from_name,prt.gen_to_name,prt.gen_from_text src_text,gen_to_text,gen_to_word_count,
                   " . $sort_key[0] . " sort_key_0,
                   " . $sort_key[1] . " sort_key_1
            FROM a_res_parts prt
            JOIN a_res_collections c
              ON c.research_id = prt.research_id
             AND c.collection_id = prt.collection_id
           WHERE prt.research_id = " . $res . "
           " . $filter . "
           ORDER BY " . $order_by;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 7 in res_func.php: ' . mysqli_error($con));
    }

    $list = array();
    while ($row = mysqli_fetch_array($result)) {
        $mark = sub_words($row['src_text'], $row['src_from_word'], $row['src_to_word']);

        array_push($list, array(
            "id" => (int) $row['part_id'],
            "col" => (int) $row['collection_id'],
            "col_name" => $row['col_name'],
            "text_before" => mb_substr($row['src_text'], 0, $mark['start']),
            "text_part" => $mark['text'],
            "text_after" => mb_substr($row['src_text'], $mark['end']),
            "src_name" => $row['gen_from_name'],
            "src_from_name" => $row['gen_from_name'],
            "src_to_name" => $row['gen_to_name'],
            "src_from_text" => $row['src_text'],
            "src_to_text" => $row['gen_to_text'],
            "src_to_word_count" => $row['gen_to_word_count'],
            "sort_key" => array(
                $row['sort_key_0'],
                $row['sort_key_1']
            ),
            "src_research" => $row['src_research'],
            "src_collection" => $row['src_collection'],
            "src_from_position" => $row['src_from_position'],
            "src_from_word" => $row['src_from_word'],
            "src_to_position" => $row['src_to_position'],
            "src_to_word" => $row['src_to_word']
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- create new category in research
// --------------------------------------------------------------------------------------
function res_new_collection($id, $prop=[])
{
    global $con;

    $res = $id['res'];
    $sql = "SELECT IFNULL(MAX(collection_id),0) col,
                    IFNULL(MAX(position),0) pos
                FROM a_res_collections 
                WHERE research_id = " . $res;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 8 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $col = $row['col'] + 1;
    $pos = $row['pos'] + 1;

    $name = 'ברירת מחדל';
    if (array_key_exists('name', $prop)) {
        $name = $prop['name'];
    }

    $desc = '';
    if (array_key_exists('description', $prop)) {
        $desc = $prop['description'];
    }

    $sql = "INSERT INTO a_res_collections
                (research_id, collection_id, type, position, name_eng, name_heb, description)
            VALUES (
                " . $res . ", 
                " . $col . ", 
                'list',
                " . $pos . ", 
                '" . $name . "', 
                '" . $name . "', 
                '" . $desc . "')";

    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 9 in res_func.php: ' . mysqli_error($con));
    }

    $catObj = array(
        "res" => (int) $res,
        "id" => (int) $col,
        "name" => $name,
        "description" => $desc
    );
    lnk_new_collection($catObj); // add collection to links

    return $catObj;
}


// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_update_collection($id, $prop)
{
    global $con;

    $res = $id['res'];
    $col = $prop['col'];
    $sql_set = '';
    $sep = '';
    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case 'name':
                $sql_set = $sql_set . $sep . "name_heb = '" . $val . "'";
                $sep = ',';
                break;
            case 'description':
                $sql_set = $sql_set . $sep . $attr . " = '" . $val . "'";
                $sep = ',';
                break;
        }
    }

    if ($sql_set != "") {
        $sql = "UPDATE a_res_collections 
                SET " . $sql_set . "  
                WHERE research_id = " . $res . "
                AND collection_id = " . $col;
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 37 in res_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- remove categories from research inlist
// --------------------------------------------------------------------------------------
function res_del_collections($id, $prop)
{
    global $con;

    $delete_where = "WHERE research_id = " . $id['res'] . " AND collection_id " . inList($prop['colList']);

    $sql = "DELETE FROM a_res_collections " . $delete_where;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 15 in res_func.php: ' . mysqli_error($con));
    }

    $sql = "DELETE FROM a_res_indexes " . $delete_where;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 15 in res_func.php: ' . mysqli_error($con));
    }

    $sql = "DELETE FROM a_res_idx_division " . $delete_where;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 15 in res_func.php: ' . mysqli_error($con));
    }

    $sql = "DELETE FROM a_res_idx_levels " . $delete_where;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 15 in res_func.php: ' . mysqli_error($con));
    }

    $sql = "DELETE FROM a_res_parts " . $delete_where;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 16 in res_func.php: ' . mysqli_error($con));
    }

    $sql = "DELETE FROM a_proj_link_collections " . $delete_where;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 17 in res_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- create new part in research
// --------------------------------------------------------------------------------------
function res_new_part($id, $prop)
{
    global $con, $reload;

    $res = $id['res'];
    $sql = "SELECT IFNULL(MAX(part_id),0) part,
                   IFNULL(MAX(position),0) pos
              FROM a_res_parts
             WHERE research_id = " . $res;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 10 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $part = $row['part'] + 1;
    $pos = $row['pos'] + 1;

    if (array_key_exists('collection_id', $prop)) {
        $col = $prop['collection_id'];
    } else {
        $col = res_get_default_col($id);
    }

    if (array_key_exists('src_research', $prop)) {
        $sql = "INSERT INTO a_res_parts
            (research_id, part_id, type, 
            collection_id, position,
            div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
            src_research, src_collection, 
            src_from_position, src_from_word, src_to_position, src_to_word, 
            gen_word_count, 
            text, comment, fields_generated) 
            VALUES(
            " . $res . "," . $part . ",'pointer',
            " . $col . "," . $pos . ",
            '','','','',
            " . $prop['src_research'] . "," . $prop['src_collection'] . ",
            " . $prop['src_from_position'] . "," . $prop['src_from_word'] . ",
            " . $prop['src_to_position'] . "," . $prop['src_to_word'] . ",
            0,'','',FALSE)";
    } else {
        $sql = "INSERT INTO a_res_parts
            (research_id, part_id, type, 
            collection_id, position,
            div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
            src_research, src_collection, 
            src_from_position, src_from_word, src_to_position, src_to_word, 
            gen_word_count, 
            text, comment, fields_generated)
            SELECT " . $res . "," . $part . ",'pointer',
            " . $col . "," . $pos . ",
            '','','','',
            research_id,collection_id,
            from_position," . $prop['src_from_word'] . ",
            to_position," . $prop['src_to_word'] . ",
            0,'','',FALSE
            FROM a_proj_elm_sequence
            WHERE project_id = " . $prop['project_id'] . "
            AND element_id = " . $prop['element_id'];
    }
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 11 in res_func.php: ' . mysqli_error($con));
    }

    res_update_generated_columns($res);

    $newParts = res_parts_prop($id, array(
        "part_id" => $part,
        "collection_id" => $col
    ));

    return array(
        "new_parts" => $newParts
    );
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_parts_prop($id, $prop)
{
    global $reload;

    if (array_key_exists('proj', $reload) && array_key_exists('collection_id', $prop)) {
        proj_objects_to_reload(array(
            "object_type" => "res_part",
            "action" => "new",
            "cat" => array(
                "res" => $id['res'],
                "col" => $prop['collection_id']
            )
        ));
    }

    return res_get_prt_list($id, $prop);
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_upd_parts($id, $prop)
{
    global $con, $reload;

    $res = $id['res'];

    $partArr = $prop['partList'];
    $updAttr = $prop['updAttr'];

    if (count($partArr) == 0 || count($updAttr) == 0) {
        return;
    }

    $sql_set = '';
    $sep = '';
    foreach ($updAttr as $attr => $val) {
        switch ($attr) {
            case 'collection_id':
                if ($val == 0) {
                    $cat = res_new_collection($id, array('name' => $updAttr['collection_name']))['id'];
                } else {
                    $cat = $val;
                }
                $sql_set = $sql_set . $sep . "rp." . $attr . " = " . $cat;
                $sep = ',';
                break;
            case 'src_from_word':
            case 'src_to_word':
                $sql_set = $sql_set . $sep . "rp." . $attr . " = " . $val;
                $sep = ',';
                break;
            case 'src_from_division':
                $sql_set = $sql_set . $sep . "rp.src_from_position = (
                    SELECT from_position 
                      FROM a_res_idx_division rd
                     WHERE rd.research_id = rp.src_research
                       AND rd.collection_id = rp.src_collection
                       AND rd.division_id = " . $val . " 
                )";
                $sep = ',';
                break;
            case 'src_to_division':
                $sql_set = $sql_set . $sep . "rp.src_to_position = (
                    SELECT to_position 
                      FROM a_res_idx_division rd
                      WHERE rd.research_id = rp.src_research
                      AND rd.collection_id = rp.src_collection
                      AND rd.division_id = " . $val . " 
                )";
                $sep = ',';
                break;
        }
    }

    if ($sql_set != '') {
        $sql = "UPDATE a_res_parts rp 
                SET " . $sql_set . ",fields_generated = FALSE  
                WHERE rp.research_id = " . $res . "
                  AND rp.part_id " . inList($partArr);
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 33 in res_func.php: ' . mysqli_error($con));
        }
    }

    res_update_generated_columns($res);

    if (array_key_exists('proj', $reload)) {
        proj_objects_to_reload(array(
            "object_type" => "res_part",
            "action" => "update",
            "cat" => array(
                "res" => $res
            )
        ));
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_delete_parts($id, $prop)
{
    global $con, $reload;

    $res = $id['res'];

    $partArr = $prop['partList'];

    $sql = "DELETE FROM a_res_parts 
            WHERE research_id = " . $res . "
              AND part_id " . inList($partArr);
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 38 in res_func.php: ' . mysqli_error($con));
    }

    if (array_key_exists('proj', $reload)) {
        proj_objects_to_reload(array(
            "object_type" => "res_part",
            "action" => "delete",
            "cat" => array(
                "res" => $res
            )
        ));
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_duplicate($id, $prop)
{
    global $con;

    $attr = res_get_basic($id);
    $attr['name'] = substr($attr['name'], 1, 15) . "-COPY";
    $newRes = res_create($attr);
    $newId = $newRes['id'];

    // copy parts
    $partArr = $prop['partList'];
    $sql = "INSERT INTO  a_res_parts 
                (research_id, part_id, type, collection_id, position, 
                div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
                text, comment, src_research, src_collection, 
                src_from_position, src_from_word, src_to_position, 
                src_to_word, gen_word_count, gen_from_text)
            SELECT " . $newId . ",ROW_NUMBER() OVER (ORDER BY part_id), 
                type, collection_id, position, 
                div_name_eng, div_name_heb, abs_name_heb, abs_name_eng, 
                text, comment, src_research, src_collection, 
                src_from_position, src_from_word, src_to_position, 
                src_to_word, gen_word_count, gen_from_text 
            FROM a_res_parts
            WHERE research_id = " . $id['res'] . "
              AND part_id " . inList($partArr);
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 35 in res_func.php: ' . mysqli_error($con));
    }

    //copy collections
    $sql = "INSERT INTO a_res_collections
                (research_id, collection_id, type, position, 
                name_eng, name_heb, description)
            SELECT " . $newId . ",collection_id, type, position, 
                name_eng, name_heb, description
            FROM a_res_collections
            WHERE research_id = " . $id['res'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 36 in res_func.php: ' . mysqli_error($con));
    }

    return array('new_res' => $newRes);

}

// --------------------------------------------------------------------------------------
// ---- update the automatic generated columns of a part (and its related parts)
// --------------------------------------------------------------------------------------
function res_update_generated_columns($res)
{
    global $con;

    $sql = "SELECT part_id,type,src_research,src_collection,src_from_position,src_to_position,src_from_word,src_to_word
              FROM a_res_parts
             WHERE research_id = " . $res . "
               AND fields_generated = FALSE
             ORDER BY part_id";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 18 in res_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        $part = $row['part_id'];

        $sql_from = "SELECT src.abs_name_heb name, src.text
                   FROM a_res_parts src
                  WHERE src.research_id = " . $row['src_research'] . "
                    AND src.collection_id = " . $row['src_collection'] . "
                    AND src.position = " . $row['src_from_position'];
        $result_from = mysqli_query($con, $sql_from);
        if (!$result_from) {
            exit_error('Error 19 in res_func.php: ' . mysqli_error($con));
        }
        $row_from = mysqli_fetch_array($result_from);

        $sql_to = "SELECT src.abs_name_heb name, src.text, src.gen_word_count word_count
                   FROM a_res_parts src
                  WHERE src.research_id = " . $row['src_research'] . "
                    AND src.collection_id = " . $row['src_collection'] . "
                    AND src.position = " . $row['src_to_position'];
        $result_to = mysqli_query($con, $sql_to);
        if (!$result_to) {
            exit_error('Error 19 in res_func.php: ' . mysqli_error($con));
        }
        $row_to = mysqli_fetch_array($result_to);

        if ($row['type'] == 'pointer') {
            $words_from_end = $row_to['word_count'] - $row['src_to_word'] - 1;
            // update 'gen_word_count'
            $sql2 = "SELECT SUM(src.gen_word_count) - " . $row['src_from_word'] . " - " . $words_from_end . " word_count
                    FROM a_res_parts src
                    WHERE src.research_id = " . $row['src_research'] . "
                    AND src.collection_id = " . $row['src_collection'] . "
                    AND src.position BETWEEN " . $row['src_from_position'] . " AND " . $row['src_to_position'];
            $result2 = mysqli_query($con, $sql2);
            if (!$result2) {
                exit_error('Error 20 in res_func.php: ' . mysqli_error($con));
            }
            $row2 = mysqli_fetch_array($result2);

            $sql3 = "UPDATE a_res_parts 
                        SET gen_word_count = " . $row2['word_count'] . "
                    WHERE research_id = " . $res . "
                    AND part_id = " . $part;
            $result3 = mysqli_query($con, $sql3);
            if (!$result3) {
                exit_error('Error 21 in res_func.php: ' . mysqli_error($con));
            }
        }

        $sql4 = "UPDATE a_res_parts prt
                SET gen_from_name = '" . $row_from['name'] . "' 
                    , gen_from_text = '" . $row_from['text'] . "'
                    , gen_to_name = '" . $row_to['name'] . "' 
                    , gen_to_text = '" . $row_to['text'] . "'
                    , gen_to_word_count = " . $row_to['word_count'] . "
                    , fields_generated = TRUE
                WHERE research_id = " . $res . "
                AND part_id = " . $part;
        $result4 = mysqli_query($con, $sql4);
        if (!$result4) {
            exit_error('Error 21 in res_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function res_DICTA_upload($id, $file)
{
    global $con;

    $residx_id = array(
        "res" => 1,
        "col" => 1,
        "idx" => 1
    );
    $colObj = res_new_collection($id, array("name" => "מקובץ"));

    $part_id = 0;
    // $fileArr = explode('תנך/',$file);
    $fileArr = preg_split("/\n/", $file);
    for ($file_i = 0; $file_i < count($fileArr); $file_i++) {
        // $lineArr = preg_split("/\n|\/|\)|\,/", $fileArr[$file_i]);
        $lineArr = preg_split("/\/|\,/", $fileArr[$file_i]);
        if (count($lineArr) >= 4) {
            $bibleRange = array("from" => 0, "to" => 999999999);

            //source
            $tanah = array_shift($lineArr);

            //division
            $division_heb = array_shift($lineArr);

            //book
            $book_heb = str_replace('ספר ', '', array_shift($lineArr));
            $bookRange = residx_get_level_range($residx_id, $book_heb, 2, $bibleRange);

            //chapter
            $chapter_heb = str_replace('פרק ', '', array_shift($lineArr));
            $chapterRange = residx_get_level_range($residx_id, $chapter_heb, 1, $bookRange);
            // $chapter = array_search($chapter_heb,$heb_num);

            //verses
            while (count($lineArr) > 0) {
                $nxt = array_shift($lineArr);
                if (str_contains($nxt, 'פסוק ')) {
                    $verse_heb = str_replace('פסוק ', '', $nxt);
                    if ($verse_heb != '') {
                        // $verse = array_search($verse_heb,$heb_num);
                        $verseRange = residx_get_level_range($residx_id, $verse_heb, 0, $chapterRange);
                        $text = array_shift($lineArr);
                        $text = str_replace('־', ' ', $text);
                        $text = str_replace('׀', ' ', $text);
                        $text = str_replace('* *', ' ', $text);
                        $textArr = explode('*', $text);

                        $toWord = 0;
                        while (count($textArr) > 0) {
                            $wordsBefore = preg_split("/\s+/", array_shift($textArr));
                            // $wordsBefore = explode(' ',array_shift($textArr));
                            // exit_error(count($wordsBefore));
                            $fromWord = $toWord + count($wordsBefore) - 1;

                            if (count($textArr) > 0) {
                                $wordsPart = explode(' ', array_shift($textArr));
                                $toWord = $fromWord + count($wordsPart) - 1;

                                // add_verse($book,$chapter,$verse,$text,$part_id);

                                res_new_part($id, array(
                                    "collection_id" => $colObj['id'],
                                    "src_research" => 1,
                                    "src_collection" => 1,
                                    "src_from_position" => $verseRange['from'],
                                    "src_from_word" => $fromWord,
                                    "src_to_position" => $verseRange['to'],
                                    "src_to_word" => $toWord
                                ));
                            }
                        }
                    }
                }
            }
        }
    }

    $newParts = res_parts_prop($id, array(
        "collection_id" => $colObj['id']
    ));

    return array(
        "new_collection" => $colObj,
        "new_parts" => $newParts
    );
}

?>