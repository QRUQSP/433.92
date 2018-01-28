<?php
//
// Description
// -----------
// This function will process and inject the rtl_433.
//
// Arguments
// ---------
// ciniki:
// tnid:                The ID of the tenant to check the session user against.
// line:                The line received from rtl_433.
//
function qruqsp_43392_rtl433ProcessLine(&$ciniki, $tnid, $line, &$devices = array()) {
  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

    //
    // The following fields will be ignored when setting up the fields for a 
    // device in qruqsp_43392_device_fields. These fields are already stored as part of the device.
    //
    $skip_fields = array('time', 'model', 'id');

    //
    // Setup the sample
    //
    $elements = json_decode($line, true);

    //
    // Check for a model and id
    //
    if( !isset($elements['model']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.5', 'msg'=>'No model specified'));
    }
    if( !isset($elements['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.6', 'msg'=>'No id specified'));
    }
    if( !isset($elements['time']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.10', 'msg'=>'No time specified'));
    }

    //
    // Parse the time in UTC and normalize to current minute.
    //
    $dt = new DateTime($elements['time'], new DateTimezone('UTC'));
    $dt->setTime($dt->format('H'), $dt->format('i'), 0);

    //
    // Check the current device list
    //
    $model_id = $elements['model'] . '-' . $elements['id'];
    if( !isset($devices[$model_id]['lookup_counter']) || $devices[$model_id]['lookup_counter'] > 50 ) {
        //
        // Check the database
        //
        $strsql = "SELECT d.id, d.model, d.did, d.name, d.status, "
            . "f.id AS field_id, f.fname, f.flags "
            . "FROM qruqsp_43392_devices AS d "
            . "LEFT JOIN qruqsp_43392_device_fields AS f ON ("
                . "d.id = f.device_id "
                . "AND f.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE d.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND d.model = '" . ciniki_core_dbQuote($ciniki, $elements['model']) . "' "
            . "AND d.did = '" . ciniki_core_dbQuote($ciniki, $elements['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'qruqsp.43392', array(
            array('container'=>'devices', 'fname'=>'id', 'fields'=>array('id', 'model', 'did', 'name', 'status')),
            array('container'=>'fields', 'fname'=>'fname', 'fields'=>array('id'=>'field_id', 'fname', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.7', 'msg'=>'Unable to load device', 'err'=>$rc['err']));
        }
        if( !isset($rc['devices']) || count($rc['devices']) < 1 ) {
            $device = array(
                'model' => $elements['model'],
                'did' => $elements['id'],
                'name' => $elements['model'] . '(' . $elements['id'] . ')',
                'status' => 10,
                'lookup_counter' => 0,
                'fields' => array(),
                );
            //
            // Add the device
            //
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'qruqsp.43392.device', $device, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.8', 'msg'=>'Unable to add the device', 'err'=>$rc['err']));
            }
            $device['id'] = $rc['id']; 
        } else {
            $device = array_shift($rc['devices']);
            $device['lookup_counter'] = 0;
        }
        $devices[$model_id] = $device;
    } else {
        $device = $devices[$model_id];
        $devices[$model_id]['lookup_counter']++;
    }

    //
    // Check for any fields that are missing and add them.
    //
    foreach($elements as $k => $v) {
        //
        // Skip the following fields, they are already captured
        //
        if( in_array($k, $skip_fields) ) {
            continue;
        }
        if( !isset($devices[$model_id]['fields'][$k]) ) {
            $devices[$model_id]['fields'][$k] = array(
                'device_id' => $device['id'],
                'fname' => $k,
                'name' => $k,
                'flags' => 0,
                'example_value' => $v,
                'last_sample_date' => '',
                );
            //
            // Add the field
            //
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'qruqsp.43392.devicefield', $devices[$model_id]['fields'][$k], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.9', 'msg'=>'Unable to add the device field'));
            }
            $devices[$model_id]['fields'][$k]['id'] = $rc['id']; 
        }
    }

    //
    // Add the data points
    //
    if( isset($device['fields']) ) {
        foreach($device['fields'] as $name => $field) {
            //
            // Skip missing fields from the json line
            //
            if( !isset($elements[$name]) ) {
                continue;
            }

            //
            // Only add to database if flag is set to Store
            //
            if( ($field['flags']&0x01) == 0 ) {
                continue;
            }

            //
            // Some devices send 3 copies of the same information, so store the last date
            // so we know if this is a duplicate sample
            //
            if( isset($devices[$model_id]['fields'][$name]['last_sample_date'])
                && $devices[$model_id]['fields'][$name]['last_sample_date'] == $dt->format('Y-m-d H:i:s')
                ) {
                continue;
            }
            $devices[$model_id]['fields'][$name]['last_sample_date'] = $dt->format('Y-m-d H:i:s');

            //
            // Add the data
            //
            $strsql = "INSERT INTO qruqsp_43392_device_data (tnid, field_id, sample_date, fvalue) VALUES ("
                . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $field['id']) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d H:i:s')) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $elements[$name]) . "') ";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'qruqsp.43392');
            if( $rc['stat'] != 'ok' ) {
                if( $rc['stat'] == 'exists' ) {
                    continue;
                }
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.11', 'msg'=>'Unable to add data sample', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
