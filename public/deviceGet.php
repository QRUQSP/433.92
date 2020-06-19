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
        'replacements'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Replacements'),
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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', '43392', 'private', 'maps');
    $rc = qruqsp_43392_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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
            'battery'=>'',
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
            . "qruqsp_43392_devices.status, "
            . "qruqsp_43392_devices.flags, "
            . "IF((qruqsp_43392_devices.flags&0x01)=0x01,'Low', 'Normal') AS battery "
            . "FROM qruqsp_43392_devices "
            . "WHERE qruqsp_43392_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_43392_devices.id = '" . ciniki_core_dbQuote($ciniki, $args['device_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'devices', 'fname'=>'id', 
                'fields'=>array('model', 'did', 'name', 'status', 'flags', 'battery'),
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
        $strsql = "SELECT f.id, "
            . "f.device_id, "
            . "f.fname, "
//            . "f.name, "
//            . "f.flags, "
//            . "IF( (flags&0x01) = 0x01, 'Yes', 'No') AS store, "
//            . "IF( (flags&0x02) = 0x02, 'Yes', 'No') AS publish, "
            . "f.ftype, "
            . "f.ftype AS ftype_text "
            . "FROM qruqsp_43392_device_fields AS f "
            . "WHERE f.device_id = '" . ciniki_core_dbQuote($ciniki, $args['device_id']) . "' "
            . "AND f.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'fields', 'fname'=>'id', 
                'fields'=>array('id', 'device_id', 'fname', 'ftype', 'ftype_text'),
//                'fields'=>array('id', 'device_id', 'fname', 'name', 'flags', 'store', 'publish', 'ftype', 'sample_date', 'fvalue'),
                'maps'=>array('ftype_text'=>$maps['devicefield']['ftype']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $device['fields'] = isset($rc['fields']) ? $rc['fields'] : array();
    }

    $rsp = array('stat'=>'ok', 'device'=>$device);
    //
    // Check if replacement list should be returned
    //
    if( isset($args['replacements']) && $args['replacements'] == 'yes' ) {
        $strsql = "SELECT qruqsp_43392_devices.id, "
            . "qruqsp_43392_devices.name "
            . "FROM qruqsp_43392_devices "
            . "WHERE qruqsp_43392_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND status = 30 "
            . "ORDER BY status "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'devices', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['active'] = isset($rc['devices']) ? $rc['devices'] : array();
    }

    return $rsp;
}
?>
