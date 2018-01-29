<?php
//
// Description
// -----------
// This function will process a web request.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:            The ID of the tenant to get page details for.
//
// args:            The possible arguments for the page
//
//
// Returns
// -------
//
function qruqsp_43392_web_processRequest(&$ciniki, $settings, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['qruqsp.43392']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'qruqsp.43392.5', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        );

    
    //
    // Get the list of devices that are active, and their fields and latest data
    //
    $dt = new DateTime('now', new DateTimezone('UTC'));
    $dt->sub(new DateInterval('P5M'));
    $strsql = "SELECT devices.id, "
        . "devices.name, "
        . "IFNULL(fields.id, 0) AS field_id, "
        . "IFNULL(fields.name, '') AS field_name, "
        . "IFNULL(data.sample_date, '') AS sample_date, "
        . "IFNULL(data.fvalue, '') AS fvalue "
        . "FROM qruqsp_43392_devices AS devices "
        . "LEFT JOIN qruqsp_43392_device_fields AS fields ON ("
            . "devices.id = fields.device_id "
            . "AND (fields.flags&0x03) = 0x03 "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN qruqsp_43392_device_data AS data ON ("
            . "fields.id = data.field_id "
            . "AND data.sample_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d H:i:s')) . "' "
            . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE devices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND devices.status = 30 "
        . "ORDER BY devices.name, fields.name, data.sample_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
        array('container'=>'devices', 'fname'=>'id', 'fields'=>array('id', 'name')),
        array('container'=>'fields', 'fname'=>'field_id', 'fields'=>array('id'=>'field_id', 'name'=>'field_name', 'sample_date', 'fvalue')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.22', 'msg'=>'Unable to load devices', 'err'=>$rc['err']));
    }
    $devices = isset($rc['devices']) ? $rc['devices'] : array();

    foreach($devices as $device) {
        $info = '';
        foreach($device['fields'] as $field) {
            $info .= "<b>{$field['name']}</b>: {$field['fvalue']}<br/>";
        }
        $page['blocks'][] = array('type'=>'content', 'title'=>$device['name'], 'content'=>$info);
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
