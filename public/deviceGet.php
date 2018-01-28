<?php
//
// Description
// ===========
// This method will return all the information about an device.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the device is attached to.
// device_id:          The ID of the device to get the details for.
//
// Returns
// -------
//
function qruqsp_43392_deviceGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'device_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Device'),
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
    $rc = qruqsp_43392_checkAccess($ciniki, $args['tnid'], 'qruqsp.43392.deviceGet');
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
    // Return default for new Device
    //
    if( $args['device_id'] == 0 ) {
        $device = array('id'=>0,
            'model'=>'',
            'did'=>'',
            'name'=>'',
            'status'=>'10',
        );
    }

    //
    // Get the details for an existing Device
    //
    else {
        $strsql = "SELECT qruqsp_43392_devices.id, "
            . "qruqsp_43392_devices.model, "
            . "qruqsp_43392_devices.did, "
            . "qruqsp_43392_devices.name, "
            . "qruqsp_43392_devices.status "
            . "FROM qruqsp_43392_devices "
            . "WHERE qruqsp_43392_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_43392_devices.id = '" . ciniki_core_dbQuote($ciniki, $args['device_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'devices', 'fname'=>'id', 
                'fields'=>array('model', 'did', 'name', 'status'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.15', 'msg'=>'Device not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['devices'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.16', 'msg'=>'Unable to find Device'));
        }
        $device = $rc['devices'][0];

        //
        // Get the list of fields
        //
        $strsql = "SELECT qruqsp_43392_device_fields.id, "
            . "qruqsp_43392_device_fields.device_id, "
            . "qruqsp_43392_device_fields.fname, "
            . "qruqsp_43392_device_fields.name, "
            . "qruqsp_43392_device_fields.flags, "
            . "qruqsp_43392_device_fields.label, "
            . "qruqsp_43392_device_fields.example_value "
            . "FROM qruqsp_43392_device_fields "
            . "WHERE qruqsp_43392_device_fields.device_id = '" . ciniki_core_dbQuote($ciniki, $args['device_id']) . "' "
            . "AND qruqsp_43392_device_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'fields', 'fname'=>'id', 
                'fields'=>array('id', 'device_id', 'fname', 'name', 'flags', 'label', 'example_value')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $device['fields'] = isset($rc['fields']) ? $rc['fields'] : array();
    }

    return array('stat'=>'ok', 'device'=>$device);
}
?>
