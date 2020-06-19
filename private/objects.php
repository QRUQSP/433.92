<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_43392_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['device'] = array(
        'name' => 'Device',
        'sync' => 'yes',
        'o_name' => 'device',
        'o_container' => 'devices',
        'table' => 'qruqsp_43392_devices',
        'fields' => array(
            'model' => array('name'=>'Model'),
            'did' => array('name'=>'ID'),
            'name' => array('name'=>'Name'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            ),
        'history_table' => 'qruqsp_43392_history',
        );
    $objects['devicefield'] = array(
        'name' => 'Device Field',
        'sync' => 'yes',
        'o_name' => 'field',
        'o_container' => 'fields',
        'table' => 'qruqsp_43392_device_fields',
        'fields' => array(
            'device_id' => array('name'=>'Device', 'ref'=>'qruqsp.43392.devices'),
            'fname' => array('name'=>'JSON Field Name'),
            'name' => array('name'=>'Name', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'ftype' => array('name'=>'Field Type', 'default'=>'0'),
            'last_value' => array('name'=>'Last Value', 'default'=>''),
            'last_date' => array('name'=>'Last Value Date', 'default'=>''),
            ),
        'history_table' => 'qruqsp_43392_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
