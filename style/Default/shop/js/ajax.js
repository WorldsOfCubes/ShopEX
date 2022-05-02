function UpdateCat(id, admTrg) {

    toggleButton('cat-' + id + '-button')

    var event = function (response) {

//        GetById('cat-' + id + '-update').reset()

        GetById('main-error-text-' + id).className = 'alert alert-danger'

        if (response != null) {

            if (response['code'] == 0) GetById('main-error-text-' + id).className = 'alert alert-success'
            if (response['code'] == 100) {
                toggleButton('profile-button')
                BlockVisible('main-error',false)
                return
            }

            GetById('main-error-text-' + id).innerHTML = nl2br(response['message'])
            BlockVisible('main-error-' + id,true)

        } else {

            GetById('main-error-text').innerHTML = 'Не удалось связаться с сервером'
            BlockVisible('main-error-' + id,true)

        }

        var name = GetById('profile-mini-name')
        if (name && !admTrg) name.innerHTML = response['name']

        var group = GetById('profile-group')
        if (group && !admTrg) group.innerHTML = response['group']

        var Ava = new Image()

        toggleButton('cat-' + id + '-button')
    }

    sendFormByIFrame('cat-' + id + '-update', event)
    return false
}
function UpdateKey(id, admTrg) {

    toggleButton('key-' + id + '-button')

    var event = function (response) {

//        GetById('key-' + id + '-update').reset()

        GetById('main-error-text-' + id).className = 'alert alert-danger'

        if (response != null) {

            if (response['code'] == 0) GetById('main-error-text-' + id).className = 'alert alert-success'
            if (response['code'] == 100) {
                toggleButton('profile-button')
                BlockVisible('main-error-' + id,false)
                return
            }

            GetById('main-error-text-' + id).innerHTML = nl2br(response['message'])
            BlockVisible('main-error-' + id,true)

        } else {

            GetById('main-error-text').innerHTML = 'Не удалось связаться с сервером'
            BlockVisible('main-error-' + id,true)

        }

        toggleButton('key-' + id + '-button')
    }

    sendFormByIFrame('key-' + id + '-update', event)
    return false
}
function RandString(length) {
    var liters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZ'.split('');

    if (! length) {
        length = 5;
    }

    var str = '';
    for (var i = 0; i < length; i++) {
        str += liters[Math.floor(Math.random() * liters.length)];
    }
    return str;
}
function GenerateKey(id) {
    var key = RandString() + '-' + RandString() + '-' + RandString() + '-' + RandString();
    KeyFiled = GetById('kf-' + id);
    KeyFiled.value = key;
}

function RemoveKey(key) {

    var event = function(response) {

        if ( response['code'] != 0 ) return false

        $('#key-' + key).fadeOut(200, function (){

            $(this).hide()
            var keyBin = GetById('key-' + key)

            if ( keyBin == null ) document.location.reload(true)
            else keyBin.parentNode.removeChild(keyBin)
        });
    }

    SendByXmlHttp('instruments/shop_mod/shop_action.php', 'method=key_delete&key=' + encodeURIComponent(key), event)
    return false
}
function RemoveCat(cat) {

    var event = function(response) {

        if ( response['code'] != 0 ) return false

        $('#cat-' + cat).fadeOut(200, function (){

            $(this).hide()
            var catBin = GetById('cat-' + cat)

            if ( catBin == null ) document.location.reload(true)
            else catBin.parentNode.removeChild(catBin)
        });
    }

    SendByXmlHttp('instruments/shop_mod/shop_action.php', 'method=cat_delete&id=' + encodeURIComponent(cat), event)
    return false
}