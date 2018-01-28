<?php
//
// Description
// ===========
// This method will return all the information about an device field.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the device field is attached to.
// field_id:          The ID of the device field to get the details for.
//
// Returns
// -------
//
function qruqsp_43392_deviceFieldGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'field_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Device Field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', '43392', 'private', 'checkAccess');
    $rc = qruqsp_43392_checkAccess($ciniki, $args['tnid'], 'qruqsp.43392.deviceFieldGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Device Field
    //
    if( $args['field_id'] == 0 ) {
        $field = array('id'=>0,
            'device_id'=>'',
            'fname'=>'',
            'name'=>'',
            'flags'=>'0',
            'label'=>'',
            'example_value'=>'',
        );
    }

    //
    // Get the details for an existing Device Field
    //
    else {
        $strsql = "SELECT qruqsp_43392_device_fields.id, "
            . "qruqsp_43392_device_fields.device_id, "
            . "qruqsp_43392_device_fields.fname, "
            . "qruqsp_43392_device_fields.name, "
            . "qruqsp_43392_device_fields.flags, "
            . "qruqsp_43392_device_fields.label, "
            . "qruqsp_43392_device_fields.example_value "
            . "FROM qruqsp_43392_device_fields "
            . "WHERE qruqsp_43392_device_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_43392_device_fields.id = '" . ciniki_core_dbQuote($ciniki, $args['field_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'fields', 'fname'=>'id', 
                'fields'=>array('device_id', 'fname', 'name', 'flags', 'label', 'example_value'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.20', 'msg'=>'Device Field not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['fields'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.21', 'msg'=>'Unable to find Device Field'));
        }
        $field = $rc['fields'][0];
    }

    return array('stat'=>'ok', 'field'=>$field);
}
?>
