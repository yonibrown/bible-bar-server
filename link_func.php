<?php
// --------------------------------------------------------------------------------------
// ---- create new link
// --------------------------------------------------------------------------------------
function lnk_create($prop){
    global $con;

    $proj = $prop['proj'];

    $name = '';
    $desc = '';
    foreach($prop as $attr => $val) {
        switch ($attr) {
            case "name":
                $name = $val;
                break;
            case "description":
                $desc = $val;
                break;
        }   
    }

    $sql = "SELECT MAX(link_id) link_id
              FROM a_proj_links
             WHERE project_id = ".$proj;
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 7 in lnk_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $link = $row['link_id']+1;

    $sql = "INSERT INTO a_proj_links
                (project_id, link_id, name, description)
            VALUES(".$proj.", 
                ".$link.", 
                '".$name."',
                '".$desc."')";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 8 in lnk_func.php: ' . mysqli_error($con));
    }

    $id = array('proj'=>$proj,'link'=>$link);
    return $id;
}

// --------------------------------------------------------------------------------------
// ---- get categories in link
// --------------------------------------------------------------------------------------
function lnk_get_categories($id){
    global $con;

    $sql = "SELECT lc.research_id,lc.collection_id,lc.division_id,lc.color,lc.hilight
              FROM a_proj_link_collections lc
             WHERE lc.project_id = ".$id['proj']."
               AND lc.link_id = ".$id['link']."
             ORDER BY lc.position";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 1 in link_func.php: ' . mysqli_error($con));
    }

    $flatArray = array();
    while($row = mysqli_fetch_array($result)) {
        if ($row['division_id'] != 0){
            $sql = "SELECT name_heb name
                      FROM a_res_idx_division
                     WHERE research_id  = ".$row['research_id']."
                       AND collection_id = ".$row['collection_id']."
                       AND division_id = ".$row['division_id'];
            $result1 = mysqli_query($con,$sql);
            if (!$result1) {
                exit_error('Error 1 in link_func.php: ' . mysqli_error($con));
            }
            $row1 = mysqli_fetch_array($result1);
        } else {
            if ($row['collection_id'] != 0){
                $sql = "SELECT name_heb name
                          FROM a_res_collections
                         WHERE research_id  = ".$row['research_id']."
                           AND collection_id = ".$row['collection_id'];
                $result1 = mysqli_query($con,$sql);
                if (!$result1) {
                    exit_error('Error 1 in link_func.php: ' . mysqli_error($con));
                }
                $row1 = mysqli_fetch_array($result1);
            } else {
                $sql = "SELECT name_heb name
                          FROM a_researches
                         WHERE research_id  = ".$row['research_id'];
                $result1 = mysqli_query($con,$sql);
                if (!$result1) {
                    exit_error('Error 1 in link_func.php: ' . mysqli_error($con));
                }
                $row1 = mysqli_fetch_array($result1);
            }
        }
        array_push($flatArray,array("color"=>$row['color'],
                                "display"=>($row['hilight'] == 1),
                                "res"=>$row['research_id'],
                                "col"=>$row['collection_id'],
                                "div"=>$row['division_id'],
                                "name"=>$row1['name']));
    }

    return $flatArray;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_get_elements($id){
    global $con;

    $sql = "SELECT le.element_id
              FROM a_proj_link_elements le
             WHERE le.project_id = ".$id['proj']."
               AND le.link_id = ".$id['link']."
             ORDER BY le.element_id";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 12 in link_func.php: ' . mysqli_error($con));
    }

    $flatArray = array();
    while($row = mysqli_fetch_array($result)) {
        array_push($flatArray,$row['element_id']);
    }

    return $flatArray;
}

// --------------------------------------------------------------------------------------
// ---- add element to a link
// --------------------------------------------------------------------------------------
function lnk_add_element($id,$prop){
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];
    $elm = $prop['elm'];

    $sql = "INSERT INTO a_proj_link_elements
                (project_id, link_id, element_id) 
            VALUES (".$proj.",
                    ".$link.",
                    ".$elm.")";
    $result = mysqli_query($con,$sql);
    if (!$result &&  mysqli_errno($con) != 1062) {
        exit_error('Error 2 in link_func.php: ' . mysqli_error($con));
    }

    elm_links_changed(array(
        'proj'=>$proj,
        'elm'=>$elm
    ));
}

// --------------------------------------------------------------------------------------
// ---- remove element from a link
// --------------------------------------------------------------------------------------
function lnk_remove_element($id,$prop){
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];
    $elm = $prop['elm'];

    $sql = "DELETE FROM a_proj_link_elements
             WHERE project_id = ".$proj."
               AND link_id = ".$link."
               AND element_id = ".$elm;
    $result = mysqli_query($con,$sql);
    if (!$result &&  mysqli_errno($con) != 1062) {
        exit_error('Error 13 in link_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- add categories to a link
// --------------------------------------------------------------------------------------
function lnk_add_categories($id,$prop){
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];

    if ($prop['type'] == 'list'){
        $list = $prop['data'];
    } else if ($prop['type'] == 'category'){
        $list = array($prop['data']);
    }

    $catArray = array();
    foreach($list as $cat) {
        // get available color
        $sql = "SELECT c.color
                FROM a_proj_link_collections c
                WHERE c.color NOT IN (
                    SELECT t.color
                    FROM a_proj_link_collections t
                    WHERE t.project_id = ".$proj."
                        AND t.link_id = ".$link.")
                LIMIT 1";
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 4 in link_func.php: ' . mysqli_error($con));
        }
        if ($row = mysqli_fetch_array($result)){
            $color = $row['color'];
        } else {
            $color = '#00d8ff';
        }
        // add the category to the link
        $sql = "INSERT INTO a_proj_link_collections
                    (project_id, link_id, position, research_id, collection_id, division_id, color, hilight) 
                VALUES (".$proj.",
                        ".$link.",
                        0,
                        ".$cat['res'].",
                        ".$cat['col'].",
                        0,
                        '".$color."',
                        1)";
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 5 in link_func.php: ' . mysqli_error($con).$sql);
        }

        // add the category to the returned array
        array_push($catArray,array("proj"=>$proj,
                                "link"=>$link,
                                "res"=>$cat['res'],
                                "col"=>$cat['col'],
                                "div"=>0,
                                "color"=>$color,
                                "hilight"=>true));
    }

    return $catArray;
}

// --------------------------------------------------------------------------------------
// ---- update category in a link
// --------------------------------------------------------------------------------------
function lnk_upd_category($id,$prop){
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];

    $catId = $prop['cat_id'];
    $catAttr = $prop['cat_attr'];

    $sql_set = '';
    $sep = '';
    foreach($catAttr as $attr => $val) {
        switch ($attr) {
            case "display":
                $sql_set .= $sep."hilight = ".($val=='true'?1:0);
                $sep = ',';
                break;
            case "color":
                $sql_set .= $sep."color = '".$val."'";
                $sep = ',';
                break;
        }   
    }

    $sql = "UPDATE a_proj_link_collections 
            SET ".$sql_set."  
            WHERE project_id = ".$id['proj']."
              AND link_id = ".$id['link']."
              AND research_id = ".$catId['res']."
              AND collection_id = ".$catId['col']."
              AND division_id = ".$catId['div'];
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 3 in link_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- remove categories from a link
// --------------------------------------------------------------------------------------
function lnk_remove_categories($id,$prop){
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];

    if ($prop['type'] == 'list'){
        $list = $prop['data'];
    } else if ($prop['type'] == 'category'){
        $list = array($prop['data']);
    }

    $catArray = array();
    foreach($list as $cat) {
        $sql = "DELETE FROM a_proj_link_collections
                 WHERE project_id = ".$proj."
                   AND link_id = ".$link."
                   AND research_id = ".$cat['res']."
                   AND collection_id = ".$cat['col']."
                   AND division_id = ".$cat['div'];
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 6 in link_func.php: ' . mysqli_error($con));
        }

        // add the category to the returned array
        array_push($catArray,array("proj"=>$proj,
                                "link"=>$link,
                                "res"=>$cat['res'],
                                "col"=>$cat['col'],
                                "div"=>$cat['div'],
                                "color"=>$color,
                                "hilight"=>true));
    }

    return $catArray;
}
?>