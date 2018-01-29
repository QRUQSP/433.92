<?php
//
// Description
// -----------
// This method searchs for a Device Fields for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Device Field for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function qruqsp_43392_deviceFieldSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', '43392', 'private', 'checkAccess');
    $rc = qruqsp_43392_checkAccess($ciniki, $args['tnid'], 'qruqsp.43392.deviceFieldSearch');
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
        . "qruqsp_43392_device_fields.example_value "
        . "FROM qruqsp_43392_device_fields "
        . "WHERE qruqsp_43392_device_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
        array('container'=>'fields', 'fname'=>'id', 
            'fields'=>array('id', 'device_id', 'fname', 'name', 'flags', 'example_value')),
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
