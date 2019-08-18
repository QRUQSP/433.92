<?php
//
// Description
// -----------
// This method will return the list of active devices, their current values for tracked fields, and graph data.
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
function qruqsp_43392_devices($ciniki) {
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
    $rc = qruqsp_43392_checkAccess($ciniki, $args['tnid'], 'qruqsp.43392.devices');
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

    $dt = new DateTime('now', new DateTimezone('UTC'));
    $dt->sub(new DateInterval('P1D'));

    //
    // Get the list of active devices
    //
    $strsql = "SELECT devices.id, "
        . "devices.model, "
        . "devices.did, "
        . "devices.name, "
        . "devices.status, "
        . "devices.status AS status_text, "
        . "fields.id AS field_id, "
        . "fields.ftype, "
        . "fields.name AS field_name, "
        . "IFNULL(data.sample_date, '') AS sample_date, "
        . "IFNULL(data.fvalue, '') AS fvalue "
        . "FROM qruqsp_43392_devices AS devices "
        . "INNER JOIN qruqsp_43392_device_fields AS fields ON ("
            . "devices.id = fields.device_id "
            . "AND (fields.flags&0x01) = 0x01 "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND devices.status = 30 "
        . "ORDER BY devices.name, fields.name, data.sample_date ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
        array('container'=>'devices', 'fname'=>'id', 
            'fields'=>array('id', 'model', 'did', 'name', 'status', 'status_text'),
            'maps'=>array('status_text'=>$maps['device']['status']),
            ),
        array('container'=>'fields', 'fname'=>'field_id', 'fields'=>array('id'=>'field_id', 'ftype', 'label'=>'field_name')),
        array('container'=>'data', 'fname'=>'sample_date', 'fields'=>array('date'=>'sample_date', 'value'=>'fvalue')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['devices']) ) {
        $rsp['devices'] = $rc['devices'];
        foreach($rsp['devices'] as $did => $device) {
            foreach($device['fields'] as $fid => $field) {
                foreach($field['data'] as $data_id => $data) {
                    if( $field['ftype'] == 11 ) { 
                        $rsp['devices'][$did]['fields'][$fid]['data'][$data_id]['value'] = ((float)$data['value'] - 32)/1.8;
                    } elseif( $field['ftype'] == 45 ) { 
                        $rsp['devices'][$did]['fields'][$fid]['data'][$data_id]['value'] = (float)$data['value'] * 1.609344;
                    } else {
                        $rsp['devices'][$did]['fields'][$fid]['data'][$data_id]['value'] = (float)$data['value'];
                    }
                }
                $rsp['devices'][$did]['fields'][$fid]['current_value'] = '';
                if( isset($field['data']) ) {
                    $cvalue = end($field['data']);
                    $rsp['devices'][$did]['fields'][$fid]['current_value'] = $cvalue['value'];
                }
            }
        }
    } else {
        $rsp['devices'] = array();
    }

    return $rsp;
}
?>
