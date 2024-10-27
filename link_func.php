<?php
// --------------------------------------------------------------------------------------
// ---- create new link
// --------------------------------------------------------------------------------------
function lnk_create($prop)
{
    global $con;

    $proj = $prop['proj'];

    $name = '';
    $desc = '';
    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "name":
                $name = $val;
                break;
            case "description":
                $desc = $val;
                break;
        }
    }

    $res = 0;
    if (array_key_exists('research_id', $prop)) {
        $res = $prop['research_id'];
        if ($name == '') {
            $res_attr = res_get_basic(array("res" => $res));
            $name = $res_attr['name'];
        }
    }

    $sql = "SELECT MAX(link_id) link_id
              FROM a_proj_links
             WHERE project_id = " . $proj;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 7 in lnk_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $link = $row['link_id'] + 1;

    $sql = "INSERT INTO a_proj_links
                (project_id, link_id, name, description,research_id)
            VALUES(" . $proj . ", 
                " . $link . ", 
                '" . $name . "',
                '" . $desc . "',
                " . $res . ")";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 8 in lnk_func.php: ' . mysqli_error($con));
    }

    $id = array('proj' => $proj, 'link' => $link);

    $elmlist = array();
    if (array_key_exists('main_element', $prop)) {
        lnk_add_element($id, array("elm" => $prop['main_element']));
        array_push($elmlist, $prop['main_element']);
    }

    $catlist = array();
    if ($res != 0) {
        $catlist = lnk_add_categories($id, array(
            "type" => "research",
            "data" => $res
        ));
    }

    return lnk_link_obj(array(
        "id" => $link,
        "proj" => $proj,
        "name" => $name,
        "desc" => $desc,
        "categories" => $catlist,
        "elements" => $elmlist,
        "research_id" => $res
    ));
}

// --------------------------------------------------------------------------------------
// ---- get link element                                     
// --------------------------------------------------------------------------------------
function lnk_get_basic($id)
{
    global $con;

    $sql = "SELECT link_id,name, description,research_id
            FROM a_proj_links
            WHERE project_id = " . $id['proj'] . "
              AND link_id = " . $id['link'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 6 in elm_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $attr = array(
        'name' => $row['name'],
        'description' => $row['description'],
        'research_id' => (int)$row['research_id']
    );

    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_get($id)
{
    $row = lnk_get_basic($id);
    return lnk_prop($id, $row);
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_set($id, $prop)
{
    global $con;

    $sql_set = '';
    $sep = '';
    foreach ($prop as $attr => $val) {
        switch ($attr) {
            case "name":
                $sql_set = $sql_set . $sep . $attr . " = '" . $val . "'";
                $sep = ',';
                break;
        }
    }

    $sql = "UPDATE a_proj_links 
            SET " . $sql_set . "  
            WHERE project_id = " . $id['proj'] . "
              AND link_id = " . $id['link'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 3 in link_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_link_obj($prop)
{
    $struct = array(
        "id" => "int,key",
        "proj" => "int,key",
        "name" => "string",
        "desc" => "string",
        "categories" => "array",
        "elements" => "array",
        "research_default" => "int"
    );
    return build_object($prop, $struct);
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_prop($id, $prop)
{
    return lnk_link_obj(array(
        "proj" => $id['proj'],
        "id" => $id['link'],
        "name" => $prop['name'],
        "desc" => $prop['description'],
        "categories" => lnk_get_categories($id, array(
            "research_id" => (int)$prop['research_id']
        )),
        "elements" => lnk_get_elements($id),
        "research_id" => $prop['research_id']
    ));
}

// --------------------------------------------------------------------------------------
// ---- get categories in link
// --------------------------------------------------------------------------------------
function lnk_get_categories($id, $prop)
{
    global $con;

    if ($prop['research_id'] == 0) {
        $sql = "SELECT lc.research_id,lc.collection_id,lc.division_id,
                lc.color,lc.hilight,' ' name
              FROM a_proj_link_collections lc
             WHERE lc.project_id = " . $id['proj'] . "
               AND lc.link_id = " . $id['link'] . "
             ORDER BY lc.position";
    } else {
        $sql = "SELECT rc.research_id,rc.collection_id,0 division_id,
                IFNULL(lc.color,'#b6bab5') color,IFNULL(lc.hilight,0) hilight,rc.name_heb name
              FROM a_res_collections rc
              LEFT JOIN a_proj_link_collections lc
                ON rc.research_id = lc.research_id
               AND rc.collection_id = lc.collection_id    
               AND lc.project_id = " . $id['proj'] . "
               AND lc.link_id = " . $id['link'] . "
             WHERE rc.research_id = " . $prop['research_id'] . " 
             ORDER BY rc.position";
    }
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 1 in link_func.php: ' . mysqli_error($con));
    }

    $flatArray = array();
    while ($row = mysqli_fetch_array($result)) {
        if ($row['name'] != ' ') {
            $name = $row['name'];
        } else {
            $name = lnk_get_cat_name($row);
        }

        array_push($flatArray, array(
            "color" => $row['color'],
            "display" => ($row['hilight'] == 1),
            "res" => (int)$row['research_id'],
            "col" => (int)$row['collection_id'],
            "div" => (int)$row['division_id'],
            "name" => $name
        ));
    }

    return $flatArray;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_get_cat_name($prop)
{
    global $con;

    if ($prop['division_id'] != 0) {
        $sql = "SELECT name2 name
                  FROM a_res_idx_division
                 WHERE research_id  = " . $prop['research_id'] . "
                   AND collection_id = " . $prop['collection_id'] . "
                   AND division_id = " . $prop['division_id'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 1 in link_func.php: ' . mysqli_error($con));
        }
        $row = mysqli_fetch_array($result);
    } elseif ($prop['collection_id'] != 0) {
        $sql = "SELECT name_heb name
                    FROM a_res_collections
                    WHERE research_id  = " . $prop['research_id'] . "
                    AND collection_id = " . $prop['collection_id'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 1 in link_func.php: ' . mysqli_error($con));
        }
        $row = mysqli_fetch_array($result);
    } else {
        $sql = "SELECT name_heb name
                    FROM a_researches
                    WHERE research_id  = " . $prop['research_id'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 1 in link_func.php: ' . mysqli_error($con));
        }
        $row = mysqli_fetch_array($result);
    }
    return $row['name'];
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_get_elements($id)
{
    global $con;

    $sql = "SELECT le.element_id
              FROM a_proj_link_elements le
             WHERE le.project_id = " . $id['proj'] . "
               AND le.link_id = " . $id['link'] . "
             ORDER BY le.element_id";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 12 in link_func.php: ' . mysqli_error($con));
    }

    $flatArray = array();
    while ($row = mysqli_fetch_array($result)) {
        array_push($flatArray, (int)$row['element_id']);
    }

    return $flatArray;
}

// --------------------------------------------------------------------------------------
// ---- add element to a link
// --------------------------------------------------------------------------------------
function lnk_add_element($id, $prop)
{
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];
    $elm = $prop['elm'];

    $sql = "INSERT INTO a_proj_link_elements
                (project_id, link_id, element_id) 
            VALUES (" . $proj . ",
                    " . $link . ",
                    " . $elm . ")";
    $result = mysqli_query($con, $sql);
    if (!$result &&  mysqli_errno($con) != 1062) {
        exit_error('Error 2 in link_func.php: ' . mysqli_error($con));
    }

    elm_links_changed(array(
        'proj' => $proj,
        'elm' => $elm
    ));
}

// --------------------------------------------------------------------------------------
// ---- remove element from a link
// --------------------------------------------------------------------------------------
function lnk_remove_element($id, $prop)
{
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];
    $elm = $prop['elm'];

    $sql = "DELETE FROM a_proj_link_elements
             WHERE project_id = " . $proj . "
               AND link_id = " . $link . "
               AND element_id = " . $elm;
    $result = mysqli_query($con, $sql);
    if (!$result &&  mysqli_errno($con) != 1062) {
        exit_error('Error 13 in link_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- add categories to a link
// --------------------------------------------------------------------------------------
function lnk_add_categories($id, $prop)
{
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];

    if ($prop['type'] == 'list') {
        $list = $prop['data'];
    } else if ($prop['type'] == 'category') {
        $list = array($prop['data']);
    } else if ($prop['type'] == 'research') {
        $res = $prop['data'];
        $colList = res_get_col_list(array("res" => $res));
        function catlist($col)
        {
            return array(
                "res" => $col['res'],
                "col" => $col['id']
            );
        }
        $list = array_map("catlist", $colList);
    }

    $catArray = array();
    foreach ($list as $cat) {
        // add the category to the returned array
        array_push($catArray, lnk_add_category($id, array("cat_id" => $cat)));
    }

    return $catArray;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_get_res_links($prop)
{
    global $con;

    $list = array();
    $sql = "SELECT ln.project_id, ln.link_id
              FROM a_proj_links ln
             WHERE ln.research_id = " . $prop['res'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 1 in lnk_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        array_push($list, array(
            "proj" => $row['project_id'],
            "link" => $row['link_id']
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_new_collection($prop)
{
    global $con;

    $list = lnk_get_res_links($prop);
    foreach ($list as $lnkId) {
        lnk_add_category($lnkId, array("cat_id" => array(
            "res" => $prop['res'],
            "col" => $prop['id']
        )));
    }
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_add_category($id, $prop)
{
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];

    $catId = $prop['cat_id'];
    if (array_key_exists('cat_attr', $prop)) {
        $catAttr = $prop['cat_attr'];
    } else {
        $catAttr = array();
    }

    // get available color
    if (array_key_exists('color', $catAttr)) {
        $color = $catAttr['color'];
    } else {
        $color = lnk_get_available_color($id);
    }

    // get display
    if (array_key_exists('display', $catAttr)) {
        $display = $catAttr['display'];
    } else {
        $display = true;
    }

    // add the category to the link
    $sql = "INSERT INTO a_proj_link_collections
                (project_id, link_id, position, research_id, collection_id, division_id, color, hilight) 
            VALUES (" . $proj . ",
                    " . $link . ",
                    0,
                    " . $catId['res'] . ",
                    " . $catId['col'] . ",
                    0,
                    '" . $color . "',
                    1)";
    try {
        $result = mysqli_query($con, $sql);
    } catch (mysqli_sql_exception $e) {
        // throw $e;
        exit_error('Error 5 in link_func.php: ' . mysqli_error($con) . $sql);
    }

    return array(
        "color" => $color,
        "display" => $display,
        "res" => $catId['res'],
        "col" => $catId['col'],
        "div" => 0,
        "name" => lnk_get_cat_name(array(
            "research_id" => $catId['res'],
            "collection_id" => $catId['col'],
            "division_id" => 0
        ))
    );
}

// --------------------------------------------------------------------------------------
// ---- 
// --------------------------------------------------------------------------------------
function lnk_get_available_color($id)
{
    global $con;

    $sql = "SELECT c.color
            FROM a_proj_link_collections c
            WHERE c.color NOT IN (
                SELECT t.color
                FROM a_proj_link_collections t
                WHERE t.project_id = " . $id['proj'] . "
                    AND t.link_id = " . $id['link'] . ")
            LIMIT 1";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 4 in link_func.php: ' . mysqli_error($con));
    }
    if ($row = mysqli_fetch_array($result)) {
        return $row['color'];
    }

    return '#00d8ff';
}

// --------------------------------------------------------------------------------------
// ---- update category in a link
// --------------------------------------------------------------------------------------
function lnk_upd_category($id, $prop)
{
    global $con;

    $proj = $id['proj'];
    $link = $id['link'];

    $catId = $prop['cat_id'];
    $catAttr = $prop['cat_attr'];

    $sql_set = '';
    $sep = '';
    foreach ($catAttr as $attr => $val) {
        switch ($attr) {
            case "display":
                $sql_set .= $sep . "hilight = " . ($val == 'true' ? 1 : 0);
                $sep = ',';
                break;
            case "color":
                $sql_set .= $sep . "color = '" . $val . "'";
                $sep = ',';
                break;
        }
    }

    $sql = "UPDATE a_proj_link_collections 
            SET " . $sql_set . "  
            WHERE project_id = " . $id['proj'] . "
              AND link_id = " . $id['link'] . "
              AND research_id = " . $catId['res'] . "
              AND collection_id = " . $catId['col'] . "
              AND division_id = " . $catId['div'];
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 3 in link_func.php: ' . mysqli_error($con));
    }

    // if the category is not yet in the link
    if (mysqli_affected_rows($con) == 0) {
        lnk_add_category($id, $prop);
    }
}
