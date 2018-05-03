<?php
//
// Description
// -----------
// This function checks the certification expirations for any expiration messages that should be sent.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
//
function qruqsp_43392_cron_jobs(&$ciniki) {
    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for fatt jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');

    //
    // Check to see if listener is running
    //
    if( isset($ciniki['config']['qruqsp.43392']['listener']) && $ciniki['config']['qruqsp.43392']['listener'] == 'active' ) {
        exec('ps ax | grep rtl_433_listen.php |grep php', $pids);
        if( count($pids) == 0 ) {
            //
            // Start the listener
            //
            error_log("Starting Listener: qruqsp-mods/43392/scripts/rtl_433_listen.php");
            exec('php /ciniki/sites/qruqsp.local/site/qruqsp-mods/43392/scripts/rtl_433_listen.php >> ' . $ciniki['config']['qruqsp.core']['log_dir'] . '/43392.log 2>&1 &');
        }

    }

    return array('stat'=>'ok');
}
