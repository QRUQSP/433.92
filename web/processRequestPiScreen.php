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
function qruqsp_43392_web_processRequestPiScreen(&$ciniki, $settings, $tnid, $args) {

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

    $page['response']['web-app'] = 'yes';
    $page['fullscreen-content'] = 'yes';

    //
    // Get the list of devices that are active, and their fields and latest data
    //
    $dt = new DateTime('now', new DateTimezone('UTC'));
    $dt->sub(new DateInterval('PT10M'));
    $strsql = "SELECT devices.id, "
        . "devices.name, "
        . "IFNULL(fields.id, 0) AS field_id, "
        . "IFNULL(fields.name, '') AS field_name, "
        . "IFNULL(fields.ftype, '') AS field_type, "
        . "IFNULL(fields.last_value, '') AS fvalue, "
        . "IFNULL(fields.last_date, '') AS sample_date, "
        . "TIMESTAMPDIFF(MINUTE, IFNULL(fields.last_date, UTC_TIMESTAMP()), UTC_TIMESTAMP()) AS sample_age "
//        . "IFNULL(data.sample_date, '') AS sample_date, "
//        . "IFNULL(data.fvalue, '??') AS fvalue "
        . "FROM qruqsp_43392_devices AS devices "
        . "INNER JOIN qruqsp_43392_device_fields AS fields ON ("
            . "devices.id = fields.device_id "
            . "AND (fields.flags&0x03) = 0x03 "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
//        . "LEFT JOIN qruqsp_43392_device_data AS data ON ("
//            . "fields.id = data.field_id "
//            . "AND data.sample_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d H:i:s')) . "' "
//            . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//            . ") "
        . "WHERE devices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND devices.status = 30 "
        . "ORDER BY devices.name, fields.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.43392', array(
        array('container'=>'devices', 'fname'=>'id', 'fields'=>array('id', 'name')),
        array('container'=>'fields', 'fname'=>'field_id', 'fields'=>array('id'=>'field_id', 'ftype'=>'field_type', 'name'=>'field_name', 'sample_date', 'fvalue', 'sample_age')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.43392.22', 'msg'=>'Unable to load devices', 'err'=>$rc['err']));
    }
    $devices = isset($rc['devices']) ? $rc['devices'] : array();

    $content = '<table cellpadding="0" cellspacing="0">';
    $content .= "<tr><td><b>Sensor</b></td><td><b>Temp</b></td><td><b>Humidity</b></td></tr>";

    foreach($devices as $device) {  
        $temperature = '';
        $humidity = '';
        foreach($device['fields'] as $field) {
            error_log($field['sample_age']);
            if( $field['ftype'] == 10 ) {
                $temperature = $field['fvalue'];
            } elseif( $field['ftype'] == 11 ) {
                $temperature = sprintf("%.1f", ($field['fvalue'] - 32)/1.8);
            } elseif( $field['ftype'] == 20 ) {
                $humidity = $field['fvalue'];
            }
        }
        if( $temperature != '' && $temperature != '??' ) {
            $temperature .= '&deg;C';
        }
        if( $humidity != '' && $humidity != '??' ) {
            $humidity .= '%';
        }
        $content .= "<tr><td>{$device['name']}</td><td>$temperature</td><td>$humidity</td></tr>";
    }
    $content .= '</table>';

    //
    // Setup CSS for small screen
    //
    if( !isset($ciniki['response']['blocks-css']) ) {
        $ciniki['response']['blocks-css'] = '';
    }
    $ciniki['response']['blocks-css'] .= "body {overflow: hidden; scroll: no;}"
        . "table {border-collapse: collapse; width: 100%; width: 100vw; height: 100vh;}"
        . "table tr:nth-child(odd) { background: #ddd; }"
        . "table td { text-align: center; vertical-align: middle; padding-left: 0.5em; padding-right: 0.5em; color: #333;}"
        . "table td:first-child { text-align: right; }"
        . "";

    //
    // Setup JS to refresh the page every 5 minutes
    //
    if( !isset($ciniki['response']['blocks-js']) ) {
        $ciniki['response']['blocks-js'] = '';
    }
    $ciniki['response']['blocks-js'] .= ""
        . "function refresh() {"
            . "window.location.reload(false);"
        . "}"
        . "window.onload=function(){setInterval(refresh,60000);}"
        . "";

    $page['blocks'][] = array('type'=>'content', 'html'=>$content);

    return array('stat'=>'ok', 'page'=>$page);
}
?>
