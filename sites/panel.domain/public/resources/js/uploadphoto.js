/**
 * Created by kashmar on 8/15/14.
 */

var aids = new Array();
var pids = new Array();
var http_url = '';

$('#addphoto').change(function(event) {
    files = event.target.files;

    event.stopPropagation(); // Stop stuff happening
    event.preventDefault(); // Totally stop stuff happening

    $.each(files, function(key, value)
    {
        var newbox = '<div class="newphotobox" id="newphotobox">';
        newbox += '<img width="120" height="120" src="/resources/images/photo_loading.gif"/>';
        newbox += '</div>';
        $("#"+PHOTOTYPE).html(newbox);

        var data = new FormData();
        var AA_ID = Math.floor(Math.random() * 1000000);
        data.append('photo', value);
        data.append('sid', U_ID);
        data.append('id', USER_ID);
        data.append('type', PHOTOTYPE);
        data.append('car_id', CAR_ID);

        $('#'+PHOTOTYPE).on('click', function(event) {});
        $.ajax({
            url: http_url+'/profile/uploadphoto',
            type: 'POST',
            data: data,
            cache: false,
            //dataType: 'json',
            //async: false;
            processData: false, // Don't process the files
            contentType: false, // Set content type to false as jQuery will tell the server its a query string request
            success: function(data)
            {
                var data = $.parseJSON(data);

                if(data.status == 'error'){
                    $("#newphotobox"+AA_ID).remove();
                    alert(data.description);
                }else{
                    var newbox = '<div class="newphotobox" id="newphotobox">';
                    newbox += '<img width="120" height="120" src="'+data.avatar.small+'"/>';
                    newbox += '</div>';
                    $("#"+PHOTOTYPE).html(newbox);
                }

            },
            error: function(jqXHR, textStatus, errorThrown)
            {
                // Handle errors here
                console.log('ERRORS: ' + textStatus);
                $("#newphotobox" + AA_ID).css('display', 'none');
                // STOP LOADING SPINNER
            }
        });
    });


});
$('#addbutton').on('click', function(event) {
    $("#create-form").submit();
    var errr = false;
});

function stopExec(event){
    event.stopPropagation(); // Stop stuff happening
    event.preventDefault(); // Totally stop stuff happening
}

function createPhotoBox(aid){
    var newbox = '<div class="newphotobox" id="newphotobox' + aid + '">';
    newbox += '<img width="120" height="120" src="/resources/images/photo_loading.gif"/>';
    newbox += '</div>';
    $("#rowphotobox").html($("#rowphotobox").html() + newbox);
}

function generateBox(pid, url, AA_ID, ismain){
    var newhtml = '';
    newhtml += '<div style="width:100%;height:120px;overflow: hidden;">';
    newhtml += '<img id="uploadimg' + AA_ID + '" width="120" src="' + url + '?' + Math.random() + '"/>';
    newhtml += '</div>';
    newhtml += '<div style="width:100%" class="photohandleline">';
    newhtml += '<a class="photohandlea" href="javascript:void();"><img id="addphoto' + AA_ID + '" onclick="mainPhoto(\'' + pid + '\', \'' + U_ID + '\', \'' + AA_ID + '\')" main="'+(ismain == 1 ? 1: 0)+'" class="addunselected" src="/resources/images/'+(ismain == 1 ? 'esas_shekil': 'esas_shekil_ol')+'.png"/></a>';
    newhtml += '<a class="photohandlea" href="javascript:rotatePhoto(\'' + pid + '\', 0, \'' + AA_ID + '\')"><img src="/resources/images/rotate_left.gif"/></a>';
    newhtml += '<a class="photohandlea" href="javascript:rotatePhoto(\'' + pid + '\', 1, \'' + AA_ID + '\')"><img src="/resources/images/rotate_right.gif"/></a>';
    newhtml += '<a class="photohandlea" href="javascript:deletePhoto(\'' + pid + '\', \'' + AA_ID + '\')"><img src="/resources/images/delete-icon.gif"/></a>';
    newhtml += '</div>';
    $("#newphotobox" + AA_ID).html(newhtml);
    $('.addunselected').on('mouseover', function() {
        $(this).attr('src','/resources/images/esas_shekil.png');
    });

    $('.addunselected').on('mouseout', function() {
        if ($(this).attr('main') == 0) $(this).attr('src','/resources/images/esas_shekil_ol.png');
    });
}

function deletePhoto(pid, aid) {
    $.ajax({
        url: http_url+'/products/delete/'+pid,
        type: 'POST',
        data: {sid: U_ID, pid: pid},
        success: function(data)
        {

        }
    });
}
function mainPhoto(pid, U_ID, AA_ID) {
    $('.addunselected').attr('src','/resources/images/esas_shekil_ol.png');
    $('#addphoto'+AA_ID).attr('src','/resources/images/esas_shekil.png');
    $('.addunselected').attr('main', '0');
    $('#addphoto'+AA_ID).attr('main', '1');

    $.ajax({
        url: http_url+'/products/mainPhoto',
        type: 'POST',
        data: {sid: U_ID, pid: pid},
        success: function(data)
        {
        }
    });
}
var data = new Array();
function rotatePhoto(pid, ang, AA_ID) {
    data[AA_ID] = (data[AA_ID]) ? data[AA_ID] : 0;
    var oldangle = data[AA_ID];
    var angle =ang;
    angle = (angle == 1) ? oldangle + 90 : oldangle - 90;
    var exceed = (angle > 360) ? 1 : 0;
    if (exceed == 1){
        angle -= 360;
        oldangle -= 360;
    }
    data[AA_ID] = angle;
    console.log(oldangle + '-' + angle);
    $("#uploadimg" + AA_ID).rotate({
            angle: oldangle,
            animateTo: angle
            //easing: $.easing.easeInOutElastic
        }
    );

    $.ajax({
        url: http_url+'/products/rotate',
        type: 'POST',
        data: {sid: U_ID, pid: pid, angle: ang},
        success: function(data)
        {
            data = $.parseJSON(data);
            //$('#uploadimg'+AA_ID).attr('src', data.url+'?'+Math.random());
        }
    });
}


$('.req').on('blur', function() {
    var $this = $(this);
    if ($this.val() == '') {
        $this.addClass('errorMsg');
    } else {
        $this.removeClass('errorMsg');
        $this.next().text('');
    }
    return false;
});


$(" #Ads_milage, #Ads_price, #Ads_power_hp").keypress(function (e) {
    //if the letter is not digit then display error and don't type anything
    if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
        return false;
    }
});
