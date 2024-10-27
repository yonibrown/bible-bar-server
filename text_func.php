<?php
// --------------------------------------------------------------------------------------
// ---- create new textbox
// --------------------------------------------------------------------------------------
function txt_create($id, $prop)
{
    global $con;

    if (!array_key_exists('point_research_id', $prop) && !array_key_exists('division_id', $prop)) {
        $prop['point_research_id'] = 1;
        $prop['point_part_id'] = 1;
    }

    $attr = txt_gen_attr($id, $prop);

    $sql = "INSERT INTO a_proj_elm_sequence
                (project_id, element_id, 
                 research_id, collection_id, from_position, to_position, 
                 seq_index, seq_level, color_level, 
                 anchor_position, anchor_word, numbering)
            VALUES(" . $id['proj'] . "," . $id['elm'] . ", 
                   " . $attr['src_research'] . "," . $attr['src_collection'] . "," . $attr['from_position'] . "," . $attr['to_position'] . ",
                   1,0,0,
                   " . $attr['anchor_position'] . ",0,'letters')";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 2 in text_func.php: ' . mysqli_error($con));
    }

    $indexId = array(
        'res' => $attr['src_research'],
        'col' => $attr['src_collection'],
        'idx' => 1 // default index
    );

    $anchorKey = residx_position_to_key($indexId, array('position' => $attr['anchor_position']));

    $keyName = array_map("indexKeyName", $anchorKey);

    array_pop($keyName); // pop the verse from the key leaving the chapter and the book

    $chapterName = implode(" ", $keyName);

    return array("name" => $chapterName);

    // if ($attr['prt_research'] != $attr['src_research']){
    //     // create link between prt_research and text element
    //     elm_link_to_cat($id,array("res"=>$attr['prt_research'],"col"=>$attr['prt_collection']));
    // }
}

function indexKeyName($key)
{
    return $key['name'];
}
// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function txt_set($id, $prop)
{
    global $con;

    $attr = txt_gen_attr($id, $prop);
    if ($attr == null) {
        return;
    }

    $sql = "UPDATE a_proj_elm_sequence
               SET research_id = " . $attr['src_research'] . ", 
                   collection_id = " . $attr['src_collection'] . ", 
                   from_position = " . $attr['from_position'] . ", 
                   to_position = " . $attr['to_position'] . ", 
                   seq_index = 1, 
                   seq_level = 0, 
                   color_level = 0, 
                   anchor_position = " . $attr['anchor_position'] . ", 
                   anchor_word = 0,
                   numbering = 'letters'
             WHERE project_id = " . $id['proj'] . "
               AND element_id = " . $id['elm'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 12 in text_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ----                                     
// --------------------------------------------------------------------------------------
function txt_gen_attr($id, $prop)
{
    global $con;

    if (array_key_exists('division_id', $prop)) {
        // get parameters by division_id
        $sql = "SELECT src.research_id src_research,src.collection_id src_collection,
                       src.from_position,src.to_position,
                       src.research_id prt_research,
                       IFNULL(anc.from_position,0) anchor_position
                  FROM a_res_idx_division src
                  LEFT JOIN a_res_idx_division anc
                    ON anc.research_id = src.research_id
                   AND anc.collection_id = src.collection_id
                   AND anc.division_id = " . $prop['anchor_div'] . "
                   AND anc.from_position BETWEEN src.from_position AND src.to_position
                 WHERE src.research_id = " . $prop['research_id'] . "
                   AND src.collection_id = " . $prop['collection_id'] . "
                   AND src.division_id = " . $prop['division_id'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 1 in text_func.php: ' . mysqli_error($con));
        }
        $row = mysqli_fetch_array($result);
        return $row;
    }

    if (array_key_exists('point_research_id', $prop)) {
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
                 WHERE prt.research_id = " . $prop['point_research_id'] . "
                   AND prt.part_id = " . $prop['point_part_id'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 1 in text_func.php: ' . mysqli_error($con));
        }
        $row = mysqli_fetch_array($result);
        return $row;
    }

    return null;
}

// --------------------------------------------------------------------------------------
// ---- get chapter                                     
// --------------------------------------------------------------------------------------
function txt_get_segment($id)
{
    global $con;

    $rep = array();

    // get text properties
    $prop = elmseq_get($id);

    $sql = "SET SQL_BIG_SELECTS=1";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 999 in text_func.php: ' . mysqli_error($con));
    }

    $sql1 = "SELECT seq.part_id seq_part,seq.div_name_eng name_eng,seq.div_name_heb name_heb,seq.position seq_position,
                    src.position src_position,src.text
 			  FROM a_res_parts seq
              JOIN a_res_parts src
                ON src.research_id = seq.src_research
              AND src.collection_id = seq.src_collection
              AND src.position BETWEEN seq.src_from_position AND seq.src_to_position
		     WHERE seq.research_id = " . $prop['research_id'] . "
              AND seq.collection_id = " . $prop['collection_id'] . "
              AND seq.position BETWEEN " . $prop['from_position'] . " AND " . $prop['to_position'] . "
             GROUP BY seq.position,src.position,
                      seq.part_id,seq.div_name_heb,src.text
             ORDER BY seq.position,src.position";
    $result1 = mysqli_query($con, $sql1);
    if (!$result1) {
        exit_error('Error 5 in text_func.php: ' . mysqli_error($con));
    }

    $rep['part_list'] = array();
    while ($row1 = mysqli_fetch_array($result1)) {
        $text = $row1['text'];
        $src_pos = $row1['src_position'];

        $anchor_part = ($row1['seq_position'] == $prop['anchor_position']);

        $txt_list = verse_to_list($text, $anchor_part, $prop['anchor_word']);
        $partObj = array(
            "part_id" => $row1['seq_part'],
            "position" => $row1['seq_position'],
            "name_letter" => $row1['name_heb'],
            "name_number" => $row1['name_eng'],
            "txt_list" => $txt_list['list']
        );

        if ($anchor_part && !$txt_list['anchor_set']) {
            $partObj['anchor'] = true;
        }

        array_push($rep['part_list'], $partObj);
    }

    $rep["title"] = txt_get_title($id);

    return $rep;
}

// --------------------------------------------------------------------------------------
// ---- get title                                     
// --------------------------------------------------------------------------------------
function txt_get_title($id)
{
    global $con;

    $sql = "SELECT d.level,d.division_id,d.name2 name
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
              WHERE s.project_id = " . $id['proj'] . "
                AND s.element_id = " . $id['elm'] . "
                AND l.part_of_key = TRUE
              ORDER BY d.level desc";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 9 in text_func.php: ' . mysqli_error($con));
    }

    $title = "";
    $sep = "";
    while ($row = mysqli_fetch_array($result)) {
        $title .= $sep . $row['name'];
        $sep = "/";
    }

    return $title;
}
