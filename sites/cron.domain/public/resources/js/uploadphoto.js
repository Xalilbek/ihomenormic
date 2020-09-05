/**
 * Created by kashmar on 8/15/14.
 */

var aids = new Array();
var pids = new Array();
var http_url = '';

$('.addphoto_xxxx').change(function(event) {
    var files = event.target.files;
    var qIndex = $(this).attr("qIndex");

    $.each(files, function(key, value){
        var data = new FormData();
        data.append('file', value);
        data.append('survey_id', SURVEY_ID);

        $('#ap_'+qIndex).on('click', function(event) {});
        $.ajax({
            url: '/survey/upload',
            type: 'POST',
            data: data,
            cache: false,
            processData: false,
            contentType: false,
            success: function(data){
                var data = $.parseJSON(data);
                console.log(data);
                if(data.status == 'error'){
                    alert(data.description);
                }else{
                    if(Object.keys(Survey.data[qIndex].answers).length < 1)
                        Survey.data[qIndex].answers = {};
                    Survey.data[qIndex].answers[data.data.id] = {
                        name: data.data.name,
                        type: data.data.type
                    }
                    Survey.render();
                }
            },
            error: function(jqXHR, textStatus, errorThrown){console.log('ERRORS: ' + textStatus);}
        });
    });
});

function stopExec(event){
    event.stopPropagation(); // Stop stuff happening
    event.preventDefault(); // Totally stop stuff happening
}

function generateBox(fileId, url){
    var newhtml = '';
    newhtml += '<div class="newphotobox">';
    newhtml += '<img width="120" height="120" src="' + url + '?' + Math.random() + '"/>';
    //newhtml += '</div>';
    newhtml += '<div style="width:100%" class="photohandleline">';
    newhtml += '<a class="photohandlea" href="javascript:deletePhoto(\'' + fileId + '\')"><img src="/resources/images/delete-icon.gif"/></a>';
    newhtml += '</div>';
    newhtml += '</div>';
    return newhtml;
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
