var PPermission = {
    permissions: false,
    userPermissions: {},
    apiLang: 'en',
    apiUrl: '',

    init: function()
    {
        PPermission.permissions = (permissionsList) ? permissionsList: {};
        PPermission.userPermissions = (userPermissions) ? userPermissions: {};
        PPermission.renderPermissions();
    },

    renderPermissions: function()
    {
        var h = '';
        for (var key in PPermission.permissions){
            var permission = PPermission.permissions[key];
            h += '<div class="row">';
            h += '<div class="col-sm-2 permission-title">'+permission.title+'</div>';

            for (var type in permission.permissions){
                var typeValue = permission.permissions[type];
                h += '<div class="col-sm-2 permission-type">';
                h += '<a class="option-item option-item-hover" href="javascript: PPermission.select(\''+key+'\', \''+type+'\');">';
                h += '<div class="option-checkbox"><i class="fa '+((PPermission.userPermissions[key] && PPermission.userPermissions[key].indexOf(type) !== -1) ? 'fa-check-square fa-green': 'fa-square fa-gray"')+'"></i></div>';
                h += '<div class="option-title">' + typeValue + '</div>';
                h += '</a>';
                h += '</div>';
            }
            h += '</div>';
        }
        document.getElementById("permissions_list").innerHTML = h;
    },

    select: function(section, permission) {
        if (!PPermission.userPermissions[section])
            PPermission.userPermissions[section] = [];

        var optIndex = PPermission.userPermissions[section].indexOf(permission);
        if(optIndex == -1){
            if(permission == 'all'){
                var _sp = [];
                for (var type in PPermission.permissions[section].permissions){
                    _sp[_sp.length] = type;
                }
                //alert(JSON.stringify(_sp))
                PPermission.userPermissions[section] = _sp;
            }else{
                PPermission.userPermissions[section][PPermission.userPermissions[section].length] = permission;
            }
        }else{
            if(permission == 'all'){
                PPermission.userPermissions[section] = []
            }else{
                PPermission.userPermissions[section].splice(optIndex, 1);
                //alert(JSON.stringify(PPermission.userPermissions[section]))
                var allIndex = PPermission.userPermissions[section].indexOf('all');
                if(allIndex >= 0)
                    PPermission.userPermissions[section].splice(allIndex, 1);
            }
        }

        PPermission.renderPermissions();
        PPermission.setPermissions();
    },

    setPermissions: function(){
        var _permissions = {};
        for (var key in PPermission.userPermissions){
            var Upermissions = PPermission.userPermissions[key];
            _permissions[key]=[];
            for (var type in Upermissions){
                _permissions[key][type] = Upermissions[type];
            }
        }

        document.getElementById("hidden_permissions").value = JSON.stringify(_permissions);
    },

    checkAll: function(){
        var _sp = {};
        for (var key in PPermission.permissions){
            _sp[key] = [];
            var permission = PPermission.permissions[key];
            for (var type in permission.permissions)
                _sp[key][_sp[key].length] = type;
        }
        PPermission.userPermissions = _sp;
        PPermission.renderPermissions();
        PPermission.setPermissions();
    },

    uncheckAll: function(){
        PPermission.userPermissions = []
        PPermission.renderPermissions();
        PPermission.setPermissions();
    },
}
PPermission.init();