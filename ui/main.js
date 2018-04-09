//
// This is the main app for the 43392 module
//
function qruqsp_43392_main() {
    //
    // The panel to list the device
    //
    this.menu = new M.panel('433.92 Mhz Devices', 'qruqsp_43392_main', 'menu', 'mc', 'large narrowaside', 'sectioned', 'qruqsp.43392.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
    }
    this.menu.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.label;
            case 1: return d.value;
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('qruqsp.43392.devices', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_43392_main.menu;
            p.data = rsp;
            p.sections = {};
            for(var i in rsp.devices) {
                p.sections[i + '_data'] = {'label':rsp.devices[i].name, 'type':'simplegrid', 'num_cols':2,
                    'aside':'yes',
                    'cellClasses':['label', ''],
                    };
                p.data[i + '_data'] = {};
                p.data[i + '_graph'] = [];
                var legend = [];
                for(var j in rsp.devices[i].fields) {
                    p.data[i + '_data'][j] = {'label':rsp.devices[i].fields[j].label, 'value':rsp.devices[i].fields[j].current_value};
                    legend[j] = rsp.devices[i].fields[j].label;
                    p.data[i + '_graph'][j] = rsp.devices[i].fields[j].data;
                }
                p.sections[i + '_graph'] = {'label':'&nbsp;', 'type':'metricsgraphics',
                    'graphtype':'multiline',
                    'missing_is_hidden': true,
                    'legend':legend,
                    };
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('settings', 'Settings', 'M.startApp("qruqsp.43392.settings",null,"M.qruqsp_43392_main.menu.open();");');
    this.menu.addButton('refresh', 'Refresh', 'M.qruqsp_43392_main.menu.open();');
    this.menu.addClose('Back');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
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
        var ac = M.createContainer(ap, 'qruqsp_43392_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
