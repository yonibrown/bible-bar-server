<?php

include 'connect_db.php';
include 'text_func.php';
include 'bar_func.php';
include 'proj_func.php';
include 'link_func.php';
include 'elm_func.php';
include 'res_func.php';
include 'residx_func.php';
include 'board_func.php';

if (empty($_POST)) {
    $_POST = json_decode(file_get_contents('php://input'), true);
}

$type = $_POST['type'];
$oper = $_POST['oper'];

$id = json_decode($_POST['id'], true);
$prop = json_decode($_POST['prop'], true);
// $id = $_POST['id'];
// $prop = $_POST['prop'];

if (array_key_exists('reload', $_POST)) {
    $reload = json_decode($_POST['reload'], true);
    // $reload = $_POST['reload'];
// if (empty($reload)){
} else {
    $reload = array();
}

if (array_key_exists('file', $_FILES)) {
    $file = file_get_contents($_FILES['file']['tmp_name']);
    $file = str_replace(array('"', '('), '', $file);
    // if (empty($file)){
} else {
    $file = "";
}

$reply = array();

$objects_to_reload = array(
    'elements' => array(),
    "links" => array(),
    "researches" => array()
);

switch ($type) {
    case "element":
        switch ($oper) {
            // create new element
            case "new":
                $reply["data"] = elm_create($prop);
                break;

            // set attributes
            case "set":
                $reply['data'] = elm_set($id, $prop);
                break;

            // text methods
            // -------------

            // get segment                                     
            case "get_segment":
                $reply['data'] = txt_get_segment($id);
                break;

            // bar methods
            // ------------

            // calculate segments in bar for display
            case "get_segments":
                $reply['data'] = bar_calc_segments($id, $prop);
                break;

            // calculate points in bar for display
            case "get_points":
                $reply['data'] = bar_calc_points($id, $prop);
                break;
        }
        break;

    case "board":
        switch ($oper) {
            // 
            case "add_field":
                $reply['data'] = brd_add_field($id, $prop);
                break;
            // 
            case "add_line":
                $reply['data'] = brd_add_line($id, $prop);
                break;
        }
        break;

    case "brd_field":
        switch ($oper) {
            // set field of board
            case "set":
                brdfld_set_field($id, $prop);
                break;
        }
        break;

    case "brd_line":
        switch ($oper) {
            // 
            case "new_content":
                brdlin_new_content($id, $prop);
                break;

            case "set":
                brdlin_set_line($id, $prop);
                break;
        }
        break;

    case "brd_content":
        switch ($oper) {
            // 
            case "set":
                $reply["data"] = brdcnt_set_content($id, $prop);
                break;
        }
        break;


    case "link":
        switch ($oper) {
            // create new link
            case "new":
                $reply["data"] = lnk_create($prop);
                break;

            // // set link attributes
            case "set":
                lnk_set($id, $prop);
                break;

            // get link attributes
            case "get":
                $reply['data'] = lnk_get($id);
                break;

            // update category in link
            case "upd_cat":
                lnk_upd_category($id, $prop);
                break;

            // 
            case "add_cat":
                lnk_add_categories($id, $prop);
                break;

            // add element to link
            case "add_elm":
                lnk_add_element($id, $prop);
                break;

            // remove element to link
            case "remove_elm":
                lnk_remove_element($id, $prop);
                break;
        }
        break;

    case "research":
        switch ($oper) {
            // set research attributes
            case "set":
                res_set($id, $prop);
                break;

            // get category list for research                                   
            case "get_col_list":
                $reply['data'] = res_get_col_list($id);
                break;

            // get point list for research                                   
            case "get_prt_list":
                $reply['data'] = res_get_prt_list($id, $prop);
                break;

            // create new category in research
            case "new_collection":
                $reply["data"] = res_new_collection($id, $prop);
                break;

            // create new category in research
            case "update_collection":
                res_update_collection($id, $prop);
                break;

            // remove categories from research
            case "delete_collections":
                res_del_collections($id, $prop);
                break;

            // create new part in research
            case "new_part":
                $reply["data"] = res_new_part($id, $prop);
                break;

            // update point in research
            case "update_parts":
                res_upd_parts($id, $prop);
                break;

            // delete point in research
            case "delete_parts":
                res_delete_parts($id, $prop);
                break;

            // update point in research
            case "duplicate":
                $reply['data'] = res_duplicate($id, $prop);
                break;

            // set research attributes
            case "upload_parts":
                $reply['data'] = res_DICTA_upload($id, $file);
                break;
        }
        break;

    case "res_index":
        switch ($oper) {
            // get research index levels list
            case "get_levels":
                $reply['data'] = residx_get_levels($id, $prop);
                break;

            // 
            case "get_divisions":
                $reply['data'] = residx_get_divisions($id, $prop);
                break;

            // 
            case "get_key":
                $reply['data'] = residx_position_to_key($id, $prop);
                break;

            // 
            // case "get_division":
            //     $reply['data'] = residx_position_to_div($id, $prop);
            //     break;
        }
        break;

    case "project":
        switch ($oper) {
            // // get list of projects in the system
            case "get_list":
                $reply['list'] = proj_get_list();
                break;

            // get project                                     
            case "get":
                $reply['data'] = proj_get($id);
                break;

            // // set project attributes
            case "set":
                proj_set($id, $prop);
                break;

            // // create new project
            case "new":
                $reply["id"] = proj_create($prop);
                break;

            // load elements to project
            case "save_elements":
                proj_save_elements($id, $prop);
                break;
        }
        break;
}

$reply['objects_to_reload']['elements'] = array_unique($objects_to_reload['elements']);

echo json_encode($reply);
include 'disconnect_db.php';
?>