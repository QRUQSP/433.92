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
    $skip_fields = array('time', 'model', 'id', 'sensor_id', 'battery_ok');

    //
    // Check if json should be logged
    //
    if( isset($ciniki['config']['qruqsp.43392']['json.logging']) 
        && $ciniki['config']['qruqsp.43392']['json.logging'] == 'yes' 
        && isset($ciniki['config']['qruqsp.core']['log_dir'])
        && $ciniki['config']['qruqsp.core']['log_dir'] != '' 
        ) {
        $log_dir = $ciniki['config']['qruqsp.core']['log_dir'] . '/qruqsp.43392';
        if( !file_exists($log_dir) ) {
            mkdir($log_dir);
        }

        $dt = new DateTime('now', new DateTimezone('UTC'));
        file_put_contents($log_dir . '/json.' . $dt->format('Y-m') . '.log',  
            '[' . $dt->format('d/M/Y:H:i:s O') . '] ' . $line . "\n",
            FILE_APPEND);
    }

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
    // Check for same line
    //
    if( isset($ciniki['43392']['last_model']) && $ciniki['43392']['last_model'] == $elements['model']
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

    //
    // Acurite 5n1 sends data split into 2 messages. Cache first one until second arrives
    //
    if( $elements['model'] == 'Acurite-5n1' 
        && ($elements['message_type'] == 56 || $elements['message_type'] == 49) 
        ) {
        
        //
        // Check if message in cache
        //
        $elements['dt'] = new DateTime($elements['time']);
        $dt = clone $elements['dt'];
        $dt->sub(new DateInterval('PT2M'));
        if( isset($ciniki['43392']['last_acurite_5n1_' . $elements['id']]) 
            && $ciniki['43392']['last_acurite_5n1_' . $elements['id']]['dt'] > $dt
            && $ciniki['43392']['last_acurite_5n1_' . $elements['id']]['message_type'] != $elements['message_type'] 
            ) {
            $elements = array_merge($ciniki['43392']['last_acurite_5n1_' . $elements['id']], $elements);
            unset($ciniki['43392']['last_acurite_5n1_' . $elements['id']]);
        } else {
            $ciniki['43392']['last_acurite_5n1_' . $elements['id']] = $elements;
            return array('stat'=>'ok');
        }
    }

    //
    // Check if elements should be logged
    //
    if( isset($ciniki['config']['qruqsp.43392']['elements.logging']) 
        && $ciniki['config']['qruqsp.43392']['elements.logging'] == 'yes' 
        && isset($ciniki['config']['qruqsp.core']['log_dir'])
        && $ciniki['config']['qruqsp.core']['log_dir'] != '' 
        ) {
        $log_dir = $ciniki['config']['qruqsp.core']['log_dir'] . '/qruqsp.43392';
        if( !file_exists($log_dir) ) {
            mkdir($log_dir);
        }
        $dt = new DateTime('now', new DateTimezone('UTC'));
        file_put_contents($log_dir . '/elements.' . $dt->format('Y-m') . '.log',  
            '[' . $dt->format('d/M/Y:H:i:s O') . '] ' . json_encode($elements) . "\n",
            FILE_APPEND);
    }

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
        $strsql = "SELECT d.id, d.model, d.did, d.name, d.status, d.flags AS device_flags, "
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
            array('container'=>'devices', 'fname'=>'id', 'fields'=>array('id', 'model', 'did', 'name', 'status', 'flags'=>'device_flags')),
            array('container'=>'fields', 'fname'=>'fname', 'fields'=>array('id'=>'field_id', 'ftype', 'fname', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            if( $rc['err']['code'] == 'ciniki.core.62' ) {
                return array('stat'=>'dbgone', 'err'=>array('code'=>'qruqsp.43392.27', 'msg'=>'Database went away.', 'err'=>$rc['err']));
            }
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.7', 'msg'=>'Unable to load device', 'err'=>$rc['err']));
        }
        if( !isset($rc['devices']) || count($rc['devices']) < 1 ) {
            $device = array(
                'model' => $elements['model'],
                'did' => $elements['id'],
                'flags' => 0,
                'name' => $elements['model'] . '(' . $elements['id'] . ')',
                'status' => 10,
                'lookup_counter' => 25,  // Make the first re-lookup quicker
                'fields' => array(),
                );
            if( isset($elements['battery_ok']) ) {
                $device['flags'] = ($elements['battery_ok'] == 0 ? 0x01 : 0);
            }
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
    // Check battery status
    //
    $device_updates = array();
    if( ($device['flags']&0x01) == 0 && isset($elements['battery_ok']) && $elements['battery_ok'] == 0 ) {
        $device['flags'] |= 0x01;
        $device_updates['flags'] = $device['flags'];
    } elseif( ($device['flags']&0x01) == 1 && isset($elements['battery_ok']) && $elements['battery_ok'] == 1 ) {
        $device['flags'] &= ~0x01;
        $device_updates['flags'] = $device['flags'];
    } 

    //
    // Check if device has been updated
    //
    if( count($device_updates) > 0 ) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'qruqsp.43392.device', $device['id'], $device_updates, 0x07);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.23', 'msg'=>'Unable to update device', 'err'=>$rc['err']));
        }
        foreach($device_updates as $k => $v) {
            $devices[$model_id][$k] = $v;
        }
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
                    error_log('WARN: Error sending data to other modules: ' . print_r($rc, true));
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
