<?php

function xmldb_block_welcomearea_upgrade($oldversion = 0) {
    global $db, $CFG;
    $result = true;

    if ($result && $oldversion < 2013010700) {
        /// Define table welcomearea to be renamed to block_welcomearea
        $table = new XMLDBTable('welcomearea');

        /// Launch rename table for welcomearea
        $result = $result && rename_table($table, 'block_welcomearea');


        /// Define table welcomearearules to be renamed to block_welcomearearules
        $table = new XMLDBTable('welcomearearules');

        /// Launch rename table for welcomearearules
        $result = $result && rename_table($table, 'block_welcomearearules');


    }


    return result;
}


