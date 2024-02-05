<?php
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

    $filter = '';
    foreach($prop as $attr => $val) {
        switch ($attr) {
            case "levels":
                if ($val == 'key'){
                    $filter .= " AND part_of_key = TRUE";
                }
                break;
        }   
    }

    $list = array();
    $sql = "SELECT level,whole_name_heb whole_name,unit_name_heb unit_name,part_of_key
              FROM a_res_idx_levels
             WHERE research_id = ".$id['res']."
               AND collection_id = ".$id['col']."
               AND index_id = ".$id['idx']."
               ".$filter."
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

    if (array_key_exists('position',$prop)){
        $key = residx_position_to_key($id,$prop);
    } else {
        $key = $prop['key'];
    }
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
        $sql = "SELECT d.level,d.division_id,d.name_heb name
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
        $sql = "SELECT d.level,".$group_func."(d.division_id) division_id,d.name_heb name
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
            "division_id"=>(int)$row['division_id'],
            "name"=>$row['name']
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function residx_get_level_range($id,$name,$level,$initialRange){
    global $con;
    $sql = "SELECT division_id,from_position,to_position
            FROM a_res_idx_division
            WHERE research_id = ".$id['res']." 
                AND collection_id = ".$id['col']." 
                AND index_id = ".$id['idx']." 
                AND level = ".$level."
                AND from_position >= ".$initialRange['from']."
                AND to_position <= ".$initialRange['to']."
                AND name_heb = '".$name."'";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error description2: ' . mysqli_error($con));
    }
    if($row = mysqli_fetch_array($result)){
        return array(
            "from"=>$row['from_position'],
            "to"=>$row['to_position']
        );
    }

    return null;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function residx_get_division($prop){
    global $con;
    $sql = "SELECT from_position,to_position,level
              FROM a_res_idx_division
             WHERE research_id   = ".$prop['research_id']." 
               AND collection_id = ".$prop['collection_id']."
               AND division_id   = ".$prop['division_id'];
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error description2: ' . mysqli_error($con));
    }
    if($row = mysqli_fetch_array($result)){
        return array(
            "from_position"=>$row['from_position'],
            "to_position"=>$row['to_position'],
            "level"=>$row['level']
        );
    }

    return null;
}

?>