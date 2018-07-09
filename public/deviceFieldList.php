<?php
//
// Description
// -----------
// This method will return the list of Device Fields for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Device Field for.
//
// Returns
// -------
//
function qruqsp_43392_deviceFieldList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', '43392', 'private', 'checkAccess');
    $rc = qruqsp_43392_checkAccess($ciniki, $args['tnid'], 'qruqsp.43392.deviceFieldList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of fields
    //
    $strsql = "SELECT qruqsp_43392_device_fields.id, "
        . "qruqsp_43392_device_fields.device_id, "
        . "qruqsp_43392_device_fields.fname, "
        . "qruqsp_43392_device_fields.name, "
        . "qruqsp_43392_device_fields.flags, "
        . "qruqsp_43392_device_fields.last_value, "
        . "qruqsp_43392_device_fields.last_date "
        . "FROM qruqsp_43392_device_fields "
        . "WHERE qruqsp_43392_device_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
        array('container'=>'fields', 'fname'=>'id', 
            'fields'=>array('id', 'device_id', 'fname', 'name', 'flags', 'last_value', 'last_date')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['fields']) ) {
        $fields = $rc['fields'];
        $field_ids = array();
        foreach($fields as $iid => $field) {
            $field_ids[] = $field['id'];
        }
    } else {
        $fields = array();
        $field_ids = array();
    }

    return array('stat'=>'ok', 'fields'=>$fields, 'nplist'=>$field_ids);
}
?>
