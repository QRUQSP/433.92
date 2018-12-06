<?php
//
// Description
// -----------
// This method will return the list of Devices for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Device for.
//
// Returns
// -------
//
function qruqsp_43392_deviceList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'active'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List Active Devices'),
        'new'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List New Devices'),
        'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', '43392', 'private', 'checkAccess');
    $rc = qruqsp_43392_checkAccess($ciniki, $args['tnid'], 'qruqsp.43392.deviceList');
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

    $rsp = array('stat'=>'ok');

    //
    // Check if new list should be cleared
    //
    if( isset($args['action']) && $args['action'] == 'clearnew' ) {
        $strsql = "SELECT qruqsp_43392_devices.id, qruqsp_43392_devices.uuid, "
            . "qruqsp_43392_device_fields.id AS field_id "
            . "qruqsp_43392_device_fields.uuid AS field_uuid "
            . "FROM qruqsp_43392_devices "
            . "LEFT JOIN qruqsp_43392_device_fields ON ("
                . "qruqsp_43392_devices.id = qruqsp_43392_device_fields.device_id "
                . "AND qruqsp_43392_device_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE qruqsp_43392_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_43392_devices.status = 10 "
            . "ORDER BY qruqsp_43392_devices.status "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'devices', 'fname'=>'id', 'fields'=>array('id', 'uuid')),
            array('container'=>'fields', 'fname'=>'field_id', 'fields'=>array('id'=>'field_id','uuid'=>'field_uuid')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $devices = isset($rc['devices']) ? $rc['devices'] : array();
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
        foreach($devices as $did => $device) {
            if( isset($device['fields']) ) {
                foreach($device['fields'] as $fid => $field) {
                    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.43392.devicefield', $field['id'], $field['uuid'], 0x07);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.24', 'msg'=>'Unable to remove device field', 'err'=>$rc['err']));
                    }
                }
            }
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.43392.device', $device['id'], $device['uuid'], 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.25', 'msg'=>'Unable to remove device', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Get the list of active devices
    //
    if( isset($args['active']) && $args['active'] == 'yes' ) {
        $strsql = "SELECT qruqsp_43392_devices.id, "
            . "qruqsp_43392_devices.model, "
            . "qruqsp_43392_devices.did, "
            . "qruqsp_43392_devices.name, "
            . "qruqsp_43392_devices.status, "
            . "qruqsp_43392_devices.status AS status_text "
            . "FROM qruqsp_43392_devices "
            . "WHERE qruqsp_43392_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND status = 30 "
            . "ORDER BY status "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'devices', 'fname'=>'id', 
                'fields'=>array('id', 'model', 'did', 'name', 'status', 'status_text'),
                'maps'=>array('status_text'=>$maps['device']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['active'] = isset($rc['devices']) ? $rc['devices'] : array();
    }

    if( isset($args['new']) && $args['new'] == 'yes' ) {
        $strsql = "SELECT qruqsp_43392_devices.id, "
            . "qruqsp_43392_devices.model, "
            . "qruqsp_43392_devices.did, "
            . "qruqsp_43392_devices.name, "
            . "qruqsp_43392_devices.status, "
            . "qruqsp_43392_devices.status AS status_text "
            . "FROM qruqsp_43392_devices "
            . "WHERE qruqsp_43392_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND status = 10 "
            . "ORDER BY status "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'devices', 'fname'=>'id', 
                'fields'=>array('id', 'model', 'did', 'name', 'status', 'status_text'),
                'maps'=>array('status_text'=>$maps['device']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['new'] = isset($rc['devices']) ? $rc['devices'] : array();
    }

    if( isset($args['all']) && $args['all'] == 'yes' ) {
        //
        // Get the list of devices
        //
        $strsql = "SELECT qruqsp_43392_devices.id, "
            . "qruqsp_43392_devices.model, "
            . "qruqsp_43392_devices.did, "
            . "qruqsp_43392_devices.name, "
            . "qruqsp_43392_devices.status, "
            . "qruqsp_43392_devices.status AS status_text "
            . "FROM qruqsp_43392_devices "
            . "WHERE qruqsp_43392_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY status "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'devices', 'fname'=>'id', 
                'fields'=>array('id', 'model', 'did', 'name', 'status', 'status_text'),
                'maps'=>array('status_text'=>$maps['device']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['devices']) ) {
            $rsp['devices'] = $rc['devices'];
            $rsp['device_ids'] = array();
            foreach($rsp['devices'] as $iid => $device) {
                $rsp['device_ids'][] = $device['id'];
            }
        } else {
            $rsp['devices'] = array();
            $rsp['device_ids'] = array();
        }
    }

    return $rsp;
}
?>
