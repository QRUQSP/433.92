#!/usr/bin/env php
<?php
//
// Description
// -----------
// This script will launch rtl_433 to listen for devices transmitting
// on 433.92Mhz.
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
// Load tenant modules
//
$strsql = "SELECT ciniki_tenants.status AS tenant_status, "
    . "ciniki_tenant_modules.status AS module_status, "
    . "ciniki_tenant_modules.package, ciniki_tenant_modules.module, "
    . "CONCAT_WS('.', ciniki_tenant_modules.package, ciniki_tenant_modules.module) AS module_id, "
    . "ciniki_tenant_modules.flags, "
    . "(ciniki_tenant_modules.flags&0xFFFFFFFF00000000)>>32 as flags2, "
    . "ciniki_tenant_modules.ruleset "
    . "FROM ciniki_tenants, ciniki_tenant_modules "
    . "WHERE ciniki_tenants.id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "AND ciniki_tenants.id = ciniki_tenant_modules.tnid "
    // Get the options and mandatory module
    . "AND (ciniki_tenant_modules.status = 1 || ciniki_tenant_modules.status = 2 || ciniki_tenant_modules.status = 90) "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.tenants', 'modules', 'module_id');
if( $rc['stat'] != 'ok' ) {
    return $rc;
}
if( !isset($rc['modules']) ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.15', 'msg'=>'No modules enabled'));
}
$ciniki['tenant']['modules'] = $rc['modules'];

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
if( isset($ciniki['config']['qruqsp.43392']['rtl_433_cmd']) && $ciniki['config']['qruqsp.43392']['rtl_433_cmd'] != '' ) {
    $rtl_cmd = $ciniki['config']['qruqsp.43392']['rtl_433_cmd'];
} else {
    $rtl_cmd = 'rtl_433';
}
if( isset($ciniki['config']['qruqsp.43392']['all.devices']) && $ciniki['config']['qruqsp.43392']['all.devices'] == 'yes' ) {
    $cmd = "$rtl_cmd -G -U -F json";
} else {
    $cmd = "$rtl_cmd -R 2 -R 3 -R 8 -R 11 -R 12 -R 13 -R 14 -R 16 -R 18 -R 19 -R 20 -R 21 -R 25 -R 26 -R 31 -R 32 -R 34 -R 35 -R 36 -R 37 -R 38 -R 39 -R 40 -R 41 -R 42 -R 47 -R 50 -R 52 -R 53 -R 54 -R 55 -R 56 -R 57 -R 66 -R 69 -R 71 -R 73 -R 74 -R 75 -R 76 -R 77 -R 78 -R 79 -R 84 -R 92 -R 94 -R 97 -U -F json";
}
$handle = popen($cmd, "r");
$exit = 'no';
$line = '';
$prev_line = '';
$updater_count = 0;
while( $exit == 'no' ) {
    $byte = fread($handle, 1);
    
    if( $byte == "\n" ) {
        $rc = qruqsp_43392_rtl433ProcessLine($ciniki, $tnid, $line, $devices);
        if( $rc['stat'] != 'ok' ) {
            print_r(json_encode($rc));
            print "\n";
            print_r($line);
            print "\n";
        }
        $prev_line = $line;
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
