<?php
//
// Description
// -----------
// This function returns the int to text mappings for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_43392_maps(&$ciniki) {
    //
    // Build the maps object
    //
    $maps = array();
    $maps['device'] = array('status'=>array(
        '10'=>'New',
        '30'=>'Active',
        '60'=>'Ignore',
    ));
    //
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
