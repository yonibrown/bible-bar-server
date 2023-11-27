<?php

include 'connect_db.php';
include 'text_func.php';
include 'bar_func.php';
include 'proj_func.php';
include 'link_func.php';
include 'elm_func.php';
include 'res_func.php';

if (empty($_POST)){
    $_POST = json_decode(file_get_contents('php://input'), true);
}

$type = $_POST['type'];
$id = $_POST['id'];
$oper = $_POST['oper'];
$prop = $_POST['prop'];

switch ($type){
    case "element":
        switch ($oper) {
            // get element                       
            case "get":
                $reply['data'] = elm_get($id);
                break;

            // create new element
            case "new":
                $reply["data"] = elm_create($prop);
                break;

            // set attributes
            case "set":
                $reply['data'] = elm_set($id,$prop);
                break;

            // // get linked categories                                     
            // case "get_categories":
            //     $reply['data'] = elm_get_categories($id,$prop);
            //     break;

            // // get links                                     
            // case "get_links":
            //     $reply['data'] = elm_get_links($id);
            //     break;

            // // text methods
            // // -------------

            // get segment                                     
            case "get_segment":
                $reply['data'] = txt_get_segment($id);
                break;

            // // get points                                     
            // case "get_text_points":
            //     $reply['data'] = txt_get_points($id,$prop);
            //     break;

            // // bar methods
            // // ------------
            
            // calculate segments in bar for display
            case "get_segments":
                $reply['data'] = bar_calc_segments($id,$prop);
                break;

            // calculate points in bar for display
            case "get_points":
                $reply['data'] = bar_calc_points($id,$prop);
                break;
        }
        break;    

    case "link":
        switch ($oper) {
            // // create new link
            // case "new":
            //     $reply["id"] = lnk_create($prop);
            //     break;

            // // get categories in link            
            // case "get_categories":
            //     $reply['data'] = lnk_get_categories($id);
            //     break;

            // // get categories in link            
            // // case "get_cat_hierarchy":
            // //     $reply['data'] = lnk_get_cat_hierarchy($id);
            // //     break;

            // update category in link
            case "upd_cat":
                lnk_upd_category($id,$prop);
                break;

            // // add categories to link
            // case "add_cat":
            //     $reply['data'] = lnk_add_categories($id,$prop);
            //     break;

            // // remove categories from link
            // case "remove_cat":
            //     $reply['data'] = lnk_remove_categories($id,$prop);
            //     break;

            // add element to link
            case "add_elm":
                $reply['data'] = lnk_add_element($id,$prop);
                break;

            // remove element to link
            case "remove_elm":
                $reply['data'] = lnk_remove_element($id,$prop);
                break;
                
            // // create new link
            // // case "new":
            // //     $reply["id"] = lnk_create($prop);
            // //     break;
        }
        break;    

    case "research":
        switch ($oper) {
            // // get list of researches in the system
            // case "get_list":
            //     $reply['list'] = res_get_list();
            //     break;

            // // get research                                     
            // case "get":
            //     $reply['data'] = res_get($id);
            //     break;

            // // set research attributes
            // case "set":
            //     res_set($id,$prop);
            //     break;

            // // create new research
            // case "new":
            //     $reply["id"] = res_create($prop);
            //     break;

            // // get category list for research                                   
            case "get_col_list":
                $reply['data'] = res_get_col_list($id);
                break;

            // get point list for research                                   
            case "get_prt_list":
                $reply['data'] = res_get_prt_list($id,$prop);
                break;

            // // create new category in research
            case "new_collection":
                $reply["data"] = res_new_collection($id,$prop);
                break;

            // create new category in research
            case "update_collection":
                res_update_collection($id,$prop);
                break;

            // // remove categories from research
            case "delete_collections":
                res_del_collections($id,$prop);
                break;

            // // create new part in research
            case "new_part":
                $reply["data"] = res_new_part($id,$prop);
                break;

            // // delete part from research
            // case "delete_part":
            //     res_del_part($id,$prop);
            //     break;

            // update point in research
            case "update_parts":
                res_upd_parts($id,$prop);
                break;

            // delete point in research
            case "delete_parts":
                res_delete_parts($id,$prop);
                break;

            // // update point in research
            // case "upd_pt":
            //     res_upd_point($id,$prop);
            //     break;

            // // get list of researches in the system
            // case "get_seq_list":
            //     $reply['list'] = res_get_sequences_list();
            //     break;
            
            // update point in research
            case "duplicate":
                $reply['data'] = res_duplicate($id,$prop);
                break;
        }
        break;    

    case "res_collection":
        switch ($oper) {
            // // get research collection
            // case "get":
            //     $reply['data'] = rescol_get($id);
            //     break;

            // // get research collection's indexes
            // case "get_indexes":
            //     $reply['data'] = rescol_get_indexes($id,$prop);
            //     break;
        }
        break;    

    case "res_index":
        switch ($oper) {
            // get research index
            case "get":
                $reply['data'] = residx_get($id);
                break;

            // // get research index levels list
            // case "get_levels":
            //     $reply['data'] = residx_get_levels($id,$prop);
            //     break;
            // // get research index level's divisions list
            // case "get_level_divisions":
            //     $reply['data'] = residx_get_level_divisions($id,$prop);
            //     break;

            // 
            case "get_divisions":
                $reply['data'] = residx_get_divisions($id,$prop);
                break;
        }
        break;    

    case "project":
        switch ($oper) {
            // // get list of projects in the system
            // case "get_list":
            //     $reply['list'] = proj_get_list();
            //     break;

            // get project                                     
            case "get":
                $reply['data'] = proj_get($id);
                break;

            // // set project attributes
            // case "set":
            //     proj_set($id,$prop);
            //     break;

            // // create new project
            // case "new":
            //     $reply["id"] = proj_create($prop);
            //     break;

            // // delete project
            // case "delete":
            //     proj_delete($id);
            //     break;

            // // get element list for project                                   
            // case "get_elm_list":
            //     $reply['data'] = proj_get_elm_list($id);
            //     break;

            // load elements to project
            case "save_elements":
                proj_save_elements($id,$prop['elements']);
                break;

            // // get links list for project                                   
            // case "get_lnk_list":
            //     $reply['data'] = proj_get_lnk_list($id,$prop);
            //     break;

            // // get categories list for project                                   
            // case "get_res_list":
            //     $reply['data'] = proj_get_res_list($id);
            //     break;

            // // add point to default research
            // case "add_point":
            //     proj_add_point($id);
            //     break;

            // // get default research for project                                   
            // case "get_def_res":
            //     $reply['data'] = proj_get_def_res($id);
            //     break;
        }
        break;    

    // case "sequence":
    //     switch ($oper) {
    //         // get list of projects in the system
    //         case "get_list":
    //             $reply['list'] = proj_get_list();
    //             break;

    //     }
    //     break;    
}

include 'disconnect_db.php';
?>