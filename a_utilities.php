<?php
include 'connect_db.php';
include 'res_func.php';

// invoke with url: http://localhost/bibar-vue-php/a_utilities.php?util=upd_word_count

$reply = array('field' => 'nothing');

$util = $_GET['util'];
if ($util == 'regen_res') {
    $res = $_GET['res'];
    echo 'regenerate columns for research:' . $res;
    $reply = res_regen($res);
}

if ($util == 'res_index') {
    // sequence - טקסט
    // $reply = res_get_sequences_list();

    // index - חלוקה
    // $id = array('res'=>1,'col'=>1);
    // $reply = rescol_get_indexes($id);

    // divisions - חטיבות
    // $id = array('res'=>1,'col'=>1,'idx'=>1);
    // $prop = array('level'=>2);
    // $reply = residx_get_level_divisions($id,$prop);
}

echo '<br>reply:<br>';
include 'disconnect_db.php';
?>