<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_43392_deviceReplace(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'old_device_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Old Device'),
        'new_device_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'New Device'),
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
    $rc = qruqsp_43392_checkAccess($ciniki, $args['tnid'], 'qruqsp.43392.deviceReplace');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the old device
    //
    $strsql = "SELECT devices.id, "
        . "devices.uuid, "
        . "devices.model, "
        . "devices.did, "
        . "devices.name, "
        . "devices.status, "
        . "fields.id AS field_id, "
        . "fields.uuid AS field_uuid, "
        . "fields.fname, "
        . "fields.ftype "
        . "FROM qruqsp_43392_devices AS devices "
        . "LEFT JOIN qruqsp_43392_device_fields AS fields ON ("
            . "devices.id = fields.device_id "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (devices.id = '" . ciniki_core_dbQuote($ciniki, $args['old_device_id']) . "' "
            . "OR devices.id = '" . ciniki_core_dbQuote($ciniki, $args['new_device_id']) . "' "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'qruqsp.43392', array(
        array('container'=>'devices', 'fname'=>'id', 'fields'=>array('id', 'uuid', 'model', 'did', 'name', 'status')),
        array('container'=>'fields', 'fname'=>'field_id', 'fields'=>array('id'=>'field_id', 'uuid'=>'field_uuid', 'fname', 'ftype')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.15', 'msg'=>'Device not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['devices'][$args['old_device_id']]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.16', 'msg'=>'Unable to find old device'));
    }
    if( !isset($rc['devices'][$args['new_device_id']]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.17', 'msg'=>'Unable to find new device'));
    }
    $old_device = $rc['devices'][$args['old_device_id']];
    $new_device = $rc['devices'][$args['new_device_id']];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'qruqsp', '43392', 'private', 'rtl433Restart');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.43392');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the fields in the new device to the settings from the old, if not already setup
    //
    foreach($new_device['fields'] as $fid => $field) {
        //
        // Check if old sensor has a field type for the fname
        //
        if( $field['ftype'] == 0 ) {
            foreach($old_device['fields'] as $old_fid => $old_field) {
                if( $old_field['fname'] == $field['fname'] && $old_field['ftype'] > 0 ) {
                    //
                    // Update the field with the type
                    //
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.43392.devicefield', $field['id'], array(
                        'ftype' => $old_field['ftype'],
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.11', 'msg'=>'Unable to update the device field'));
                    }
                }
            }
        }
    }

    //
    // Update the Device in the database
    //
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.43392.device', $new_device['id'], array(
        'name' => $old_device['name'],
        'status' => $old_device['status'],
        ), 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.43392');
        return $rc;
    }

    //
    // Update other modules via hooks
    //
    foreach($ciniki['tenant']['modules'] as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'updateObjectID');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array(
                'object' => 'qruqsp.43392.device',
                'old_object_id' => $args['old_device_id'],
                'new_object_id' => $args['new_device_id'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Remove the old fields
    //
    foreach($old_device['fields'] as $fid => $field) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.43392.devicefield', $field['id'], $field['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.43392');
            return $rc;
        }
    }

    //
    // Remove the old device
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.43392.device', $old_device['id'], $old_device['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.43392');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'qruqsp.43392');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Restart rtl_433
    //
    $rc = qruqsp_43392_rtl433Restart($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.28', 'msg'=>'', 'err'=>$rc['err']));
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'qruqsp', '43392');

    return array('stat'=>'ok');
}
?>
