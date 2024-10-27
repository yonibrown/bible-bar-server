<?php
// --------------------------------------------------------------------------------------
// ---- get index properties
// --------------------------------------------------------------------------------------
function residx_get($id)
{
    global $con;

    $sql = "SELECT name_heb name
              FROM a_res_indexes
             WHERE research_id = " . $id['res'] . "
               AND collection_id = " . $id['col'] . "
               AND index_id = " . $id['idx'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 22 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $name = $row['name'];

    $level_list = residx_get_levels($id, array('dummy' => ''));

    $attr = array(
        'name' => $name,
        'levels' => $level_list
    );

    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- get levels in index                                    
// --------------------------------------------------------------------------------------
function residx_get_levels($id, $prop)
{
    global $con;

    $filter = '';
    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "levels":
                if ($val == 'key') {
                    $filter .= " AND part_of_key = TRUE";
                }
                break;
        }
    }

    $list = array();
    $sql = "SELECT level,whole_name_heb whole_name,unit_name_heb unit_name,part_of_key
              FROM a_res_idx_levels
             WHERE research_id = " . $id['res'] . "
               AND collection_id = " . $id['col'] . "
               AND index_id = " . $id['idx'] . "
               " . $filter . "
             ORDER BY level desc";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 28 in res_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        array_push($list, array(
            "id" => (int)$row['level'],
            "whole_name" => $row['whole_name'],
            "unit_name" => $row['unit_name'],
            "part_of_key" => ($row['part_of_key'] == 1)
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ----                
// --------------------------------------------------------------------------------------
function residx_get_max_level($id, $prop)
{
    global $con;

    $partOfKeyCrit = " ";
    if (array_key_exists('part_of_key', $prop)) {
        if ($prop['part_of_key']) {
            $partOfKeyCrit = " AND part_of_key = true";
        }
    }

    $sql = "SELECT MAX(level) level
              FROM a_res_idx_levels
             WHERE research_id = " . $id['res'] . "
               AND collection_id = " . $id['col'] . "
               AND index_id = " . $id['idx'] .
        $partOfKeyCrit;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 32 in res_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    return $row['level'];
}

// --------------------------------------------------------------------------------------
// ---- get division of every level of the key                                   
// --------------------------------------------------------------------------------------
function residx_get_divisions($id, $prop)
{
    if (array_key_exists('position', $prop)) {
        // get key from position
        $key = residx_position_to_key($id, $prop);
    } else {
        if (count($prop['key']) == 0) {
            // get key levels from index
            $level_list = residx_get_levels($id, array('levels' => 'key'));
            $key = array_map("level_list_to_empty_key", $level_list);
        } else {
            $key = $prop['key'];
        }
    }
    $divs = array();

    $firstLevel = TRUE;
    $parent_div = 0;
    foreach ($key as $level) {
        if ($parent_div != -999) {
            $level_prop = array(
                'level' => $level['level'],
                'selected_div' => $level['division_id']
            );
            if (!$firstLevel) {
                $level_prop['parent_div'] = $parent_div;
            }
            $levelDivs = residx_get_level_divisions($id, $level_prop);
            switch ($level['division_id']) {
                case 0:
                    // first division
                    $selected_div = $levelDivs['list'][0]['id'];
                    break;
                case -1:
                    // last division
                    $selected_div = end($levelDivs)['list']['id'];
                    break;
                default:
                    $selected_div = $level['division_id'];
            }
        } else {
            // if the high level div is -999 return empty lists in lower levels
            $levelDivs = array("list" => array());
            $selected_div = -999;
        }

        $level_divs = array(
            'level' => $level['level'],
            'divisions' => $levelDivs['list'],
            'selected_div' => $selected_div
        );
        array_push($divs, $level_divs);

        $parent_div = $selected_div;
        $firstLevel = FALSE;
    }

    return $divs;
}

function level_list_to_empty_key($level)
{
    return array(
        "level" => $level['id'],
        "division_id" => -999
    );
}

// --------------------------------------------------------------------------------------
// ---- get divisions in level of index                                   
// --------------------------------------------------------------------------------------
function residx_get_level_divisions($id, $prop)
{
    global $con;

    $parent_div = "";
    if (array_key_exists('parent_div', $prop)) {
        $sql = "SELECT from_position,to_position
                  FROM a_res_idx_division
                 WHERE research_id = " . $id['res'] . " 
                   AND division_id = " . $prop['parent_div'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 24 in res_func.php: ' . mysqli_error($con));
        }
        $row = mysqli_fetch_array($result);
        $parent_div = " AND from_position >= " . $row['from_position'] . " AND to_position <= " . $row['to_position'];
    }

    $list = array();
    $sql = "SELECT division_id,name1,name2,name3 
              FROM a_res_idx_division
             WHERE research_id = " . $id['res'] . " 
               AND collection_id = " . $id['col'] . " 
               AND index_id = " . $id['idx'] . " 
               AND level = " . $prop['level'] . "
               " . $parent_div . "
             ORDER BY from_position";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 29 in res_func.php: ' . mysqli_error($con));
    }
    $selected_div = array();
    while ($row = mysqli_fetch_array($result)) {
        array_push($list, array(
            "id" => (int)$row['division_id'],
            "name" => $row['name2'],
            "nameArr" => array($row['name1'],$row['name2'],$row['name3']),
            "selected" => ($row['division_id'] == $prop['selected_div'])
        ));
        if ($row['division_id'] == $prop['selected_div']) {
            $selected_div = array(
                "id" => (int)$row['division_id'],
                "name" => $row['name2'],
                "nameArr" => array($row['name1'],$row['name2'],$row['name3'])
            );
        }
    }
    return array('list' => $list, 'selected_div' => $selected_div);
}

// --------------------------------------------------------------------------------------
// ---- convert position/division to index key
// --------------------------------------------------------------------------------------
function residx_position_to_key($id, $prop)
{
    global $con;

    $list = array();

    if (array_key_exists('division_id', $prop)) {
        // get by division
        $sql = "SELECT d.level,d.division_id,d.name1,d.name2,d.name3
                  FROM a_res_idx_division p
                  JOIN a_res_idx_division d
                    ON d.research_id = p.research_id
                   AND d.collection_id = p.collection_id
                   AND d.index_id = p.index_id
                   AND d.from_position <= p.from_position
                   AND d.to_position >= p.to_position
                  JOIN a_res_idx_levels l
                    ON l.research_id = d.research_id
                   AND l.collection_id = d.collection_id
                   AND l.index_id = d.index_id
                   AND l.level = d.level
                   AND l.part_of_key = TRUE
                 WHERE p.research_id = " . $id['res'] . " 
                   AND p.collection_id = " . $id['col'] . " 
                   AND p.index_id = " . $id['idx'] . " 
                   AND p.division_id = " . $prop['division_id'] . "
                 ORDER BY d.level DESC";
    } else if (array_key_exists('position', $prop)) {
        // get by position
        if ($prop['position'] > 0) {
            $sql = "SELECT d.level,d.division_id,d.name1,d.name2,d.name3
                  FROM a_res_idx_division d
                  JOIN a_res_idx_levels l
                    ON l.research_id = d.research_id
                   AND l.collection_id = d.collection_id
                   AND l.index_id = d.index_id
                   AND l.level = d.level
                   AND l.part_of_key = TRUE
                 WHERE d.research_id = " . $id['res'] . " 
                   AND d.collection_id = " . $id['col'] . " 
                   AND d.index_id = " . $id['idx'] . " 
                   AND " . $prop['position'] . " BETWEEN d.from_position AND d.to_position
                 ORDER BY d.level DESC";
        } else {
            if ($prop['position'] == 0) {
                $group_func = 'MIN';
            } else { /* $prop['position'] == -1 */
                $group_func = 'MAX';
            }
            $sql = "SELECT d.level," . $group_func . "(d.division_id) division_id,d.name1,d.name2,d.name3
                  FROM a_res_idx_division d
                  JOIN a_res_idx_levels l
                    ON l.research_id = d.research_id
                   AND l.collection_id = d.collection_id
                   AND l.index_id = d.index_id
                   AND l.level = d.level
                   AND l.part_of_key = TRUE
                 WHERE d.research_id = " . $id['res'] . " 
                   AND d.collection_id = " . $id['col'] . " 
                   AND d.index_id = " . $id['idx'] . " 
                 GROUP BY d.level
                 ORDER BY d.level DESC";
        }
    } else {
        // get default key
        $sql = "SELECT l.level level, -999 division_id,' ' name1,' ' name2,' ' name3
                  FROM a_res_idx_levels l
                 WHERE l.research_id = " . $id['res'] . " 
                   AND l.collection_id = " . $id['col'] . " 
                   AND l.index_id = " . $id['idx'] . "
                   AND l.part_of_key = TRUE
                 ORDER BY l.level DESC";
    }

    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 30 in res_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        // array_push($list,$row['division_id']);
        array_push($list, array(
            "level" => (int)$row['level'],
            "division_id" => (int)$row['division_id'],
            "name" => $row['name2'],
            "nameArr" => array($row['name1'],$row['name2'],$row['name3'])
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- get the division of level 0 that the position is in it
// --------------------------------------------------------------------------------------
// function residx_position_to_div($id, $prop)
// {
//     global $con;

//     $sql = "SELECT d.division_id,d.name2 name
//                   FROM a_res_idx_division d
//                  WHERE d.research_id = " . $id['res'] . " 
//                    AND d.collection_id = " . $id['col'] . " 
//                    AND d.index_id = " . $id['idx'] . " 
//                    AND d.level = 0
//                    AND " . $prop['position'] . " BETWEEN d.from_position AND d.to_position";
//     $result = mysqli_query($con, $sql);
//     if (!$result) {
//         exit_error('Error 30 in res_func.php: ' . mysqli_error($con));
//     }
//     $row = mysqli_fetch_array($result);
//     return array('division_id' => $row['division_id']);
// }

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function residx_get_level_range($id, $name, $level, $initialRange)
{
    global $con;
    $sql = "SELECT division_id,from_position,to_position
            FROM a_res_idx_division
            WHERE research_id = " . $id['res'] . " 
                AND collection_id = " . $id['col'] . " 
                AND index_id = " . $id['idx'] . " 
                AND level = " . $level . "
                AND from_position >= " . $initialRange['from'] . "
                AND to_position <= " . $initialRange['to'] . "
                AND name2 = '" . $name . "'";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error description2: ' . mysqli_error($con));
    }
    if ($row = mysqli_fetch_array($result)) {
        return array(
            "from" => $row['from_position'],
            "to" => $row['to_position']
        );
    }

    return null;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function residx_get_division($prop)
{
    global $con;
    $sql = "SELECT from_position,to_position,level
              FROM a_res_idx_division
             WHERE research_id   = " . $prop['research_id'] . " 
               AND collection_id = " . $prop['collection_id'] . "
               AND division_id   = " . $prop['division_id'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error description2: ' . mysqli_error($con));
    }
    if ($row = mysqli_fetch_array($result)) {
        return array(
            "from_position" => $row['from_position'],
            "to_position" => $row['to_position'],
            "level" => $row['level']
        );
    }

    return null;
}
