<?php
// --------------------------------------------------------------------------------------
// ---- get list of projects in the system
// --------------------------------------------------------------------------------------
function proj_get_list()
{
    global $con;
    $list = array();

    $sql = "SELECT project_id id,name,description,display_version
                FROM a_projects
                ORDER BY project_id";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 1 in proj_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        array_push($list, array(
            "id" => (int) $row['id'],
            "name" => $row['name'],
            "desc" => $row['description'],
            "display_version" => (int) $row['display_version']
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- get project                                     
// --------------------------------------------------------------------------------------
function proj_get($id)
{
    global $con;

    $proj = $id['proj'];

    $sql = "SELECT name,description,primary_link
            FROM a_projects
            WHERE project_id = " . $proj;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 2 in proj_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);

    $elm_list = proj_get_elm_list($id);
    $lnk_list = proj_get_lnk_list($id, array('dummy' => ''));
    $res_list = proj_get_res_list($id);
    $tab_list = proj_get_tab_list($id);

    $attr = array(
        'name' => $row['name'],
        'desc' => $row['description'],
        'primary_link' => (int) $row['primary_link'],
        'elements' => $elm_list,
        'links' => $lnk_list,
        'researches' => $res_list,
        'tabs' => $tab_list
    );
    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- set project attributes
// --------------------------------------------------------------------------------------
function proj_set($id, $prop)
{
    global $con;

    $proj = $id['proj'];
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

    $sql = "UPDATE a_projects 
            SET " . $sql_set . "  
            WHERE project_id = " . $proj;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 3 in proj_func.php: ' . mysqli_error($con));
    }
}

// --------------------------------------------------------------------------------------
// ---- create new project
// --------------------------------------------------------------------------------------
function proj_create($prop)
{
    global $con;
    $sql = "SELECT MAX(project_id) project_id
                FROM a_projects";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 4 in proj_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);
    $proj = $row['project_id'] + 1;

    $sql = "INSERT INTO a_projects
                (project_id, 
                name, 
                description,
                primary_link) 
            VALUES(" . $proj . ", 
                '" . $prop['name'] . "', 
                '" . $prop['desc'] . "',
                1)";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 5 in proj_func.php: ' . mysqli_error($con));
    }

    // create primary link
    lnk_create(array(
        "name" => "primary link",
        "proj" => $proj
    ));

    // create default research for project
    // $res_prop = array("name"=>"project","desc"=>"project default research","proj"=>$proj);
    // $res = res_create($res_prop);
    // res_new_collection($res,array("name"=>"default","desc"=>"default collection"));

    return array("proj" => $proj);
}

// --------------------------------------------------------------------------------------
// ---- save elements display in project
// --------------------------------------------------------------------------------------
function proj_save_elements($id, $prop)
{
    global $con;

    $proj = $id['proj'];
    $elements = $prop['elements'];
    $tab = $prop['tab'];

    proj_delete_unlisted_elements($id, $prop);
    // proj_clear_redundant_data();

    // update display for elements in the list
    // ----------------------------------------
    foreach ($elements as $elm) {
        $sql = "UPDATE a_proj_elements
                   SET position = " . $elm['position'] . "
                     , tab_id = " . $tab . "
                 WHERE project_id = " . $proj . "
                   AND element_id = " . $elm['id'];
        $result = mysqli_query($con, $sql);
        if (!$result) {
            exit_error('Error 14 in proj_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ----                                 
// --------------------------------------------------------------------------------------
function proj_delete_unlisted_elements($id, $prop)
{
    global $con;

    $proj = $id['proj'];
    $elements = $prop['elements'];
    $tab = $prop['tab'];

    $delete_where = "WHERE project_id = " . $proj . "
                       AND tab_id = " . $tab;
    if (count($elements) > 0) {
        $elm_ids = array();
        foreach ($elements as $elm) {
            array_push($elm_ids, $elm['id']);
        }
        $delete_where .= " AND element_id NOT " . inList($elm_ids);
    }

    $sql = "UPDATE a_proj_elements
               SET position = 0 
               " . $delete_where;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 7 in proj_func.php: ' . mysqli_error($con));
    }

    // delete elements that are not in the list
    // ----------------------------------------
    // $sql = "DELETE FROM a_proj_elements ".$delete_where;
    // $result = mysqli_query($con,$sql);
    // if (!$result) {
    //     exit_error('Error 7 in proj_func.php: ' . mysqli_error($con));
    // }

    // $sql = "DELETE FROM a_proj_elm_sequence ".$delete_where;
    // $result = mysqli_query($con,$sql);
    // if (!$result) {
    //     exit_error('Error 8 in proj_func.php: ' . mysqli_error($con));
    // }

    // $sql = "DELETE FROM a_proj_elm_seq_divisions ".$delete_where;
    // $result = mysqli_query($con,$sql);
    // if (!$result) {
    //     exit_error('Error 9 in proj_func.php: ' . mysqli_error($con));
    // }

    // $sql = "DELETE FROM a_proj_elm_link ".$delete_where;
    // $result = mysqli_query($con,$sql);
    // if (!$result) {
    //     exit_error('Error 10 in proj_func.php: ' . mysqli_error($con));
    // }

    // $sql = "DELETE FROM a_proj_elm_research ".$delete_where;
    // $result = mysqli_query($con,$sql);
    // if (!$result) {
    //     exit_error('Error 11 in proj_func.php: ' . mysqli_error($con));
    // }

    // $sql = "DELETE FROM a_proj_elm_parts ".$delete_where;
    // $result = mysqli_query($con,$sql);
    // if (!$result) {
    //     exit_error('Error 12 in proj_func.php: ' . mysqli_error($con));
    // }

    // // unlink elements
    // $sql = "DELETE FROM a_proj_link_elements ".$delete_where;
    // $result = mysqli_query($con,$sql);
    // if (!$result) {
    //     exit_error('Error 13 in proj_func.php: ' . mysqli_error($con));
    // }
}

// --------------------------------------------------------------------------------------
//                                
// --------------------------------------------------------------------------------------
// function proj_clear_redundant_data(){
//     global $con;

//     // if there are links that remained without elements, delete them
//     $sql = "DELETE FROM a_proj_links
//              WHERE (project_id,link_id) NOT IN (
//                           SELECT project_id,link_id
//                             FROM a_proj_link_elements)";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 18 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_link_collections
//              WHERE (project_id,link_id) NOT IN (
//                           SELECT project_id,link_id
//                             FROM a_proj_links)";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 19 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_elm_link
//              WHERE (project_id,link_id) NOT IN (
//                           SELECT project_id,link_id
//                             FROM a_proj_links)";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 21 in proj_func.php: ' . mysqli_error($con));
//     }
// }

// --------------------------------------------------------------------------------------
// ---- get category list for researches in project                                   
// --------------------------------------------------------------------------------------
function proj_get_res_list($id)
{
    global $con;

    $proj = $id['proj'];
    $list = array();
    $sql = "SELECT r.research_id,r.name_heb name
              FROM a_researches r
             WHERE r.research_id in(
                 SELECT pa.research_id
                   FROM a_proj_elm_research pa
                   JOIN a_proj_elements pe
                     ON pa.project_id = pe.project_id
                    AND pa.element_id = pe.element_id
                  WHERE pe.project_id = " . $proj . "
                    AND pe.position > 0)
                OR r.research_id in(
                 SELECT pa.research_id
                   FROM a_proj_elm_parts pa
                   JOIN a_proj_elements pe
                     ON pa.project_id = pe.project_id
                    AND pa.element_id = pe.element_id
                  WHERE pe.project_id = " . $proj . "
                    AND pe.position > 0)
                OR r.research_id in(
                 SELECT research_id
                   FROM a_proj_link_collections
                  WHERE project_id = " . $proj . ")
             ORDER BY r.project_id DESC,r.research_id";
    //  OR r.project_id = ".$proj."
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 15 in proj_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        array_push($list, res_prop(array(
            "res" => $row['research_id']
        ), $row));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- get elements list for project                                   
// --------------------------------------------------------------------------------------
function proj_get_elm_list($id)
{
    global $con;

    $proj = $id['proj'];
    $list = array();

    $sql = "SELECT pe.element_id id,type,name,
                   opening_element,
                   pe.tab_id tab,
                   pe.position,
                   pe.y_addition,
                   show_props,
                   open_text_element
            FROM a_proj_elements pe
            WHERE pe.project_id = " . $proj . "
              AND position > 0
            ORDER BY position";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 16 in proj_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        $elmId = array(
            "proj" => $proj,
            "elm" => $row['id']
        );
        array_push($list, elm_prop($elmId, $row));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- get tab list for project                                   
// --------------------------------------------------------------------------------------
function proj_get_tab_list($id)
{
    global $con;

    $proj = $id['proj'];
    $list = array();

    $sql = "SELECT DISTINCT IFNULL(pt.tab_id,0) id,IFNULL(pt.width_pct,100) width_pct,IFNULL(pt.type,'elements') type
              FROM a_projects p
              LEFT JOIN a_proj_elements pe
                ON p.project_id = pe.project_id
               AND pe.position > 0
              LEFT JOIN a_proj_tabs pt
                ON p.project_id = pt.project_id
               AND pe.tab_id = pt.tab_id 
             WHERE p.project_id = " . $proj . "
             ORDER BY pe.tab_id";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 16 in proj_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        array_push($list, array(
            "id" => (int) $row['id'],
            "width_pct" => (int) $row['width_pct'],
            "type" => $row['type']
        ));
    }
    // if (count($list) == 0){
    //     array_push($list,array(
    //         "id"=>0,

    //         ));
    // }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- get links list for project                                   
// --------------------------------------------------------------------------------------
function proj_get_lnk_list($id, $prop)
{
    global $con;

    $proj = $id['proj'];

    $filter = '';
    if ($prop != null) {
        foreach ($prop as $attr => $val) {
            $exists = "EXISTS(
                            SELECT 1
                              FROM a_proj_link_elements e
                             WHERE e.project_id = " . $proj . "
                               AND e.element_id = " . $val . "
                               AND e.link_id = l.link_id)";
            switch ($attr) {
                case "exclude_element":
                    $filter .= " AND NOT " . $exists;
                    break;
            }
        }
    }

    $list = array();

    $sql = "SELECT l.link_id, l.name, l.description,l.research_id
              FROM a_proj_links l
             WHERE l.project_id = " . $proj . "
            " . $filter . "
            ORDER BY l.link_id";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 17 in proj_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        array_push($list, lnk_prop(array(
            "proj" => $proj,
            "link" => $row['link_id']
        ), $row));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ----                           
// --------------------------------------------------------------------------------------
function proj_objects_to_reload($prop)
{
    global $con, $reload;

    // $elm_list = array();
    $in_list = null;
    $points_reload = false;
    $segments_reload = false;

    switch ($prop['object_type']) {
        case 'res_part':
            $cat = $prop['cat'];
            switch ($prop['action']) {
                case 'new':
                case 'update':
                case 'delete':
                    $elmListObj = proj_get_cat_elements($cat);
                    // $elm_list = $elmListObj['elm_list'];
                    if (!is_null($elmListObj)) {
                        $in_list = $elmListObj['in_list'];
                    }
                    $points_reload = true;
                    break;
            }
            break;
    }

    if ($in_list != null) {
        $sql_set = '';
        $sep = '';
        if ($points_reload) {
            $sql_set .= $sep . "points_generated = " . ($points_reload ? "FALSE" : "TRUE");
            $sep = ',';
        }
        if ($segments_reload) {
            $sql_set .= $sep . "segments_generated = " . ($segments_reload ? "FALSE" : "TRUE");
            $sep = ',';
        }
        if ($sql_set != '') {
            $sql = "UPDATE a_proj_elm_sequence 
                    SET " . $sql_set . "  
                    WHERE project_id = " . $reload['proj'] . "
                    AND element_id " . $in_list;
            $result = mysqli_query($con, $sql);
            if (!$result) {
                exit_error('Error 3 in proj_func.php: ' . mysqli_error($con));
            }
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- get elements to reload                    
// --------------------------------------------------------------------------------------
function proj_get_cat_elements($cat)
{
    global $con, $objects_to_reload, $reload;

    $col_pred = '';
    if (array_key_exists('col', $cat)) {
        $col_pred = " AND el.collection_id = " . $cat['col'];
    }

    // $elm_list = array();
    $in_list = array();

    $sql = "SELECT el.element_id
              FROM view_proj_link_elm_col el
             WHERE el.project_id = " . $reload['proj'] . "
               AND el.research_id = " . $cat['res'] . "
               " . $col_pred . "
               AND el.element_id IN(SELECT e.element_id
                                      FROM a_proj_elements e
                                     WHERE e.project_id = " . $reload['proj'] . " 
                                       AND e.position > 0) 
            GROUP BY el.element_id";
    $result = mysqli_query($con, $sql);
    if (!$result) {
        exit_error('Error 33 in proj_func.php: ' . mysqli_error($con));
    }
    while ($row = mysqli_fetch_array($result)) {
        array_push($objects_to_reload['elements'], (int) $row['element_id']);
        array_push($in_list, $row['element_id']);
    }

    if (count($in_list) == 0) {
        return null;
    }

    return array(
        // "elm_list"=>$elm_list,
        "in_list" => inList($in_list)
    );
}

// --------------------------------------------------------------------------------------
// ---- delete project                                   
// --------------------------------------------------------------------------------------
// function proj_delete($id){
//     global $con;

//     $proj = $id['proj'];

//     $delete_where = "WHERE project_id = ".$proj;

//     $sql = "DELETE FROM a_projects ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 21 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_elements ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 22 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_elm_link ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 23 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_elm_parts ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 24 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_elm_research ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 25 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_elm_sequence ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 26 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_elm_seq_divisions ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 27 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_links ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 28 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_link_collections ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 29 in proj_func.php: ' . mysqli_error($con));
//     }

//     $sql = "DELETE FROM a_proj_link_elements ".$delete_where;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 30 in proj_func.php: ' . mysqli_error($con));
//     }
// }
?>