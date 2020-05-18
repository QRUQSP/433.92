//
// This is the settings app for the 43392 module
//
function qruqsp_43392_settings() {
    
    //
    // The list of availabe field types
    //
    this.fieldTypes = {
        '0':'Unknown',
        '1':'Ignored',
        '10':'Temperature (C)',
        '11':'Temperature (F)',
        '20':'Humidity (%)',
        '30':'Wind Direction (Deg)',
        '40':'Wind Speed (kph)',
        '45':'Wind Speed (mph)',
        '50':'Rainfall Accumulated (1/100")',
        };
    //
    // The panel to list the device
    //
    this.menu = new M.panel('device', 'qruqsp_43392_settings', 'menu', 'mc', 'medium', 'sectioned', 'qruqsp.43392.settings.menu');
    this.menu.data = {};
    this.menu.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search device',
            'noData':'No device found',
            },
        'active':{'label':'Devices', 'type':'simplegrid', 'num_cols':2,
            'noData':'No devices setup',
            },
        'new':{'label':'New Devices', 'type':'simplegrid', 'num_cols':2,
            'noData':'No new devices',
            'addTxt':'Clear List',
            'addFn':'M.qruqsp_43392_settings.menu.clearList();',
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('qruqsp.43392.deviceSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.qruqsp_43392_settings.menu.liveSearchShow('search',null,M.gE(M.qruqsp_43392_settings.menu.panelUID + '_' + s), rsp.devices);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.qruqsp_43392_settings.edit.open(\'M.qruqsp_43392_settings.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'active' || s == 'new' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.status_text;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'active' || s == 'new' ) {
            return 'M.qruqsp_43392_settings.edit.open(\'M.qruqsp_43392_settings.menu.open();\',\'' + d.id + '\',M.qruqsp_43392_settings.menu.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('qruqsp.43392.deviceList', {'tnid':M.curTenantID, 'active':'yes', 'new':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_43392_settings.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.clearList = function() {
        M.api.getJSONCb('qruqsp.43392.deviceList', {'tnid':M.curTenantID, 'active':'yes', 'new':'yes', 'action':'clearnew'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_43392_settings.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('refresh', 'Refresh', 'M.qruqsp_43392_settings.menu.open();');
    this.menu.addClose('Back');

    //
    // The panel to display Device
    //
    this.device = new M.panel('Device', 'qruqsp_43392_settings', 'device', 'mc', 'medium mediumaside', 'sectioned', 'qruqsp.43392.settings.device');
    this.device.data = null;
    this.device.device_id = 0;
    this.device.sections = {
    }
    this.device.open = function(cb, did, list) {
        if( did != null ) { this.device_id = did; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.43392.deviceGet', {'tnid':M.curTenantID, 'device_id':this.device_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_43392_settings.device;
            p.data = rsp.device;
            p.refresh();
            p.show(cb);
        });
    }
    this.device.addButton('edit', 'Edit', 'M.qruqsp_43392_settings.edit.open(\'M.qruqsp_43392_settings.device.open();\',M.qruqsp_43392_settings.device.device_id);');
    this.device.addClose('Back');

    //
    // The panel to edit Device
    //
    this.edit = new M.panel('Device', 'qruqsp_43392_settings', 'edit', 'mc', 'medium mediumaside', 'sectioned', 'qruqsp.43392.settings.edit');
    this.edit.data = null;
    this.edit.device_id = 0;
    this.edit.nplist = [];
    this.edit.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'model':{'label':'Model', 'editable':'no', 'type':'text'},
            'did':{'label':'id', 'editable':'no', 'type':'text'},
            'name':{'label':'Name', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'New', '30':'Active', '60':'Ignore'}},
            }},
//        'fields':{'label':'', 'fields':{
//            }},
        'fields':{'label':'Fields', 'type':'simplegrid', 'num_cols':2,
//            'headerValues':['Name', 'Store', 'Publish', 'Type', 'Last Value', 'Last Date'],
            'headerValues':['Name', 'Type'],
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_43392_settings.edit.save();'},
            'replace':{'label':'Replace Another Device', 'fn':'M.qruqsp_43392_settings.edit.save("M.qruqsp_43392_settings.replace.open(\'M.qruqsp_43392_settings.edit.open();\',M.qruqsp_43392_settings.edit.device_id);");'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_43392_settings.edit.device_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_43392_settings.edit.remove();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.43392.deviceHistory', 'args':{'tnid':M.curTenantID, 'device_id':this.device_id, 'field':i}};
    }
    this.edit.cellValue = function(s, i, j, d) {
        if( s == 'fields' ) {
            switch(j) {
                case 0: return d.fname;
                case 1: return d.ftype_text;
            }
        }
    }
    this.edit.rowFn = function(s, i, d) {
        if( s == 'fields' ) {
            return 'M.qruqsp_43392_settings.edit.save("M.qruqsp_43392_settings.devicefield.open(\'M.qruqsp_43392_settings.edit.open();\',\'' + d.id + '\',M.qruqsp_43392_settings.edit.nplist);");';
        }
    }
    this.edit.open = function(cb, did, list) {
        if( did != null ) { this.device_id = did; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.43392.deviceGet', {'tnid':M.curTenantID, 'device_id':this.device_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_43392_settings.edit;
            p.data = rsp.device;
            p.sections.fields.fields = {};
            for(var i in rsp.device.fields) {
                p.sections.fields.fields['field_' + rsp.device.fields[i].id] = {'label':rsp.device.fields[i].fname, 
                    'type':'select', 'options':M.qruqsp_43392_settings.fieldTypes};
                p.data['field_' + rsp.device.fields[i].id] = rsp.device.fields[i].ftype;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_43392_settings.edit.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.device_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.43392.deviceUpdate', {'tnid':M.curTenantID, 'device_id':this.device_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.43392.deviceAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_43392_settings.edit.device_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.edit.remove = function() {
        M.confirm('Are you sure you want to remove device?',null,function() {
            M.api.getJSONCb('qruqsp.43392.deviceDelete', {'tnid':M.curTenantID, 'device_id':M.qruqsp_43392_settings.edit.device_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_43392_settings.edit.close();
            });
        });
    }
    this.edit.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.device_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_43392_settings.edit.save(\'M.qruqsp_43392_settings.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.device_id) + 1] + ');\');';
        }
        return null;
    }
    this.edit.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.device_id) > 0 ) {
            return 'M.qruqsp_43392_settings.edit.save(\'M.qruqsp_43392_settings.device_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.device_id) - 1] + ');\');';
        }
        return null;
    }
    this.edit.addButton('save', 'Save', 'M.qruqsp_43392_settings.edit.save();');
    this.edit.addClose('Cancel');
    this.edit.addButton('next', 'Next');
    this.edit.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Device Field
    //
    this.devicefield = new M.panel('Device Field', 'qruqsp_43392_settings', 'devicefield', 'mc', 'medium', 'sectioned', 'qruqsp.43392.settings.devicefield');
    this.devicefield.data = null;
    this.devicefield.field_id = 0;
    this.devicefield.nplist = [];
    this.devicefield.sections = {
        'general':{'label':'', 'fields':{
            'device_id':{'label':'Device', 'editable':'no', 'type':'text'},
            'fname':{'label':'JSON Field Name', 'editable':'no', 'type':'text'},
//            'name':{'label':'Name', 'type':'text'},
//            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Store'}, '2':{'name':'Visible'},}},
            'ftype':{'label':'Data', 'type':'select', 'options':this.fieldTypes},
            'last_value':{'label':'Example', 'editable':'no', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_43392_settings.devicefield.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_43392_settings.devicefield.field_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_43392_settings.devicefield.remove();'},
            }},
        };
    this.devicefield.fieldValue = function(s, i, d) { return this.data[i]; }
    this.devicefield.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.43392.deviceFieldHistory', 'args':{'tnid':M.curTenantID, 'field_id':this.field_id, 'field':i}};
    }
    this.devicefield.open = function(cb, fid, list) {
        if( fid != null ) { this.field_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.43392.deviceFieldGet', {'tnid':M.curTenantID, 'field_id':this.field_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_43392_settings.devicefield;
            p.data = rsp.field;
            p.refresh();
            p.show(cb);
        });
    }
    this.devicefield.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_43392_settings.devicefield.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.field_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.43392.deviceFieldUpdate', {'tnid':M.curTenantID, 'field_id':this.field_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.43392.deviceFieldAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_43392_settings.devicefield.field_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.devicefield.remove = function() {
        M.confirm('Are you sure you want to remove field?',null,function() {
            M.api.getJSONCb('qruqsp.43392.deviceFieldDelete', {'tnid':M.curTenantID, 'field_id':M.qruqsp_43392_settings.devicefield.field_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_43392_settings.devicefield.close();
            });
        });
    }
    this.devicefield.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.field_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_43392_settings.devicefield.save(\'M.qruqsp_43392_settings.devicefield.open(null,' + this.nplist[this.nplist.indexOf('' + this.field_id) + 1] + ');\');';
        }
        return null;
    }
    this.devicefield.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.field_id) > 0 ) {
            return 'M.qruqsp_43392_settings.devicefield.save(\'M.qruqsp_43392_settings.field_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.field_id) - 1] + ');\');';
        }
        return null;
    }
    this.devicefield.addButton('save', 'Save', 'M.qruqsp_43392_settings.devicefield.save();');
    this.devicefield.addClose('Cancel');
    this.devicefield.addButton('next', 'Next');
    this.devicefield.addLeftButton('prev', 'Prev');

    //
    // The panel to replace an active device with a new device. This is used
    // when the ID on a sensor changes after battery replacement.
    //
    this.replace = new M.panel('Replace Device', 'qruqsp_43392_settings', 'edit', 'mc', 'medium', 'sectioned', 'qruqsp.43392.settings.replace');
    this.replace.data = null;
    this.replace.device_id = 0;
    this.replace.nplist = [];
    this.replace.sections = {
        'general':{'label':'', 'fields':{
            'model':{'label':'Model', 'editable':'no', 'type':'text'},
            'did':{'label':'id', 'editable':'no', 'type':'text'},
            }},
        '_sensors':{'label':'Replace Active Sensor', 'fields':{
            'old_device_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':{}, 'complex_options':{'value':'id', 'name':'name'}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'replace':{'label':'Replace Sensor', 'fn':'M.qruqsp_43392_settings.replace.save();'},
            }},
        };
    this.replace.fieldValue = function(s, i, d) { return this.data[i]; }
    this.replace.open = function(cb, did, list) {
        if( did != null ) { this.device_id = did; }
        M.api.getJSONCb('qruqsp.43392.deviceGet', {'tnid':M.curTenantID, 'device_id':this.device_id, 'replacements':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_43392_settings.replace;
            p.data = rsp.device;
            p.sections._sensors.fields.old_device_id.options = rsp.active;
            p.refresh();
            p.show(cb);
        });
    }
    this.replace.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_43392_settings.replace.close();'; }
        var old_device_id = this.formValue('old_device_id');
        M.api.getJSONCb('qruqsp.43392.deviceReplace', {'tnid':M.curTenantID, 'old_device_id':old_device_id, 'new_device_id':this.device_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.qruqsp_43392_settings.menu.open();
        });
    }
    this.replace.addClose('Cancel');

    //
    // Start the app
    // cb - The callback to run when the user leaves the settings panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'qruqsp_43392_settings', 'yes');
        if( ac == null ) {
            M.alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
