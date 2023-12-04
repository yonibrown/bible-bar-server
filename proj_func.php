<?php
// --------------------------------------------------------------------------------------
// ---- get list of projects in the system
// --------------------------------------------------------------------------------------
// function proj_get_list(){
//     global $con;
//     $list = array();

//     $sql = "SELECT project_id id,name,description
//                 FROM a_projects
//                 ORDER BY project_id";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 1 in proj_func.php: ' . mysqli_error($con));
//     }
//     while($row = mysqli_fetch_array($result)) {
//         array_push($list,array(
//             "id"=>$row['id'],
//             "name"=>$row['name'],
//             "desc"=>$row['description']
//         ));
//     }
//     return $list;
// }

// --------------------------------------------------------------------------------------
// ---- get project                                     
// --------------------------------------------------------------------------------------
function proj_get($id){
    global $con;

    $proj = $id['proj'];
    $sql = "SELECT name,description
            FROM a_projects
            WHERE project_id = ".$proj;
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 2 in proj_func.php: ' . mysqli_error($con));
    }
    $row = mysqli_fetch_array($result);

    $elm_list = proj_get_elm_list($id);
    $lnk_list = proj_get_lnk_list($id,array('dummy'=>''));
    $res_list = proj_get_res_list($id,array('dummy'=>''));
    
    $attr = array(
        'name'=>$row['name'],
        'desc'=>$row['description'],
        'elements'=>$elm_list,
        'links'=>$lnk_list,
        'researches'=>$res_list
    );
    return $attr;
}

// --------------------------------------------------------------------------------------
// ---- set project attributes
// --------------------------------------------------------------------------------------
// function proj_set($id,$prop){
//     global $con;

//     $proj = $id['proj'];
//     $sql_set = '';
//     $sep = '';
//     foreach($prop as $attr => $val) {
//         switch ($attr) {
//             default:
//                 $sql_set = $sql_set.$sep.$attr." = ".$val;
//                 $sep = ',';
//                 break;
//         }   
//     }

//     $sql = "UPDATE a_projects 
//             SET ".$sql_set."  
//             WHERE project_id = ".$proj;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 3 in proj_func.php: ' . mysqli_error($con));
//     }
// }

// --------------------------------------------------------------------------------------
// ---- create new project
// --------------------------------------------------------------------------------------
// function proj_create($prop){
//     global $con;
//     $sql = "SELECT MAX(project_id) project_id
//                 FROM a_projects";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 4 in proj_func.php: ' . mysqli_error($con));
//     }
//     $row = mysqli_fetch_array($result);
//     $proj = $row['project_id']+1;

//     $sql = "INSERT INTO a_projects
//                 (project_id, 
//                 name, 
//                 description) 
//             VALUES(".$proj.", 
//                 '".$prop['name']."', 
//                 '".$prop['desc']."')";
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 5 in proj_func.php: ' . mysqli_error($con));
//     }

//     // create default research for project
//     $res_prop = array("name"=>"project","desc"=>"project default research","proj"=>$proj);
//     $res = res_create($res_prop);
//     res_new_collection($res,array("name"=>"default","desc"=>"default collection"));

//     return array("proj"=>$proj);
// }

// --------------------------------------------------------------------------------------
// ---- get default research of project
// --------------------------------------------------------------------------------------
// function proj_get_def_res($id){
//     global $con;

//     $proj = $id['proj'];
//     $reply = array();

//     // get the default research
//     $sql = "SELECT research_id
//             FROM a_researches
//             WHERE project_id = ".$proj;
//     $result = mysqli_query($con,$sql);
//     if (!$result) {
//         exit_error('Error 6 in proj_func.php: ' . mysqli_error($con));
//     }
//     if($row = mysqli_fetch_array($result)){
//         $reply['res'] = $row['research_id'];
//     } else {
//         // if there is no default research for this project, create one
//         $res_prop = array("name"=>"project","desc"=>"default research","proj"=>$proj);
//         $res = res_create($res_prop);
//         $reply['res'] = $res['res'];

//         // create a collection in the new research
//         res_new_collection($res,array("name"=>"default","desc"=>"default collection"));
//     }
//     $cat = array('res'=>$reply['res'],'col'=>1);

//     // get the link for the default research (if exists)
//     // $sql = "SELECT l.link_id
//     //           FROM a_proj_link_collections l
//     //          WHERE l.project_id =  ".$proj."
//     //            AND l.research_id = ".$reply['res']."
//     //            AND l.collection_id = 1";
//     // $result = mysqli_query($con,$sql);
//     // if (!$result) {
//     //     exit_error('Error 31 in proj_func.php: ' . mysqli_error($con));
//     // }
//     // if($row = mysqli_fetch_array($result)){
//     //     $reply['lnk'] = $row['link_id'];
//     // } else {
//     //     $linkId = lnk_create(array("proj"=>$id['proj']));
//     //     lnk_add_categories($linkId,array("type"=>"category","data"=>$cat));
//     //     $reply['lnk'] = $linkId;
//     //     // create new element
//     //     // $newelm = jjj;
//     // }

//     return $reply;
// }

// --------------------------------------------------------------------------------------
// ---- save elements display in project
// --------------------------------------------------------------------------------------
function proj_save_elements($id,$elements){
    global $con;

    $proj = $id['proj'];

    proj_delete_unlisted_elements($id,$elements);
    // proj_clear_redundant_data();

    // update display for elements in the list
    // ----------------------------------------
    foreach ($elements as $elm) {
        $sql = "UPDATE a_proj_elements
                   SET position=".$elm['position']."
                 WHERE project_id = ".$proj."
                   AND element_id = ".$elm['id'];
        $result = mysqli_query($con,$sql);
        if (!$result) {
            exit_error('Error 14 in proj_func.php: ' . mysqli_error($con));
        }
    }
}

// --------------------------------------------------------------------------------------
// ----                                 
// --------------------------------------------------------------------------------------
function proj_delete_unlisted_elements($id,$elements){
    global $con;

    $proj = $id['proj'];

    $elm_ids = array();
    foreach ($elements as $elm) {
        array_push($elm_ids,$elm['id']);
    }
    $elm_ids_str = implode(",",$elm_ids);
    $delete_where = "WHERE project_id = ".$proj." AND element_id NOT IN(".$elm_ids_str.")";

    $sql = "UPDATE a_proj_elements
               SET position=0 
               ".$delete_where;
    $result = mysqli_query($con,$sql);
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
function proj_get_res_list($id){
    global $con;

    $proj = $id['proj'];
    $list = array();
    $sql = "SELECT r.research_id,r.name_heb res_name
              FROM a_researches r
             WHERE r.research_id in(
                 SELECT pa.research_id
                   FROM a_proj_elm_research pa
                   JOIN a_proj_elements pe
                     ON pa.project_id = pe.project_id
                    AND pa.element_id = pe.element_id
                  WHERE pe.project_id = ".$proj."
                    AND pe.position > 0)
                OR r.research_id in(
                 SELECT pa.research_id
                   FROM a_proj_elm_parts pa
                   JOIN a_proj_elements pe
                     ON pa.project_id = pe.project_id
                    AND pa.element_id = pe.element_id
                  WHERE pe.project_id = ".$proj."
                    AND pe.position > 0)
                OR r.research_id in(
                 SELECT research_id
                   FROM a_proj_link_collections
                  WHERE project_id = ".$proj.")
             ORDER BY r.project_id DESC,r.research_id";
            //  OR r.project_id = ".$proj."
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 15 in proj_func.php: ' . mysqli_error($con));
    }
    while($row = mysqli_fetch_array($result)) {
        $catList = res_get_col_list(array("res"=>$row['research_id']));
        array_push($list,array("id"=>(int)$row['research_id'],
                               "name"=>$row['res_name'],
                               "collections"=>$catList
                            ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- get elements list for project                                   
// --------------------------------------------------------------------------------------
function proj_get_elm_list($id){
    global $con;

    $proj = $id['proj'];
    $list = array();

    $sql = "SELECT pe.element_id id,type,name,
                   opening_element,
                   pe.position,
                   show_props
            FROM a_proj_elements pe
            WHERE pe.project_id = ".$proj."
              AND position > 0
            ORDER BY position";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 16 in proj_func.php: ' . mysqli_error($con));
    }
    while($row = mysqli_fetch_array($result)) {
        $elmId = array(
            "proj"=>$proj,
            "elm"=>$row['id']
        );
        array_push($list,elm_prop($elmId,$row));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ---- get links list for project                                   
// --------------------------------------------------------------------------------------
function proj_get_lnk_list($id,$prop){
    global $con;

    $proj = $id['proj'];

    $filter = '';
    if ($prop != null){
        foreach($prop as $attr => $val) {
            $exists = "EXISTS(
                            SELECT 1
                              FROM a_proj_link_elements e
                             WHERE e.project_id = ".$proj."
                               AND e.element_id = ".$val."
                               AND e.link_id = l.link_id)";
            switch ($attr) {
                case "exclude_element":
                    $filter .= " AND NOT ".$exists;
                    break;
            }   
        }
    }

    $list = array();

    $sql = "SELECT l.link_id, l.name, l.description
              FROM a_proj_links l
             WHERE l.project_id = ".$proj."
            ".$filter."
            ORDER BY l.link_id";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 17 in proj_func.php: ' . mysqli_error($con));
    }
    while($row = mysqli_fetch_array($result)) {
        $catlist = lnk_get_categories(array(
            "proj"=>$proj,
            "link"=>$row['link_id']
        ));
        $elmlist = lnk_get_elements(array(
            "proj"=>$proj,
            "link"=>$row['link_id']
        ));
        array_push($list,array(
            "id"=>(int)$row['link_id'],
            "proj"=>(int)$proj,
            "name"=>$row['name'],
            "desc"=>$row['description'],
            "categories"=>$catlist,
            "elements"=>$elmlist
        ));
    }
    return $list;
}

// --------------------------------------------------------------------------------------
// ----                           
// --------------------------------------------------------------------------------------
function proj_objects_to_reload($prop){
    global $con,$objects_to_reload,$reload;
    
    // $elm_list = array();
    $in_list = '';
    $points_reload = false;
    $segments_reload = false;

    switch ($prop['object_type']){
        case 'res_part':
            $cat = $prop['cat'];
            switch($prop['action']){
                case 'new':
                case 'delete':
                    $elmListObj = proj_get_cat_elements($cat);
                    // $elm_list = $elmListObj['elm_list'];
                    $in_list = $elmListObj['in_list'];
                    $points_reload = true;
                    break;
            }
            break;

        case 'link_name':
            switch($prop['action']){
                case 'update':
                    $found = false;
                    foreach($objects_to_reload['links'] as $link){
                        if ($link['id'] == $prop['link']){
                            $found = true;
                            $link['actions']['name'] = $prop['name'];
                        }
                    }
                    if (!$found){
                        array_push($objects_to_reload['links'],array(
                            "id"=>$prop['link'],
                            "actions"=>array(
                                "name"=>$prop['name']
                            )
                        ));
                    }
                    break;
            }
            break;

        case 'research_name':
            switch($prop['action']){
                case 'update':
                    $found = false;
                    foreach($objects_to_reload['researches'] as $res){
                        if ($res['id'] == $prop['res']){
                            $found = true;
                            $res['actions']['name'] = $prop['name'];
                        }
                    }
                    if (!$found){
                        array_push($objects_to_reload['researches'],array(
                            "id"=>$prop['res'],
                            "actions"=>array(
                                "name"=>$prop['name']
                            )
                        ));
                    }
                    break;
            }
            break;
    }

    if ($in_list != ''){
        $sql_set = '';
        $sep = '';
        if ($points_reload){
            $sql_set .= $sep."points_generated = ".($points_reload?"FALSE":"TRUE");
            $sep = ',';
        }
        if ($segments_reload){
            $sql_set .= $sep."segments_generated = ".($segments_reload?"FALSE":"TRUE");
            $sep = ',';
        }
        if($sql_set != ''){
            $sql = "UPDATE a_proj_elm_sequence 
                    SET ".$sql_set."  
                    WHERE project_id = ".$reload['proj']."
                    AND element_id ".$in_list;
            $result = mysqli_query($con,$sql);
            if (!$result) {
                exit_error('Error 3 in proj_func.php: ' . mysqli_error($con));
            }
        }
    }
}

// --------------------------------------------------------------------------------------
// ---- get elements to reload                    
// --------------------------------------------------------------------------------------
function proj_get_cat_elements($cat){
    global $con,$objects_to_reload,$reload;

    $col_pred = '';
    if (array_key_exists('col',$cat)){
        $col_pred = " AND el.collection_id = ".$cat['col'];
    }

    // $elm_list = array();
    $in_list = array();

    $sql = "SELECT el.element_id
              FROM view_proj_link_elm_col el
             WHERE el.project_id = ".$reload['proj']."
               AND el.research_id = ".$cat['res']."
               ".$col_pred."
               AND el.element_id IN(SELECT e.element_id
                                      FROM a_proj_elements e
                                     WHERE e.project_id = ".$reload['proj']." 
                                       AND e.position > 0) 
            GROUP BY el.element_id";
    $result = mysqli_query($con,$sql);
    if (!$result) {
        exit_error('Error 33 in proj_func.php: ' . mysqli_error($con));
    }
    while($row = mysqli_fetch_array($result)) {
        array_push($objects_to_reload['elements'],array(
            "id"=>(int)$row['element_id'],
            "actions"=>array(
                "reload"=>true
            )
        ));
        array_push($in_list,$row['element_id']);
    }

    return array(
        // "elm_list"=>$elm_list,
        "in_list"=>inList($in_list)
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