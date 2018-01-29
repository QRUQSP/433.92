<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:            The ID of the tenant to get events for.
//
// args:            Extra arguments
//
//
// Returns
// -------
//
function qruqsp_43392_hooks_webOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['qruqsp.43392']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.4', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'tnid', $tnid, 'ciniki.web', 'settings', 'page-43392');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = isset($rc['setings']) ? $rc['settings'] : array();

    $options = array();

    $pages['qruqsp.43392'] = array('name'=>'433.92 Mhz', 'options'=>$options);

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
