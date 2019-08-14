<?php
//
// Description
// -----------
// This script is to run the tnc script once a minute from cron to query devices configured.
//

//
// Initialize Moss by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'cron', 'private', 'logMsg');

//
// Check to see if listener is running
//
if( isset($ciniki['config']['qruqsp.43392']['listener']) && $ciniki['config']['qruqsp.43392']['listener'] == 'active' ) {
    exec('ps ax | grep rtl_433_listen.php |grep -v grep', $pids);
    if( count($pids) == 0 ) {
        //
        // Start the listener
        //
        // FIXME: Update to used the config file root_dir
        //
        error_log("Starting Listener: qruqsp-mods/43392/scripts/rtl_433_listen.php");
        exec('php /ciniki/sites/qruqsp.local/site/qruqsp-mods/43392/scripts/rtl_433_listen.php >> ' . $ciniki['config']['qruqsp.core']['log_dir'] . '/43392.log 2>&1 &');
    }
}

exit(0);
?>
