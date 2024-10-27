<?php
// --------------------------------------------------------------------------------------
//
//    board                                     
//
// --------------------------------------------------------------------------------------
function elmbrd_get($id)
{
    $fields = elmbrd_get_fields($id);
    $lines = elmbrd_get_lines($id, array("fields" => $fields));

    return array(
        'fields' => $fields,
        'lines' => $lines
    );
}

// --------------------------------------------------------------------------------------
//
// --------------------------------------------------------------------------------------
function elmbrd_get_fields($id)
{
    global $con;

    $sql = "SELECT field_id, title,field_type,width_pct,position,
                   parent_field,display_whole_verse,reference_style
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
            'width_pct' => (int)$row['width_pct'],
            'parent_field' => (int)$row['parent_field'],
            'display_whole_verse' => (int)$row['display_whole_verse'],
            'reference_style' => (int)$row['reference_style']
        ));
    };

    return $fields;
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function elmbrd_get_lines($id, $prop)
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
            "content" => brdlin_get_content(array(
                "proj" => $id['proj'],
                "elm" => $id['elm'],
                "line" => $row['line_id']
            ), $prop)
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

    $parentField = $fieldId;
    if (array_key_exists('parent_field', $prop)) {
        $parentField = $prop['parent_field'];
    }

    $sql = "INSERT INTO a_proj_elm_board_fields
                (project_id, element_id, field_id, position,
                 title, field_type, width_pct, parent_field,
                 display_whole_verse,reference_style) 
            VALUES(" . $id['proj'] . ", 
                " . $id['elm'] . ", 
                " . $fieldId . ",
                " . $prop['position'] . ",
                'חדש',
                '" . $prop['type'] . "',
                10,
                " . $parentField . ",
                TRUE,0)";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 3 in elm_func.php: ' . mysqli_error($con));
    }
    return array(
        'id' => $fieldId,
        'position' => $prop['position'],
        'title' => 'חדש',
        'type' => $prop['type'],
        "width_pct" => 10,
        "parent_field" => $parentField,
        "display_whole_verse" => true,
        "reference_style" => 0
    );
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function brdlin_get_content($id, $prop)
{
    global $con;

    $lineContent = array();
    foreach ($prop['fields'] as $field) {
        $cntId = array_merge($id, array("field" => $field['id']));
        if ($content = brdcnt_get_content($cntId)) {
            array_push($lineContent, $content);
        }
    }
    return $lineContent;
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function brdcnt_get_content_basic($id)
{
    global $con;

    $sql = "SELECT field_id,text,
                   src_research, src_collection, 
                   src_from_division, src_from_word, src_to_division, src_to_word,
                   fields_generated,
                   gen_from_name, gen_to_name, 
                   gen_from_position, gen_to_position, 
                   gen_from_text, gen_to_text 
              FROM a_proj_elm_board_content  
             WHERE project_id = " . $id['proj'] . "
               AND element_id = " . $id['elm'] . "
               AND line_id = " . $id['line'] . "
               AND field_id = " . $id['field'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 10 in elm_func.php: ' . mysqli_error($con));
    }
    if ($row = mysqli_fetch_array($result)) {
        $content = array(
            'field' => (int)$row['field_id'],
            'text' => $row['text'],
            'src_research' => (int)$row['src_research'],
            'src_collection' => (int)$row['src_collection'],
            'src_from_division' => (int)$row['src_from_division'],
            'src_from_word' => (int)$row['src_from_word'],
            'src_to_division' => (int)$row['src_to_division'],
            'src_to_word' => (int)$row['src_to_word'],
            'src_from_name' => $row['gen_from_name'],
            'src_to_name' => $row['gen_to_name'],
            'fields_generated' => $row['fields_generated'],
            'gen_from_position' => (float)$row['gen_from_position'],
            'gen_to_position' => (float)$row['gen_to_position'],
            'gen_from_text' => $row['gen_from_text'],
            'gen_to_text' => $row['gen_to_text']
        );
        return $content;
    };
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function brdcnt_get_content($id)
{
    $content = brdcnt_get_content_basic($id);

    if (!$content) {
        return null;
    }

    if (!$content['fields_generated']) {
        return brdcnt_update_generated_columns($id, $content);
    }
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
            case "title":
            case "sort":
            case "ordering":
                $sql_set .= $sep . $attr . " = '" . $val . "'";
                $sep = ',';
                break;
            case "position":
            case "width_pct":
            case "display_whole_verse":
            case "reference_style":
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

    // brdfld_set_parent_field($id, $prop);
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
            case "src_to_division":
                $sql_set .= $sep . $attr . " = " . $val;
                $sep = ',';
                $sql_set .= $sep . "fields_generated = FALSE";
                break;
            case "src_from_word":
            case "src_to_word":
                $sql_set .= $sep . $attr . " = " . $val;
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

    $updated_content = brdcnt_update_generated_columns($id);
    return $updated_content;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function brdcnt_update_generated_columns($id, $content = null)
{
    global $con;

    if (!$content) {
        $content = brdcnt_get_content_basic($id);
    }

    if ($content['src_research'] > 0 && $content['src_from_division'] > 0) {
        return brdcnt_update_generated_research($id, $content);
    }

    $sql1 = "UPDATE a_proj_elm_board_content
                SET fields_generated = TRUE
              WHERE project_id = " . $id['proj'] . "
                AND element_id = " . $id['elm'] . "
                AND line_id = " . $id['line'] . "
                AND field_id = " . $id['field'];
    $result1 = mysqli_query($con, $sql1);
    if (!$result1) {
        exit_error('Error 21 in board_func.php: ' . mysqli_error($con));
    }
    return $content;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function brdcnt_update_generated_research($id, $content)
{
    global $con;

    $div_from = residx_get_division(array(
        "research_id" => $content['src_research'],
        "collection_id" => $content['src_collection'],
        "division_id" => $content['src_from_division']
    ));
    $div_to = residx_get_division(array(
        "research_id" => $content['src_research'],
        "collection_id" => $content['src_collection'],
        "division_id" => $content['src_to_division']
    ));

    $from_position = $div_from['from_position'];
    $to_position = $div_to['to_position'];

    $sql_from = "SELECT src.abs_name_heb name, src.text
                   FROM a_res_parts src
                  WHERE src.research_id = " . $content['src_research'] . "
                    AND src.collection_id = " . $content['src_collection'] . "
                    AND src.position = " . $from_position;
    $result_from = mysqli_query($con, $sql_from);
    if (!$result_from) {
        exit_error('Error 19 in board_func.php: ' . mysqli_error($con));
    }
    $row_from = mysqli_fetch_array($result_from);

    $sql_to = "SELECT src.abs_name_heb name, src.text
                   FROM a_res_parts src
                  WHERE src.research_id = " . $content['src_research'] . "
                    AND src.collection_id = " . $content['src_collection'] . "
                    AND src.position = " . $to_position;
    $result_to = mysqli_query($con, $sql_to);
    if (!$result_to) {
        exit_error('Error 19 in board_func.php: ' . mysqli_error($con));
    }
    $row_to = mysqli_fetch_array($result_to);

    $sql1 = "UPDATE a_proj_elm_board_content
                SET gen_from_position = " . $from_position . "
                  , gen_to_position = " . $to_position . "
                  , gen_from_text = '" . $row_from['text'] . "'
                  , gen_to_text = '" . $row_to['text'] . "'
                  , fields_generated = TRUE
              WHERE project_id = " . $id['proj'] . "
                AND element_id = " . $id['elm'] . "
                AND line_id = " . $id['line'] . "
                AND field_id = " . $id['field'];
    $result1 = mysqli_query($con, $sql1);
    if (!$result1) {
        exit_error('Error 21 in board_func.php: ' . mysqli_error($con));
    }

    return array_merge($content, array(
        "gen_from_position" => (float)$from_position,
        "gen_to_position" => (float)$to_position,
        "gen_from_text" => $row_from['text'],
        "gen_to_text" => $row_to['text']
    ));
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

    foreach ($prop['content'] as $attr => $val) {
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
                        1,1," . $fromDiv . ",
                        " . $fromWord . "," . $toDiv . "," . $toWord . ",
                        '" . $fromName . "','" . $toName . "')";
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
