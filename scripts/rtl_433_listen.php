#!/usr/bin/env php
<?php
//
// Description
// -----------
// This script will launch rtl_power to listen in a frequency range and import
// the dbm values into the database.
//

//
// Initialize CINIKI by including the ciniki-api.ini
//
$start_time = microtime(true);
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}

require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');

print "$ciniki_root\n";
//
// Initialize Ciniki
//
$rc = ciniki_core_init($ciniki_root, 'json');
if( $rc['stat'] != 'ok' ) {
    print "ERR: Unable to initialize Ciniki\n";
    exit;
}

//
// Setup the $ciniki variable to hold all things qruqsp.  
//
$ciniki = $rc['ciniki'];

//
// Load required modules
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbConnect');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
ciniki_core_loadMethod($ciniki, 'qruqsp', '43392', 'private', 'rtl433ProcessLine');

//
// Check which tnid we should use
//
if( isset($ciniki['config']['qruqsp.43392']['tnid']) ) {
    $tnid = $ciniki['config']['qruqsp.43392']['tnid'];
} elseif( isset($ciniki['config']['ciniki.core']['qruqsp_tnid']) ) {
    $tnid = $ciniki['config']['ciniki.core']['qruqsp_tnid'];
} else {
    $tnid = $ciniki['config']['ciniki.core']['master_tnid'];
}

//
// device cache so they don't have to be loaded each time from database
//
$devices = array();

//
// Setup the rtl command to run.
// -G - Use the full list of protocols to listen for
// -U - specify time in UTC
// -F json - output in json format for each entry
//
$rtl_cmd = 'rtl_433';
$cmd = "$rtl_cmd -G -U -F json";

$handle = popen($cmd, "r");
$exit = 'no';
$line = '';
$prev_sample = null;
$updater_count = 0;
while( $exit == 'no' ) {
    $byte = fread($handle, 1);
    
    if( $byte  == "\n" ) {
        $rc = qruqsp_43392_rtl433ProcessLine($ciniki, $tnid, $line, $devices);
        if( $rc['stat'] != 'ok' ) {
            print_r(json_encode($rc));
            print "\n";
            print_r($line);
            print "\n";
        }
        $line = '';
        $updater_count++;
    } else {
        $line .= $byte;
    }

    if( feof($handle) ) {
        break;
    }
}
?>
