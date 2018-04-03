//
// This is the main app for the 43392 module
//
function qruqsp_43392_main() {
    //
    // The panel to list the device
    //
    this.menu = new M.panel('433.92 Mhz Devices', 'qruqsp_43392_main', 'menu', 'mc', 'medium', 'sectioned', 'qruqsp.43392.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'devices' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'devices' ) {
            return 'M.qruqsp_43392_main.device.open(\'M.qruqsp_43392_main.menu.open();\',\'' + d.id + '\',M.qruqsp_43392_main.device.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('qruqsp.43392.deviceList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_43392_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('settings', 'Settings', 'M.startApp("qruqsp.43392.settings",null,"M.qruqsp_43392_main.menu.open();");');
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
