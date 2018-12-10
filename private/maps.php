<?php
//
// Description
// -----------
// This function returns the int to text mappings for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_43392_maps(&$ciniki) {
    //
    // Build the maps object
    //
    $maps = array();
    $maps['device'] = array('status'=>array(
        '10'=>'New',
        '30'=>'Active',
        '60'=>'Ignore',
    ));
    $maps['devicefield'] = array(
        'ftype'=>array(
            '0' => 'Unknown',
            '1' => 'Ignored',
            '10' => 'Temperature (C)',
            '11' => 'Temperature (F)',
            '20' => 'Humidity (%)',
            '30' => 'Wind Direction (Deg)',
            '31' => 'Wind Direction (Heading)',
            '40' => 'Wind Speed (kph)',
            '45' => 'Wind Speed (mph)',
            '50' => 'Rain Fall (1/100th inch)',
        ),
    );

    //
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
