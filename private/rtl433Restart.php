<?php
//
// Description
// -----------
// This function will restart the rtl_433 program and listener wrapper.
// It's not elegant but works for now. It would be nice if the PID for
// the listener could be stored in the database and a HUP issued to 
// reload the sensor list.
//
// Arguments
// ---------
// ciniki:
// tnid:                The ID of the tenant to check the session user against.
// line:                The line received from rtl_433.
//
function qruqsp_43392_rtl433Restart(&$ciniki, $tnid) {

    //
    // Only restart the RTL 433 if listener is configured in ciniki-api.ini
    //
    if( isset($ciniki['config']['qruqsp.43392']['listener']) && $ciniki['config']['qruqsp.43392']['listener'] == 'active' ) {
        exec('sudo killall rtl_433');
        sleep(1);
        exec('php /ciniki/sites/qruqsp.local/site/qruqsp-mods/43392/scripts/rtl_433_listen.php >> ' . $ciniki['config']['qruqsp.core']['log_dir'] . '/43392.log 2>&1 &');
    }

    return array('stat'=>'ok');
}
?>
