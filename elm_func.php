<?php

// --------------------------------------------------------------------------------------
// ---- get element                                     
// --------------------------------------------------------------------------------------
function elm_get($id)
{
    return elm_prop($id, elm_get_basic($id));
}

// --------------------------------------------------------------------------------------
// ---- get element                                     
// --------------------------------------------------------------------------------------
function elm_get_basic($id)
{
    global $con;

    $sql = "SELECT type, name, description, opening_element,
                   tab_id tab, position, y_addition, open_text_element
            FROM a_proj_elements e
            WHERE e.project_id = " . $id['proj'] . "
              AND e.element_id = " . $id['elm'];
    $result = mysqli_query($con, $sql);
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
function elm_prop($id, $prop)
{
    $name = $prop['name'];
    switch ($prop['type']) {
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
        case 'board':
            $spc_attr = elmbrd_get($id);
            break;
        default:
            $spc_attr = array();
    }

    if (array_key_exists('name', $spc_attr)) {
        $name = $spc_attr['name'];
    }

    $openTextElm = 0;
    if (array_key_exists('open_text_element', $prop)) {
        $openTextElm = $prop['open_text_element'];
    }

    $yAddition = 0;
    if (array_key_exists('y_addition', $prop)) {
        $yAddition = (int)$prop['y_addition'];
    }


    return array(
        "id" => (int)$id['elm'],
        "proj" => (int)$id['proj'],
        "type" => $prop['type'],
        "name" => $name,
        "tab" => (int)$prop['tab'],
        "position" => (float)$prop['position'],
        "y_addition" => $yAddition,
        "attr" => $spc_attr,
        "open_text_element" => (int)$openTextElm
    );
}

// --------------------------------------------------------------------------------------
// ---- create new element
// --------------------------------------------------------------------------------------
function elm_create($prop)
{
    global $con;

    $proj = $prop['proj'];
    $type = $prop['type'];

    $sql = "SELECT MAX(element_id) element_id
              FROM a_proj_elements
             WHERE project_id = " . $proj;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 2 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $elm = $row['element_id'] + 1;

    $openingElm = 0;
    if (array_key_exists('opening_element', $prop)) {
        $openingElm = $prop['opening_element'];
    }

    $sql = "INSERT INTO a_proj_elements
                (project_id, element_id, type, 
                 name, description, position, y_addition,
                 show_props, opening_element) 
            VALUES(" . $proj . ", 
                " . $elm . ", 
                '" . $type . "',
                '" . $prop['name'] . "',' ',
                " . $prop['position'] . ",0, 
                0,
                " . $openingElm . ")";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 3 in elm_func.php: ' . mysqli_error($con));
    }

    $id = array('proj' => $proj, 'elm' => $elm);

    if ($openingElm != 0) {
        $sql = "INSERT INTO a_proj_link_elements
                    (project_id, link_id, element_id) 
                SELECT project_id, link_id, $elm
                  FROM a_proj_link_elements
                 WHERE project_id = " . $proj . "
                   AND element_id = " . $openingElm;
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 21 in elm_func.php: ' . mysqli_error($con));
        }

        if ($type == "text") {
            elm_set_basic(array(
                "proj" => $proj,
                "elm" => $openingElm
            ), array(
                "open_text_element" => $elm
            ));
        }
    }

    if (array_key_exists('links', $prop)) {
        foreach ($prop['links'] as $link_obj) {
            lnk_add_element($link_obj, array("elm" => $elm));
        }
    }

    $resp = array();
    switch ($type) {
        case 'bar':
            bar_create($id, $prop);
            break;
        case 'text':
            $resp = txt_create($id, $prop);
            break;
        case 'link':
            elmlnk_create($id, $prop);
            break;
            // case 'research':
            //     elmres_create($id,$prop);
            //     break;
        case 'parts':
            $resp = elmprt_create($id, $prop);
            break;
        case 'board':
            $resp = elmbrd_create($id, $prop);
            break;
    }

    $upd = array();
    $rep = array("elm" => elm_prop($id, $prop));
    foreach ($resp as $attr => $val) {
        switch ($attr) {
            case "res":
                $rep[$attr] = $val;
                break;
            case "name":
                $upd[$attr] = $val;
                $rep['elm'][$attr] = $val;
                break;
        }
    }

    elm_set_basic($id, $upd);

    return $rep;
}

// --------------------------------------------------------------------------------------
// ---- set element's attributes
// --------------------------------------------------------------------------------------
function elm_set($id, $prop)
{
    global $con;

    elm_set_basic($id, $prop);

    $elm_prop = elm_get_basic($id);
    switch ($elm_prop['type']) {
        case 'bar':
            elmseq_set($id, $prop);
            break;
        case 'text':
            txt_set($id, $prop);
            elmseq_set($id, $prop);
            break;
        case 'parts':
            elmprt_set($id, $prop);
            break;
        case 'board':
            elmbrd_set($id, $prop);
            break;
        case 'link':
            elmlnk_set($id, $prop);
            break;
    }

    return elm_get($id);
}

// --------------------------------------------------------------------------------------
// ---- set element's attributes
// --------------------------------------------------------------------------------------
function elm_set_basic($id, $prop)
{
    global $con;

    $sql_set = '';
    $sep = '';
    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "show_props":
                $sql_set .= $sep . $attr . " = " . ($val == 'true' ? 1 : 0);
                $sep = ',';
                break;
            case "open_text_element":
            case "position":
            case "y_addition":
                $sql_set .= $sep . $attr . " = " . $val;
                $sep = ',';
                break;
            case "name":
                $sql_set .= $sep . $attr . " = '" . $val . "'";
                $sep = ',';
                break;
        }
    }

    if ($sql_set != '') {
        $sql = "UPDATE a_proj_elements 
                SET " . $sql_set . "  
                WHERE project_id = " . $id['proj'] . "
                AND element_id = " . $id['elm'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 4 in elm_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function elm_links_changed($id)
{
    $elm_prop = elm_get($id);
    switch ($elm_prop['type']) {
        case 'bar':
            elmseq_set($id, array('points_generated' => FALSE));
            break;
    }
}

// --------------------------------------------------------------------------------------
// ---- get link element                                     
// --------------------------------------------------------------------------------------
function elmlnk_get($id)
{
    global $con;

    $sql = "SELECT link_id,link_display
            FROM a_proj_elm_link
            WHERE project_id = " . $id['proj'] . "
              AND element_id = " . $id['elm'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 6 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $attr = array(
        'link_id' => (int)$row['link_id'],
        'link_display' => $row['link_display']
    );

    $lnkProp = lnk_get_basic(array(
        "proj" => $id['proj'],
        "link" => $row['link_id']
    ));
    $attr['name'] = $lnkProp['name'];

    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- create new link element
// --------------------------------------------------------------------------------------
function elmlnk_create($id, $prop)
{
    global $con;

    // if (array_key_exists('link_id',$prop)){
    //     $link = $prop['link_id'];
    // } else {
    //     $link = lnk_create(array("proj"=>$id['proj']))['link'];
    // }

    $sql = "INSERT INTO a_proj_elm_link
                (project_id, element_id, link_id, link_display)
            VALUES(" . $id['proj'] . ", 
                   " . $id['elm'] . ", 
                   " . $prop['link'] . ",
                   'list')";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 7 in elm_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- create new research element
// --------------------------------------------------------------------------------------
function elmres_create($id, $prop)
{
    global $con;

    $sql = "INSERT INTO a_proj_elm_research
                (project_id, element_id, research_id)
            VALUES(" . $id['proj'] . ", 
                   " . $id['elm'] . ", 
                   '" . $prop['res'] . "')";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 9 in elm_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- get research points element                                     
// --------------------------------------------------------------------------------------
function elmprt_get($id)
{
    global $con;

    $sql = "SELECT research_id, tab,sort,ordering,
                   parts_col_width_pct, parts_src_width_pct
            FROM a_proj_elm_parts
            WHERE project_id = " . $id['proj'] . "
              AND element_id = " . $id['elm'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 10 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);

    $sort = 0;
    if ($row['sort'] == 'src') {
        $sort = 1;
    }

    $attr = array(
        'res' => (int)$row['research_id'],
        'tab' => $row['tab'],
        'sort' => (int)$sort,
        'ordering' => $row['ordering'],
        'col_width' => (int)$row['parts_col_width_pct'],
        'src_width' => (int)$row['parts_src_width_pct']
    );

    $resProp = res_get_basic(array(
        "res" => $row['research_id']
    ));
    $attr['name'] = $resProp['name'];

    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- create new research points element
// --------------------------------------------------------------------------------------
function elmprt_create($id, $prop)
{
    global $con;

    if (array_key_exists('res', $prop)) {
        $res = $prop['res'];
        $resObj = res_get(array("res" => $res));
    } else {
        $resProp = array(
            'proj' => $id['proj'],
            'name' => $prop['name'],
            'desc' => ''
        );
        $resObj = res_create($resProp);
        $res = $resObj['id'];
    }

    $sql = "INSERT INTO a_proj_elm_parts
                (project_id, element_id, research_id, tab,sort, ordering)
            VALUES(" . $id['proj'] . ", 
                   " . $id['elm'] . ", 
                   " . $res . ", 
                   'parts','src','ASC')";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 11 in elm_func.php: ' . mysqli_error($con));
    }
    return array("res" => $resObj);
}

// --------------------------------------------------------------------------------------
// ---- set attributes
// --------------------------------------------------------------------------------------
function elmprt_set($id, $prop)
{
    global $con;

    $row = elmprt_get($id);

    $sql_set = '';
    $sep = '';

    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "sort":
                if ($val == 1) {
                    $sql_set .= $sep . $attr . " = 'src'";
                } else {
                    $sql_set .= $sep . $attr . " = 'col'";
                }
                $sep = ',';
                break;
            case "ordering":
            case "tab":
                $sql_set .= $sep . $attr . " = '" . $val . "'";
                $sep = ',';
                break;
            case "width_pct":
                if (array_key_exists('field_id', $prop)) {
                    switch ($prop['field_id']) {
                        case 0:
                            $sql_set .= $sep . "parts_col_width_pct = " . (int)$val;
                            $sep = ',';
                            break;
                        case 1:
                            $sql_set .= $sep . "parts_src_width_pct = " . (int)$val;
                            $sep = ',';
                            break;
                    }
                }
                break;
        }
    }

    if ($sql_set != '') {
        $sql = "UPDATE a_proj_elm_parts 
                SET " . $sql_set . "  
                WHERE project_id = " . $id['proj'] . "
                AND element_id = " . $id['elm'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 16 in elm_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- set attributes
// --------------------------------------------------------------------------------------
function elmlnk_set($id, $prop)
{
    global $con;

    $row = elmlnk_get($id);

    $sql1_set = '';
    $sep1 = '';
    $sql2_set = '';
    $sep2 = '';

    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "link_display":
                $sql1_set .= $sep1 . $attr . " = '" . $val . "'";
                $sep1 = ',';
                break;
        }
    }

    if ($sql1_set != '') {
        $sql1 = "UPDATE a_proj_elm_link 
                SET " . $sql1_set . "  
                WHERE project_id = " . $id['proj'] . "
                AND element_id = " . $id['elm'];
        $result1 = mysqli_query($con, $sql1);
        if (!$result1) {
            exit_error('Error 19 in elm_func.php: ' . mysqli_error($con));
        }
    }

    if ($sql2_set != '') {
        $sql2 = "UPDATE a_proj_links l
                SET " . $sql2_set . "  
                WHERE l.project_id = " . $id['proj'] . "
                AND l.link_id = " . $row['link_id'];
        $result2 = mysqli_query($con, $sql2);
        if (!$result2) {
            exit_error('Error 19 in elm_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- get bar/text element
// --------------------------------------------------------------------------------------
function elmseq_get($id)
{
    global $con;

    $row = elmseq_get_basic($id);

    $indexId = array(
        'res' => $row['research_id'],
        'col' => $row['collection_id'],
        'idx' => $row['index_id']
    );

    $fromPos = $row['from_position'];
    $fromKey = residx_position_to_key($indexId, array('position' => $fromPos));

    $toPos = $row['to_position'];
    $toKey = residx_position_to_key($indexId, array('position' => $toPos));

    $attr = array(
        'research_id' => (int)$row['research_id'],
        'collection_id' => (int)$row['collection_id'],
        'from_position' => (float)$fromPos,
        'to_position' => (float)$toPos,
        'seq_index' => (int)$row['index_id'],
        'seq_level' => (int)$row['seq_level'],
        'color_level' => (int)$row['color_level'],
        'from_key' => $fromKey,
        'to_key' => $toKey,
        'anchor_position' => (float)$row['anchor_position'],
        'anchor_word' => (int)$row['anchor_word'],
        'numbering' => $row['numbering'],
        'segments_generated' => ($row['segments_generated'] == '1'),
        'points_generated' => ($row['points_generated'] == '1'),
        'total_words' => (int)$row['gen_total_words']
    );

    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function elmseq_get_basic($id)
{
    global $con;

    $sql = "SELECT e.research_id, e.collection_id,
                   e.from_position, e.to_position, 
                   e.seq_index index_id, e.seq_level, e.color_level, 
                   e.anchor_position, e.anchor_word, e.numbering,
                   e.segments_generated, e.points_generated, e.gen_total_words
            FROM a_proj_elm_sequence e
            WHERE e.project_id = " . $id['proj'] . " 
              AND e.element_id = " . $id['elm'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 15 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);

    return $row;
}

// --------------------------------------------------------------------------------------
// ---- set text attributes
// --------------------------------------------------------------------------------------
function elmseq_set($id, $prop)
{
    global $con;

    $sql_set = '';
    $sep = '';

    if (!array_key_exists('research_id', $prop) || !array_key_exists('collection_id', $prop)) {
        $row = elmseq_get_basic($id);
    }
    if (array_key_exists('research_id', $prop)) {
        $row['research_id'] = $prop['research_id'];
    }
    if (array_key_exists('collection_id', $prop)) {
        $row['collection_id'] = $prop['collection_id'];
    }

    $cancel_generated = FALSE;
    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "division_id":
            case "from_div":
            case "to_div":
                $row2 = residx_get_division(array(
                    "research_id" => $row['research_id'],
                    "collection_id" => $row['collection_id'],
                    "division_id" => $val
                ));

                if ($attr == "division_id" || $attr == "from_div") {
                    $sql_set .= $sep . "from_position = " . $row2['from_position'];
                    $sep = ',';
                }
                if ($attr == "division_id" || $attr == "to_div") {
                    $sql_set .= $sep . "to_position = " . $row2['to_position'];
                    $sep = ',';
                }
                if ($attr == "division_id") {
                    $level = $row2['level'] - 1;
                    $sql_set .= $sep . "seq_level = " . $level;
                    $sep = ',';
                }

                $cancel_generated = TRUE;
                break;

            case "research_id":
            case "collection_id":
            case "seq_index":
            case "seq_level":
                $sql_set .= $sep . $attr . " = " . $val;
                $sep = ',';
                $cancel_generated = TRUE;
                break;

            case "numbering":
                $sql_set .= $sep . $attr . " = '" . $val . "'";
                $sep = ',';
                break;

            case "segments_generated":
            case "points_generated":
                $sql_set .= $sep . $attr . " = " . ($val ? "TRUE" : "FALSE");
                $sep = ',';
                break;
        }
    }

    if ($cancel_generated) {
        $sql_set .= ",segments_generated = FALSE
                     ,points_generated = FALSE
                     ,gen_total_words = 0";
    }

    if ($sql_set != '') {
        $sql = "UPDATE a_proj_elm_sequence 
                SET " . $sql_set . "  
                WHERE project_id = " . $id['proj'] . "
                AND element_id = " . $id['elm'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 18 in elm_func.php: ' . mysqli_error($con));
        }

        // if ($attr = "sequence_id"){
        //     txt_update_division_colors($id);
        // }
    }
}
