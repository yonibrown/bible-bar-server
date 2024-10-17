<?php
// --------------------------------------------------------------------------------------
//
//    board                                     
//
// --------------------------------------------------------------------------------------
function elmbrd_get($id)
{
    global $con;

    $attr = array(
        'fields' => elmbrd_get_fields($id),
        'lines' => elmbrd_get_lines($id)
    );

    return $attr;
}

// --------------------------------------------------------------------------------------
//
// --------------------------------------------------------------------------------------
function elmbrd_get_fields($id)
{
    global $con;

    $sql = "SELECT field_id, title,field_type,width_pct,position
            FROM a_proj_elm_board_fields
            WHERE project_id = " . $id['proj'] . "
              AND element_id = " . $id['elm'] . "
              AND position > 0
            ORDER BY position";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 10 in elm_func.php: ' . mysqli_error($con));
    }
    $fields = array();
    while ($row = mysqli_fetch_array($result)) {
        array_push($fields, array(
            'id' => (int)$row['field_id'],
            'position' => (float)$row['position'],
            'title' => $row['title'],
            'type' => $row['field_type'],
            'width_pct' => (int)$row['width_pct']
        ));
    };

    return $fields;
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function elmbrd_get_lines($id)
{
    global $con;

    $sql = "SELECT li.line_id,li.position
              FROM a_proj_elm_board_lines li 
             WHERE li.project_id = " . $id['proj'] . "
               AND li.element_id = " . $id['elm'] . "
               AND position > 0
             ORDER BY li.position";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 10 in elm_func.php: ' . mysqli_error($con));
    }
    $lines = array();
    while ($row = mysqli_fetch_array($result)) {

        array_push($lines, array(
            'id' => (int)$row['line_id'],
            'position' => (float)$row['position'],
            "content" => elmbrd_get_content($id, $row['line_id'])
        ));
    };

    return $lines;
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function brd_add_line($id, $prop)
{
    global $con;

    $sql = "SELECT MAX(line_id) line_id
              FROM a_proj_elm_board_lines
             WHERE project_id = " . $id['proj'] . "
               AND element_id = " . $id['elm'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 2 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $lineId = $row['line_id'] + 1;

    $sql = "INSERT INTO a_proj_elm_board_lines
                (project_id, element_id, line_id, position) 
            VALUES(" . $id['proj'] . ", 
                " . $id['elm'] . ", 
                " . $lineId . ",
                " . $prop['position'] . ")";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 3 in elm_func.php: ' . mysqli_error($con));
    }
    return array(
        'id' => $lineId,
        'position' => $prop['position'],
        "content" => array()
    );
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function brd_add_field($id, $prop)
{
    global $con;

    $sql = "SELECT MAX(field_id) field_id
              FROM a_proj_elm_board_fields
             WHERE project_id = " . $id['proj'] . "
               AND element_id = " . $id['elm'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 2 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $fieldId = $row['field_id'] + 1;

    $sql = "INSERT INTO a_proj_elm_board_fields
                (project_id, element_id, field_id, position,
                 title, field_type, width_pct) 
            VALUES(" . $id['proj'] . ", 
                " . $id['elm'] . ", 
                " . $fieldId . ",
                " . $prop['position'] . ",
                'חדש','freeText',10)";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 3 in elm_func.php: ' . mysqli_error($con));
    }
    return array(
        'id' => $fieldId,
        'position' => $prop['position'],
        'title' => 'חדש',
        'type' => 'text',
        "width_pct" => 10
    );
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function elmbrd_get_content($id, $lineId)
{
    global $con;

    $sql = "SELECT field_id,text,
                   src_research, src_collection, 
                   src_from_division, src_from_word, src_to_division, src_to_word,
                   gen_from_name, gen_to_name 
              FROM a_proj_elm_board_content  
             WHERE project_id = " . $id['proj'] . "
               AND element_id = " . $id['elm'] . "
               AND line_id = " . $lineId . "
             ORDER BY field_id";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 10 in elm_func.php: ' . mysqli_error($con));
    }
    $content = array();
    while ($row = mysqli_fetch_array($result)) {

        array_push($content, array(
            'field' => (int)$row['field_id'],
            'text' => $row['text'],
            'src_research' => $row['src_research'],
            'src_collection' => $row['src_collection'],
            'src_from_division' => $row['src_from_division'],
            'src_from_word' => $row['src_from_word'],
            'src_to_division' => $row['src_to_division'],
            'src_to_word' => $row['src_to_word'],
            'src_from_name' => $row['gen_from_name'],
            'src_to_name' => $row['gen_to_name']
        ));
    };

    return $content;
}

// --------------------------------------------------------------------------------------
// ---- set attributes
// --------------------------------------------------------------------------------------
function brdfld_set_field($id, $prop)
{
    global $con;

    $sql_set = '';
    $sep = '';

    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "sort":
            case "ordering":
                $sql_set .= $sep . $attr . " = '" . $val . "'";
                $sep = ',';
                break;
            case "position":
            case "width_pct":
                $sql_set .= $sep . $attr . " = " . (int)$val;
                $sep = ',';
                break;
        }
    }

    if ($sql_set != '') {
        $sql = "UPDATE a_proj_elm_board_fields 
                SET " . $sql_set . "  
                WHERE project_id = " . $id['proj'] . "
                AND element_id = " . $id['elm'] . "
                AND field_id = " . $id['field'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 16 in elm_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- set attributes
// --------------------------------------------------------------------------------------
function brdcnt_set_content($id, $prop)
{
    global $con;

    $sql_set = '';
    $sep = '';

    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "text":
                $sql_set .= $sep . $attr . " = '" . $val . "'";
                $sep = ',';
                break;
            case "src_from_division":
            case "src_from_word":
            case "src_to_division":
            case "src_to_word":
                $sql_set .= $sep . $attr . " = '" . $val . "'";
                $sep = ',';
                break;
            case "src_from_name":
                $sql_set .= $sep . "gen_from_name = '" . $val . "'";
                $sep = ',';
                break;
            case "src_to_name":
                $sql_set .= $sep . "gen_to_name = '" . $val . "'";
                $sep = ',';
                break;
        }
    }

    if ($sql_set != '') {
        $sql = "UPDATE a_proj_elm_board_content 
                   SET " . $sql_set . "  
                 WHERE project_id = " . $id['proj'] . "
                   AND element_id = " . $id['elm'] . "
                   AND line_id = " . $id['line'] . "
                   AND field_id = " . $id['field'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 16 in elm_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- set attributes
// --------------------------------------------------------------------------------------
function brdlin_new_content($id, $prop)
{
    global $con;

    $text = '';
    $fromDiv = 0;
    $fromWord = 0;
    $fromName = '';
    $toDiv = 0;
    $toWord = 999;
    $toName = '';

    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "text":
                $text = $val;
                break;
            case "src_from_division":
                $fromDiv = $val;
                break;
            case "src_from_word":
                $fromWord = $val;
                break;
            case "src_to_division":
                $toDiv = $val;
                break;
            case "src_to_word":
                $toWord = $val;
                break;
            case "src_from_name":
                $fromName = $val;
                break;
            case "src_to_name":
                $toName = $val;
                break;
        }
    }

    $sql = "INSERT INTO a_proj_elm_board_content 
                (project_id, element_id, line_id, field_id, text,
                src_research, src_collection, src_from_division, src_from_word, 
                src_to_division, src_to_word,gen_from_name,gen_to_name)
                VALUES(" . $id['proj'] . ",
                        " . $id['elm'] . ",
                        " . $id['line'] . ",
                        " . $prop['field'] . ",
                        '" . $text . "',
                        1,1,".$fromDiv.",
                        ".$fromWord.",".$toDiv.",".$toWord.",
                        '".$fromName."','".$toName."')";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 16 in elm_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- set attributes
// --------------------------------------------------------------------------------------
function brdlin_set_line($id, $prop)
{
    global $con;

    $sql_set = '';
    $sep = '';

    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "position":
                $sql_set .= $sep . $attr . " = " . $val;
                $sep = ',';
                break;
        }
    }

    if ($sql_set != '') {
        $sql = "UPDATE a_proj_elm_board_lines 
                   SET " . $sql_set . "  
                 WHERE project_id = " . $id['proj'] . "
                   AND element_id = " . $id['elm'] . "
                   AND line_id = " . $id['line'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 16 in elm_func.php: ' . mysqli_error($con));
        }
    }
}
