<?php
//
// Description
// -----------
// This function will process and inject the rtl_433.
//
// The lookup_counter creates a simple cache for device data and will reload 
// every 50 messages received from the device.
//
// Arguments
// ---------
// ciniki:
// tnid:                The ID of the tenant to check the session user against.
// line:                The line received from rtl_433.
//
function qruqsp_43392_rtl433ProcessLine(&$ciniki, $tnid, $line, &$devices = array()) {
  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');

    //
    // The following fields will be ignored when setting up the fields for a 
    // device in qruqsp_43392_device_fields. These fields are already stored as part of the device.
    //
    $skip_fields = array('time', 'model', 'id', 'sensor_id');

    //
    // Setup the sample
    //
    $elements = json_decode($line, true);

    if( isset($elements['sensor_id']) && !isset($elements['id']) ) {    
        $elements['id'] = $elements['sensor_id'];
    }

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
    // Check for sequence number and same line
    //
    if( isset($elements['sequence_num']) 
        && isset($ciniki['43392']['last_model']) && $ciniki['43392']['last_model'] == $elements['model']
        && isset($ciniki['43392']['last_id']) && $ciniki['43392']['last_id'] == $elements['id']
        && isset($ciniki['43392']['last_time']) && $ciniki['43392']['last_time'] == $elements['time']
        ) {
        return array('stat'=>'ok');
    }
    if( !isset($ciniki['43392']) ) {
        $ciniki['43392'] = array();
    }
    $ciniki['43392']['last_model'] = $elements['model'];
    $ciniki['43392']['last_id'] = $elements['id'];
    $ciniki['43392']['last_time'] = $elements['time'];

/*    //
    // Parse the time in UTC and normalize to current minute.
    //
    $dt = new DateTime($elements['time'], new DateTimezone('UTC'));
    $dt->setTime($dt->format('H'), $dt->format('i'), 0);
 */   
    // Setup UTC date
    $utc = new DateTime('now', new DateTimezone('UTC'));

    //
    // Check the current device list
    //
    $model_id = $elements['model'] . '-' . $elements['id'];
    if( !isset($devices[$model_id]['lookup_counter']) || $devices[$model_id]['lookup_counter'] > 50 ) {
        //
        // Check the database
        //
        $strsql = "SELECT d.id, d.model, d.did, d.name, d.status, "
            . "f.id AS field_id, f.fname, f.ftype, f.flags "
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
            array('container'=>'fields', 'fname'=>'fname', 'fields'=>array('id'=>'field_id', 'ftype', 'fname', 'flags')),
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
    // Setup data 
    //
    $data = array(
        'sample_date' => $elements['time'],
        'object' => 'qruqsp.43392.device',
        'object_id' => $device['id'],
        'sensor' => $device['name'],
        'station' => $ciniki['config']['ciniki.core']['sync.name'],
        );
    //
    // Add the current GPS coordinates to the response
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'tenantGPSCoords');
    $rc = ciniki_tenants_hooks_tenantGPSCoords($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.19', 'msg'=>'Unable to get GPS Coordinates', 'err'=>$rc['err']));
    }
    $data['latitude'] = $rc['latitude'];
    $data['longitude'] = $rc['longitude'];
    $data['altitude'] = $rc['altitude'];

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
        //
        // Add the field when it doesn't exist in db
        //
        if( !isset($devices[$model_id]['fields'][$k]) ) {
            $devices[$model_id]['fields'][$k] = array(
                'device_id' => $device['id'],
                'fname' => $k,
                'name' => $k,
                'flags' => 0,
                'last_value' => $v,
                'last_date' => $utc->format('Y-m-d H:i:s'),
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
        //
        // Check if ftype is recognized
        //
        elseif( isset($devices[$model_id]['fields'][$k]['ftype']) && $devices[$model_id]['fields'][$k]['ftype'] > 0 ) {
            // Temp (C)
            if( $devices[$model_id]['fields'][$k]['ftype'] == 10 ) {
                $data['43392-data-type'] = 'weather';
                $data['celsius'] = $v;
            }
            // Temp (F)
            elseif( $devices[$model_id]['fields'][$k]['ftype'] == 11 ) {
                $data['43392-data-type'] = 'weather';
                $data['celsius'] = ($v-32)*(5/9);
            }
            // Humidity (%)
            elseif( $devices[$model_id]['fields'][$k]['ftype'] == 20 ) {
                $data['43392-data-type'] = 'weather';
                $data['humidity'] = $v;
            }
            // Wind Direction (degrees)
            elseif( $devices[$model_id]['fields'][$k]['ftype'] == 30 ) {
                $data['43392-data-type'] = 'weather';
                $data['wind_deg'] = $v;
            }
            // Wind Speed (kph)
            elseif( $devices[$model_id]['fields'][$k]['ftype'] == 40 ) {
                $data['43392-data-type'] = 'weather';
                $data['wind_kph'] = $v;
            }
            // Wind Speed (mph)
            elseif( $devices[$model_id]['fields'][$k]['ftype'] == 45 ) {
                $data['43392-data-type'] = 'weather';
                $data['wind_kph'] = ($v * 1.609344);
            }
            // Rainfall accumulated as 1/100th of an inch
            // Same as raw counter on acurite
            elseif( $devices[$model_id]['fields'][$k]['ftype'] == 50 ) {
                $data['43392-data-type'] = 'weather';
                $data['rain_mm'] = ($v * 0.254);
            }
        }
    }

    //
    // Check if any other modules want the data received
    //
    if( $devices[$model_id]['status'] == 30 && isset($data['43392-data-type']) && $data['43392-data-type'] != '' ) {
        //
        // If there was data returned, check to see if any modules want it
        //
        foreach($ciniki['tenant']['modules'] as $module => $m) {
            list($pkg, $mod) = explode('.', $module);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', $data['43392-data-type'] . 'DataReceived');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $tnid, $data);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }


    //
    // Add the data points      **** No longer store data in this module ****
    //
/*    if( isset($device['fields']) ) {
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
    } */

    return array('stat'=>'ok');
}
?>
