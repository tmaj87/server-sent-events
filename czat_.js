function get256() {
    return (Math.round(Math.random() * 180) + 50).toString(16);
}

function getColor() {
    var p1 = get256(),
            p2 = get256(),
            p3 = get256();
    return p1 + p2 + p3;
}

function error(msg) {
    $('#message_box').append('<div class="message"><span class="content" style="color: red">' + msg + '</span></div>');
}

var color = getColor();
var lastId = 0;
var iam = 0;

$.ajax({
    url: $('#message_form').attr('action'),
    type: 'post',
    data: {iam: 1},
    success: function (data) {
        iam = data;
    }
});

if (!!window.EventSource) {
    var obj = new EventSource($('#message_form').attr('action'));
    obj.addEventListener('message', function (e) {
        if (e.lastEventId == lastId) {
            return;
        }
        lastId = e.lastEventId;
        var data = JSON.parse(e.data);
        var element = $('<div class="message"><span class="time">' + data.t + '</span><span class="nick" style="color: #' + data.c + '">' + data.n + '<span class="hash">' + data.h + '</span></span><span class="content">' + data.m + '</span></div>');
        if (data.h != iam) {
            $('#sound_box').html('<audio autoplay><source src="czat_ding.wav" type="audio/wav"></audio>');
        }
        element.hide();
        if (data.c != color) {
            element.css('background', 'white');
        }
        $('#message_box').append(element);
        element.slideDown(200, function () {
            $('#message_box').scrollTop($('#message_box')[0].scrollHeight);
        });
    }, false);
    obj.addEventListener('users', function (e) {
        var data = JSON.parse(e.data);
        $('#users_box').html('');
        for (id in data) {
            $('#users_box').append('<span class="user" style="color: #' + data[id] + '">' + id + '</span>');
        }
    }, false);
}

$('#message_form input[name="c"]').val(color);
$('#message_form input[name="u"]').css('color', '#' + color);
$('#message_form').submit(function (e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: $(this).attr('method'),
        data: $(this).serializeArray(),
        success: function (data) {
            if (data != 'nop') {
                $('#message_form input[name="m"]').val('');
            } else {
                error('Poczekaj.');
            }
        }
    });
});