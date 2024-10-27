<?php
// --------------------------------------------------------------------------------------
// ---- create new bar
// --------------------------------------------------------------------------------------
function bar_create($id, $prop)
{
    global $con;

    if (array_key_exists('research_id', $prop)) {
        $res = $prop['research_id'];
        $col = $prop['collection_id'];
    } else {
        $res = 1;
        $col = 1;
    }

    if (array_key_exists('from_position', $prop)) {
        $fromPos = $prop['from_position'];
    } else {
        $fromPos = 0;
    }

    if (array_key_exists('to_position', $prop)) {
        $toPos = $prop['to_position'];
    } else {
        $toPos = -1;
    }

    $max_level = residx_get_max_level(array(
        "res" => $res,
        "col" => $col,
        "idx" => 1
    ));

    $sql = "INSERT INTO a_proj_elm_sequence
                (project_id, element_id, 
                 research_id, collection_id, from_position, to_position, 
                 seq_index, seq_level, color_level, 
                 segments_generated, points_generated,gen_total_words) 
            VALUES(" . $id['proj'] . ", 
                " . $id['elm'] . ", 
                " . $res . ", 
                " . $col . ", 
                " . $fromPos . ", 
                " . $toPos . ", 
                1," . $max_level . "," . $max_level . ",
                FALSE,FALSE,0)";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 4 in bar_func.php: ' . mysqli_error($con));
    }

    // bar_update_division_colors($id);
    $sql = "INSERT INTO a_proj_elm_seq_divisions
                (project_id, element_id, ord, color1, color2) 
            SELECT " . $id['proj'] . ", " . $id['elm'] . ", ord, color1, color2
              FROM a_proj_elm_seq_divisions
             WHERE project_id = 1
               AND element_id = 1";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 18 in bar_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- calculate segments in bar for display
// --------------------------------------------------------------------------------------
function bar_calc_segments($id, $dsp)
{
    global $con;

    // get bar properties
    $prop = elmseq_get($id);

    // get bar colors
    $sql = "SELECT ord,color1,color2
              FROM a_proj_elm_seq_divisions
             WHERE project_id = " . $id['proj'] . "
               AND element_id = " . $id['elm'] . "
             ORDER BY ord";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 5 in bar_func.php: ' . mysqli_error($con));
    }
    $colorOrd = 1;
    $colors1 = array();
    $colors2 = array();
    while ($row = mysqli_fetch_array($result)) {
        $colors1[$colorOrd] = $row['color1'];
        $colors2[$colorOrd] = $row['color2'];
        $colorOrd++;
    }

    // get colors' max positions
    $sql = "SELECT to_position max_pos
              FROM a_res_idx_division
             WHERE research_id = " . $prop['research_id'] . "
               AND collection_id = " . $prop['collection_id'] . "
               AND level = " . $prop['color_level'] . "
             ORDER BY division_id";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 14 in bar_func.php: ' . mysqli_error($con));
    }
    $colorOrd = 1;
    while ($row = mysqli_fetch_array($result)) {
        $colorMaxPos[$colorOrd] = $row['max_pos'];
        $colorOrd++;
    }

    // prepare queries
    $base_table = bar_base_table($prop);
    $base_where = bar_base_where($prop);

    $rep = array();
    $rep['segments'] = array();

    // get total verses in selection
    $total_words = bar_get_selection_size($id, $prop, $base_table);

    $sql = "SET SQL_BIG_SELECTS=1";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 7 in bar_func.php: ' . mysqli_error($con));
    }

    if ($prop['segments_generated'] == TRUE) {
        $sql = "SELECT d.division_id,d.name,d.width_pct, d.to_position to_pos
              FROM g_proj_elm_segments d
             WHERE d.project_id = " . $id['proj'] . "
               AND d.element_id = " . $id['elm'] . "
             ORDER BY d.to_position,d.division_id";
    } else {
        bar_init_gen_segments($id);
        $sql = "SELECT d.division_id,d.name2 name, SUM(base.gen_word_count) words, 
                       d.to_position to_pos
              FROM a_res_parts base
              JOIN a_res_idx_division d
                ON base.research_id = d.research_id
               AND base.collection_id = d.collection_id
               AND base.position BETWEEN d.from_position AND d.to_position
             WHERE " . $base_where . "
               AND d.index_id = " . $prop['seq_index'] . "
               AND d.level = " . $prop['seq_level'] . "
             GROUP BY d.to_position,d.division_id,d.name2";
    }
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 8 in bar_func.php: ' . mysqli_error($con));
    }

    $colorOrd = 1;
    $color = "";
    while ($row = mysqli_fetch_array($result)) {
        if ($prop['segments_generated'] == FALSE) {
            $sg_size = get_segment_size($id, $row, $total_words);
        } else {
            $sg_size = array("width_pct" => $row['width_pct'] . '%');
        }
        if ($row['to_pos'] > $colorMaxPos[$colorOrd]) {
            $colorOrd++;
        }
        if ($color == $colors1[$colorOrd]) {
            $color = $colors2[$colorOrd];
        } else {
            $color = $colors1[$colorOrd];
        }
        array_push($rep['segments'], array(
            "div" => (int) $row['division_id'],
            "name" => $row['name'],
            "width" => $sg_size['width_pct'],
            "color" => $color,
            "to_pos" => (float) $row['to_pos'],
            "max_pos" => (float) $colorMaxPos[$colorOrd]
        ));
    }

    if ($prop['segments_generated'] == FALSE) {
        $prop['segments_generated'] = TRUE;
        elmseq_set($id, array('segments_generated' => TRUE));
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 16 in bar_func.php: ' . mysqli_error($con));
        }
    }

    return $rep;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function bar_get_selection_size($id, $prop, $base_table)
{
    global $con, $heb_num;

    if ($prop['total_words'] != 0) {
        return $prop['total_words'];
    }

    $sql = "SELECT SUM(gen_word_count) total_words
              FROM " . $base_table . " base";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 11 in bar_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);

    $sql = "UPDATE a_proj_elm_sequence
               SET gen_total_words = " . $row['total_words'] . " 
             WHERE project_id = " . $id['proj'] . "  
               AND element_id = " . $id['elm'];
    // try {
    //     $result = mysqli_query($con,$sql);
    // }
    // catch (mysqli_sql_exception $e) {
    //     // throw $e;
    //     exit_error('Error 12 in bar_func.php: ' . $sql);
    // }               
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 12 in bar_func.php: ' . mysqli_error($con));
    }

    $prop['total_words'] = $row['total_words'];

    return $prop['total_words'];
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function get_segment_size($id, $row1, $total_words)
{
    global $con;

    $widthPct = $row1['words'] / $total_words * 100;

    $sql = "INSERT INTO g_proj_elm_segments
                (project_id, element_id, 
                division_id, to_position, width_pct, name) 
                VALUES (" . $id['proj'] . "
                ," . $id['elm'] . "
                ," . $row1['division_id'] . "
                ," . $row1['to_pos'] . "
                ," . $widthPct . "
                ,'" . $row1['name'] . "')";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 13 in bar_func.php: ' . mysqli_error($con));
    }

    return array(
        'width_pct' => $widthPct . '%'
    );
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function bar_init_gen_segments($id)
{
    global $con;

    $sql = "DELETE FROM g_proj_elm_segments 
            WHERE project_id = " . $id['proj'] . "
            AND element_id = " . $id['elm'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 15 in bar_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function bar_init_gen_points($id)
{
    global $con;

    $sql = "DELETE FROM g_proj_elm_points 
            WHERE project_id = " . $id['proj'] . "
            AND element_id = " . $id['elm'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 15 in bar_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function bar_base_where($prop)
{
    global $con;

    $pos_pred = "";
    if ($prop['from_position'] > 0) {
        $pos_pred .= " AND base.position >= " . $prop['from_position'];
    }
    if ($prop['to_position'] > 0) {
        $pos_pred .= " AND base.position <= " . $prop['to_position'];
    }

    $base_where = " base.research_id = " . $prop['research_id'] . "
                AND base.collection_id = " . $prop['collection_id'] . "
                " . $pos_pred;

    return $base_where;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function bar_base_table($prop)
{
    global $con;

    $pos_pred = "";
    if ($prop['from_position'] > 0) {
        $pos_pred .= " AND position >= " . $prop['from_position'];
    }
    if ($prop['to_position'] > 0) {
        $pos_pred .= " AND position <= " . $prop['to_position'];
    }

    $base_table = "(SELECT research_id, part_id, type, 
                           collection_id, position, div_name_heb div_name, abs_name_heb abs_name,  
                           src_research, src_collection, src_from_position, src_from_word, src_to_position, src_to_word, 
                           gen_word_count, text, comment
                      FROM a_res_parts
                     WHERE research_id = " . $prop['research_id'] . "
                       AND collection_id = " . $prop['collection_id'] . "
                       " . $pos_pred . "
                       ) AS ";

    return $base_table;
}

// --------------------------------------------------------------------------------------
// ---- calculate points in bar for display
// --------------------------------------------------------------------------------------
function bar_calc_points($id, $dsp)
{
    global $con, $heb_num;

    $big_part_ratio = 1 / 1000;

    // get bar properties
    $prop = elmseq_get($id);

    $base_table = bar_base_table($prop);

    // get total verses in selection
    $total_words = bar_get_selection_size($id, $prop, $base_table);

    $filter = "";

    // categories filter
    if (array_key_exists('categories', $dsp)) {
        $filter .= " AND (pt.research_id,pt.collection_id) in(";
        foreach ($dsp['categories'] as $catId) {
            $filter .= "(" . $catId['res'] . "," . $catId['col'] . ")";
        }
        $filter .= ")";
    }

    // point filter
    if (array_key_exists('point', $dsp)) {
        $filter .= " AND pt.research_id = " . $dsp['point']['res'] . "
                     AND pt.part_id = " . $dsp['point']['prt'];
    }

    $sql = "SET SQL_BIG_SELECTS=1";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 11 in bar_func.php: ' . mysqli_error($con));
    }

    if ($prop['points_generated'] == TRUE) {
        $sql = "SELECT pt.link_id,
                   pt.research_id,pt.collection_id, pt.part_id,
                   pt.offset_pct, pt.width_pct, pt.name
              FROM g_proj_elm_points pt
             WHERE pt.project_id = " . $id['proj'] . "
               AND pt.element_id = " . $id['elm'] . "
               " . $filter . "
             ORDER BY pt.link_id,pt.research_id,pt.part_id";
    } else {
        bar_init_gen_points($id);

        // exit_wrror('yoni');
        // get all parts in collections that are linked to the bar element
        $sql = "SELECT pt.link_id,
                  pt.research_id,pt.collection_id, pt.part_id,
                  pt.gen_word_count pt_count, 
                  pt.src_from_position pt_from_position,pt.src_to_position pt_to_position, 
                  pt.src_from_word pt_from_word, pt.src_to_word pt_to_word,
                  pt.src_research pt_src_research,pt.src_collection pt_src_collection
              FROM view_proj_link_elm_part pt
             WHERE pt.project_id = " . $id['proj'] . "
              AND pt.element_id = " . $id['elm'] . "
              " . $filter . "
             GROUP BY pt.link_id,pt.research_id,pt.part_id";
    }
    $result1 = mysqli_query($con, $sql);
    if (!$result1) {
        exit_error('Error 12 in bar_func.php: ' . mysqli_error($con));
    }

    $rep = array();
    $rep['points'] = array();

    while ($row1 = mysqli_fetch_array($result1)) {
        if ($prop['points_generated'] == FALSE) {
            $pt_size = get_point_size($id, $base_table, $row1, $total_words, $big_part_ratio);
        } else {
            $pt_size = array(
                'offset_pct' => $row1['offset_pct'] . '%',
                "width_pct" => $row1['width_pct'] . '%',
                "name" => $row1['name']
            );
        }
        if ($pt_size['offset_pct'] != '') {
            array_push($rep['points'], array(
                "link" => (int) $row1['link_id'],
                "res" => (int) $row1['research_id'],
                "col" => (int) $row1['collection_id'],
                "id" => (int) $row1['part_id'],
                "position" => $pt_size['offset_pct'],
                "width" => $pt_size['width_pct'],
                "verse" => $pt_size['name']
            ));
        }
    }

    if ($prop['points_generated'] == FALSE) {
        $prop['points_generated'] = TRUE;
        elmseq_set($id, array('points_generated' => TRUE));
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 16 in bar_func.php: ' . mysqli_error($con));
        }
    }
    return $rep;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function get_point_size($id, $base_table, $row1, $total_words, $big_part_ratio)
{
    global $con, $heb_num;

    // check if the part is within the bar range
    $sql = "SELECT seq.position, seq.abs_name, seq.gen_word_count seq_count,
                   seq.src_from_position seq_from_position,seq.src_to_position seq_to_position,
                   seq.src_from_word seq_from_word, seq.src_to_word seq_to_word
              FROM " . $base_table . " seq
             WHERE seq.src_research = " . $row1['pt_src_research'] . "
               AND seq.src_collection = " . $row1['pt_src_collection'] . "
               AND (seq.src_to_position > " . $row1['pt_from_position'] . " OR
                   (seq.src_to_position = " . $row1['pt_from_position'] . " AND seq.src_to_word >= " . $row1['pt_from_word'] . "))
               AND (seq.src_from_position < " . $row1['pt_to_position'] . " OR 
                   (seq.src_from_position = " . $row1['pt_to_position'] . " AND seq.src_from_word <= " . $row1['pt_to_word'] . "))
             ORDER BY seq.src_from_position,seq.src_from_word
             LIMIT 1";
    $result4 = mysqli_query($con, $sql);
    if ($row4 = mysqli_fetch_array($result4)) {
        // sum word count in all former positions
        $sql = "SELECT SUM(former_seq.gen_word_count) seq_former_count
                FROM " . $base_table . " former_seq
                WHERE former_seq.position < " . $row4['position'];
        $result5 = mysqli_query($con, $sql);
        $row5 = mysqli_fetch_array($result5);
        $offset = $row5['seq_former_count'];

        if ($row4['seq_count'] / $total_words < $big_part_ratio) {
            $offset += $row4['seq_count'];
        } else {
            if ($row1['pt_from_position'] > $row4['seq_from_position']) {
                $sql = "SELECT SUM(gen_word_count) words
                        FROM " . $base_table . " base
                        WHERE position >= " . $row4['seq_from_position'] . "
                        AND position < " . $row1['pt_from_position'];
                $result2 = mysqli_query($con, $sql);
                if (!$result2) {
                    exit_error('Error 13 in bar_func.php: ' . mysqli_error($con));
                }
                $row2 = mysqli_fetch_array($result2);
                $offset += $row2['words'];
            } elseif ($row1['pt_from_position'] == $row4['seq_from_position']) {
                $word_gap = $row1['pt_from_word'] - $row4['seq_from_word'];
                if ($word_gap > 0) {
                    $offset += $word_gap;
                }
            }
        }

        $width = $row1['pt_count'];
        if (
            $row1['pt_from_position'] < $row4['seq_from_position']
            || $row1['pt_to_position'] > $row4['seq_to_position']
        ) {
            $sql = "SELECT SUM(gen_word_count) words
                    FROM " . $base_table . " base
                    WHERE position BETWEEN " . $row1['pt_from_position'] . " AND " . $row1['pt_to_position'] . "
                    AND NOT position BETWEEN " . $row4['seq_from_position'] . " AND " . $row4['seq_to_position'];
            $result2 = mysqli_query($con, $sql);
            if (!$result2) {
                exit_error('Error 14 in bar_func.php: ' . mysqli_error($con));
            }
            $row2 = mysqli_fetch_array($result2);
            $width -= $row2['words'];
        }
        if ($row1['pt_from_position'] == $row4['seq_from_position']) {
            $word_gap = $row4['seq_from_word'] - $row1['pt_from_word'];
            if ($word_gap > 0) {
                $offset -= $word_gap;
            }
        }
        if ($row1['pt_to_position'] == $row4['seq_to_position']) {
            $word_gap = $row1['pt_to_word'] - $row4['seq_to_word'];
            if ($word_gap > 0) {
                $offset -= $word_gap;
            }
        }

        $offsetPct = $offset / $total_words * 100;
        $widthPct = $width / $total_words * 100;
        $name = $row4['abs_name'];

        $sql = "INSERT INTO g_proj_elm_points
                (project_id, element_id, 
                link_id, research_id, part_id, collection_id, 
                offset_pct, width_pct, name) 
                VALUES (" . $id['proj'] . "
                ," . $id['elm'] . "
                ," . $row1['link_id'] . "
                ," . $row1['research_id'] . "
                ," . $row1['part_id'] . "
                ," . $row1['collection_id'] . "
                ," . $offsetPct . "
                ," . $widthPct . "
                ,'" . $name . "')";
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 13 in bar_func.php: ' . mysqli_error($con));
        }

        return array(
            'offset_pct' => $offsetPct . '%',
            'width_pct' => $widthPct . '%',
            'name' => $name
        );
    }

    return array(
        'offset_pct' => '',
        'width_pct' => '',
        'name' => ''
    );
}
?>