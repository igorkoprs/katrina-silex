function changeCart(item, action) {
    console.log(item);
    item = $(item);
    var quantity = item.find('.number-basket').val();
    var price = item.find('.price').val();
    if (action === 'plus') {
        quantity++;
    } else if (action === 'minus') {
        if (quantity == 1) {
            return false;
        } else {
            quantity--;
        }
    }
    console.log(quantity);

    var amount = quantity * price;
    item.find('.amount-basket strong').text(amount.toFixed(2) + ' AED');
    item.find('.number-basket').val(quantity);

    appendToCart(item, 'updateItem');
}

$(document).ready(function () {
    $('input[name=type]').change(function () {
        if ($('input[name=type]:checked').val() == 'delivery_same_day') {
            $('.delivery-same').show();
            $('.delivery-hidden').hide();
            $('.pickup-hidden').hide();
            $('#pic_lat').val('');
            $('#pic_lng').val('');
            $('.str-2').append('<input type="text" class="form-control hidden google-search" placeholder="Street" id="str-2" value="">');
            initMapSameDay()
        } else if ($('input[name=type]:checked').val() == 'delivery') {
            $('.delivery-same').hide();
            $('.pickup-hidden').hide();
            $('.delivery-hidden').show();
            $('#pic_lat').val('');
            $('#pic_lng').val('');
            $('.str').append('<input type="text" class="form-control hidden google-search" placeholder="Street" id="str" value="">');
            initMapDelivery();
        } else if ($('input[name=type]:checked').val() == 'pickup') {
            $('.delivery-same').hide();
            $('.pickup-hidden').show();
            $('.delivery-hidden').hide();
            $('#pic_lat').val('');
            $('#pic_lng').val('');
            initMap();
        }
    });
    $('.xdsoft_timepicker.active').hide();
    initMap();
    checkDate();
    getCityDistricts($('#cities').val());

    $('#warehouse, #cities, #cities-same-day').select2();


    var date = new Date();
    var def_date = new Date(date.getFullYear(), date.getMonth(), date.getDate(), date.getHours() + 1, date.getMinutes());
    var def_time = date.getHours() + 1 + ':' + date.getMinutes();

    $('#ddate').datetimepicker({
        format: 'd/M/Y H:i',
        minDate: def_date,
        allowTimes: ['8:00', '9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00']
    });
    $('#ddate2').datetimepicker();
    $('#ddate_2').datetimepicker({
        format: 'd/M/Y H:i',
        minDate: new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1)
    });
    $('#ddate_2_sameday').datetimepicker({
        format: 'd/M/Y H:i',
        allowTimes: ['11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'],
        maxDate: 0,
        minDate: 0,
        defaultTime: def_time
    });
});

$('#pickup_span, label[for="pickup"]').click(function () {
    /*Redisable all this input*/
    $('#warehouse').prop('disabled', false);
    $('#ddate').prop('disabled', false);

    /*Disable all anouther input*/
    $('#cities').prop('disabled', true);
    $('#cities-same-day').prop('disabled', true);
    $('#districts-same-day').prop('disabled', true);
    $('#districts').prop('disabled', true);
    $('#ddate_2').prop('disabled', true);
    $('#str').prop('disabled', true);
    $('#ddate_2_sameday').prop('disabled', true);
    $('#str-2').prop('disabled', true);
    $('.min-amount > input').prop('disabled', true);

    // $('#pic_map').show();
});

$('#delivery_span, label[for="delivery"] ').click(function () {
    /*Disable all anouther input*/
    $('#cities').prop('disabled', false);
    $('#districts').prop('disabled', false);
    $('#ddate_2').prop('disabled', false);
    $('#str').prop('disabled', false);
    $('.min-amount > input').prop('disabled', false);

    /*Redisable all this input*/
    $('#warehouse').prop('disabled', true);
    $('#ddate').prop('disabled', true);
    $('#cities-same-day').prop('disabled', true);
    $('#districts-same-day').prop('disabled', true);
    $('#ddate_2_sameday').prop('disabled', true);
    $('#str-2').prop('disabled', true);

    //  $('#pic_map').hide();
});

$('#delivery_same_day_span, label[for="delivery_same_day"]').click(function () {
    /*Redisable all this input*/
    $('#cities-same-day').prop('disabled', false);
    $('#districts-same-day').prop('disabled', false);
    $('#ddate_2_sameday').prop('disabled', false);
    $('#str-2').prop('disabled', false);

    /*Disable all anouther input*/
    $('#cities').prop('disabled', true);
    $('#districts').prop('disabled', true);
    $('#ddate_2').prop('disabled', true);
    $('#str').prop('disabled', true);
    $('#warehouse').prop('disabled', true);
    $('#ddate').prop('disabled', true);
    $('.min-amount > input').prop('disabled', true);

    //   $('#pic_map').hide();
});


function SendOrder() {
    console.log('SendOrder');
    if ($('input[name=type]:checked').val() != undefined) {

        if ($('input[name=type]:checked').val() == 'delivery') {
            if ($('#cities').val() != null && $('#districts').val() != null && $('#ddate_2').val() != '' && $('#str').val() != '') {
                checkout();
                /*sweet_Alert('Thank you, your order is accepted', 'success', true, false);*/
                $('.footer-panel').css({"position": "inherit"});
                console.log($('#str').val());
            } else if ($('#c_body').length < 0) {
                console.log('cart is empty');
            } else {
                sweet_Alert('Fields are not filled', 'error', true, false);
                $('.footer-panel').css({"position": "inherit"});
            }
        }
        if ($('input[name=type]:checked').val() == 'delivery_same_day') {
            if ($('#cities-same-day').val() != null && $('#districts-same-day').val() != null && $('#ddate_2_sameday').val() != '' && $('#str-2').val() != '') {
                var total_sum = $('#basket-total').attr('data-total');
                if (total_sum < 100) {
                    sweet_Alert('Minimum order amount 100 AED', 'error', true, false);
                    $('.footer-panel').css({"position": "inherit"});
                    console.log('total sum: ', total_sum);
                } else {
                    checkout();
                }
            } else if ($('#c_body').length < 0) {
                console.log('cart is empty');
            } else {
                sweet_Alert('Fields are not filled', 'error', true, false);
                $('.footer-panel').css({"position": "inherit"});
                console.log($('#str-2').val());
            }
        }
        if ($('input[name=type]:checked').val() == 'pickup') {
            if ($('#warehouse').val() != '' && $('#ddate').val() != '') {
                checkout();
                $('.footer-panel').css({"position": "inherit"});
            } else {
                sweet_Alert('Fields are not filled', 'error', true, false);
                $('.footer-panel').css({"position": "inherit"});
            }
        }
    } else {
        sweet_Alert('Fields are not filled', 'error', true, false);
        $('.footer-panel').css({"position": "inherit"});
    }
}

$("#districts").click(function () {
    if ($('#districts').find(':selected').data('min_amount') != undefined)
        $('.min-amount').attr('value', 'Min amount ' + $('#districts').find(':selected').data('min_amount') + ' AED');
});
$("#districts-same-day").click(function () {
    if ($('#districts-same-day').find(':selected').data('min_amount') != undefined)
        $('.min-amount-delivery').attr('value', 'Min amount ' + $('#districts-same-day').find(':selected').data('min_amount') + ' AED');
});

$(function () {
    $('.delivery-time').datetimepicker({
            allowTimes: ['8:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'],
            useSeconds: false
        }
    );
});

function checkDate() {
    $('.reload_element').on("dp.show", function (e) {
        $('td.day').click(function () {
            console.log($('#ddate').val());
        });
    });
}

$('#pickup_span').click();

var styles = [
    {
        "stylers": [
            {
                "saturation": -100
            },
            {
                "gamma": 1
            }
        ]
    },
    {
        "elementType": "labels.text.stroke",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "poi.business",
        "elementType": "labels.text",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "poi.business",
        "elementType": "labels.icon",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "poi.place_of_worship",
        "elementType": "labels.text",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "poi.place_of_worship",
        "elementType": "labels.icon",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "geometry",
        "stylers": [
            {
                "visibility": "simplified"
            }
        ]
    },
    {
        "featureType": "water",
        "stylers": [
            {
                "visibility": "on"
            },
            {
                "saturation": 50
            },
            {
                "gamma": 0
            },
            {
                "hue": "#50a5d1"
            }
        ]
    },
    {
        "featureType": "administrative.neighborhood",
        "elementType": "labels.text.fill",
        "stylers": [
            {
                "color": "#333333"
            }
        ]
    },
    {
        "featureType": "road.local",
        "elementType": "labels.text",
        "stylers": [
            {
                "weight": 0.5
            },
            {
                "color": "#333333"
            }
        ]
    },
    {
        "featureType": "transit.station",
        "elementType": "labels.icon",
        "stylers": [
            {
                "gamma": 1
            },
            {
                "saturation": 50
            }
        ]
    }
];
var all_shops = [
    {
        id: 16,
        lat: 25.221829,
        lng: 55.434515,
        name: 'Mirdif',
        tel: '04-251 55 77/ 04-284 71 81',
        social: '050-394 04 06',
        email: 'orders@katrinasweets.com',
        time: '7 a.m till 12 midnight every day'
    },
    {
        id: 2,
        lat: 25.099150,
        lng: 55.205056,
        name: 'Al Barsha, Dubai',
        tel: '04-379 47 12/ 04-399 47 07',
        social: '056-573 17 27',
        email: 'katrinabarsha@gmail.com',
        time: '7 a.m till 12 midnight every day'
    },
    {
        id: 14,
        lat: 25.232732,
        lng: 55.385531,
        name: 'Al Rashidiya, Bin Sougat Centre, Dubai',
        tel: '04-284 09 61',
        social: '056-756 83 90',
        email: 'binsougat@katrinasweets.com',
        time: '7 a.m till 12 midnight every day'
    }
];
var infoBubble = new InfoBubble({
    map: map,
    position: new google.maps.LatLng(25.221829, 55.434515),
    padding: 0,
    hideCloseButton: true,
    backgroundColor: 'transparent',
    borderWidth: 0,
    borderRadius: 3,
    arrowSize: 0,
    shadowStyle: 0,
    zIndex: null,
    disableAnimation: true
});
var styledMap = new google.maps.StyledMapType(styles, {name: 'Styled Map'});
centerLatLng = new google.maps.LatLng(25.221829, 55.434515);
var mapOptions = {
    zoom: 10,
    navigationControl: false,
    mapTypeControl: false,
    scaleControl: false,
    streetViewControl: false,
    overviewMapControl: false,
    scrollwheel: false,
    disableDoubleClickZoom: true,
    rotateControl: false,

    center: centerLatLng, mapTypeControlOptions: {
        mapTypeIds: [google.maps.MapTypeId.ROADMAP, 'map_style']
    }
};

var map, centerLatLng, to;
var all_markers = [];

function initMap() {
    google.maps.event.addDomListener(window, 'load', initMap);
    map = new google.maps.Map(document.getElementById("pic_map"), mapOptions);

    var marker = new google.maps.Marker({
        position: centerLatLng,
        map: map,
        icon: '/assets/images/icon/marker_cream.png'
    });

    map.mapTypes.set('map_style', styledMap);
    map.setMapTypeId('map_style');

    google.maps.event.addListener(infoBubble, 'domready', function () {
        $('.pop-up').parent().parent().css('height', 'auto');
        $('.pop-up').parent().parent().css('top', '23px');
    });

    google.maps.event.addListener(map, "click", function (e) {
        infoBubble.close();
        placeMarker(e.latLng, map, marker);
    });

    for (var i = 0; i < all_shops.length; i++) {
        var latLng = new google.maps.LatLng(all_shops[i].lat, all_shops[i].lng);
        var name = all_shops[i].name;
        var tel = all_shops[i].tel;
        var social = all_shops[i].social;
        var email = all_shops[i].email;
        var time = all_shops[i].time;
        addMarker(latLng, name, tel, social, email, time);
    }

    function addMarker(latLng, name, tel, social, email, time) {
        var marker = new google.maps.Marker({
            position: latLng,
            map: map,
            title: name,
            content: tel + social + email + time,
            icon: '/assets/images/icon/marker_cream.png'
        });
        all_markers.push(marker);

        google.maps.event.addListener(marker, "click", function () {
            var contentString = '<div class="pop-up">' +
                '<div>' +
                '<div class="pop-up-title">' + name +
                '</div>' + '<div class="pop-up-content">' +
                '<p>' + tel + '</p>' + '<p>' + social + '</p>' +
                '<p>' + email + '</p>' + '<p>' + time + '</p>' + '</div>' +
                '</div>' + '</div>';

            infoBubble.setContent(contentString);
            infoBubble.open(map, marker);
        });
    }
}

function placeMarker(position, map, marker) {
    if (marker == null) {
        marker = new google.maps.Marker({
            position: position,
            map: map,
            icon: '/assets/images/icon/marker_cream.png'
        });
        console.log(1);
    } else {
        marker.setPosition(position);
        $('#pic_lat').val(position.lat().toFixed(7));
        $('#pic_lng').val(position.lng().toFixed(7));
        google.maps.event.addListener(marker, "click", function () {
            var contentString = '<div class="pop-up">' +
                '<div>' +
                '<div class="pop-up-title">' + 'Pickup' +
                '</div>' + '<div class="pop-up-content">' +
                '<p> Latitude: ' + position.lat().toFixed(7) + '</p>' +
                '<p> Longitude: ' + position.lng().toFixed(7) + '</p>' +
                '</div>' + '</div>';
            infoBubble.setContent(contentString);
            infoBubble.open(map, marker);

            console.log(marker);
            console.log(position);
        });
    }
}

function initMapDelivery() {
    google.maps.event.addDomListener(window, 'load', initMapDelivery);
    map = new google.maps.Map(document.getElementById("map_place_id"), mapOptions);

    var marker = new google.maps.Marker({
        position: centerLatLng,
        map: map,
        icon: '/assets/images/icon/marker_cream.png'
    });

    map.mapTypes.set('map_style', styledMap);
    map.setMapTypeId('map_style');

    google.maps.event.addListener(infoBubble, 'domready', function () {
        $('.pop-up').parent().parent().css('height', 'auto');
        $('.pop-up').parent().parent().css('top', '23px');
    });

    google.maps.event.addListener(map, "click", function (e) {
        infoBubble.close();
        marker.setMap(null);
        for (var i = 0; i < all_shops.length; i++) {
            var latLng = new google.maps.LatLng(all_shops[i].lat, all_shops[i].lng);
            var name = all_shops[i].name;
            var tel = all_shops[i].tel;
            var social = all_shops[i].social;
            var email = all_shops[i].email;
            var time = all_shops[i].time;
            addMarker(latLng, name, tel, social, email, time);
        }
        marker = new google.maps.Marker({
            position: e.latLng,
            map: map,
            icon: '/assets/images/icon/marker_cream.png'
        });
        all_markers.push(marker);
        placeMarker(e.latLng, map, marker);
    });

    for (var i = 0; i < all_shops.length; i++) {
        var latLng = new google.maps.LatLng(all_shops[i].lat, all_shops[i].lng);
        var name = all_shops[i].name;
        var tel = all_shops[i].tel;
        var social = all_shops[i].social;
        var email = all_shops[i].email;
        var time = all_shops[i].time;
        addMarker(latLng, name, tel, social, email, time);
    }

    function addMarker(latLng, name, tel, social, email, time) {
        var marker = new google.maps.Marker({
            position: latLng,
            map: map,
            title: name,
            content: tel + social + email + time,
            icon: '/assets/images/icon/marker_cream.png'
        });
        all_markers.push(marker);

        google.maps.event.addListener(marker, "click", function () {
            var contentString = '<div class="pop-up">' +
                '<div>' +
                '<div class="pop-up-title">' + name +
                '</div>' + '<div class="pop-up-content">' +
                '<p>' + tel + '</p>' + '<p>' + social + '</p>' +
                '<p>' + email + '</p>' + '<p>' + time + '</p>' + '</div>' +
                '</div>' + '</div>';

            infoBubble.setContent(contentString);
            infoBubble.open(map, marker);
        });
    }

    var input = document.getElementById("str");
    input.classList.remove('hidden');
    var autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo('bounds', map);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    autocomplete.addListener('place_changed', function () {
        infoBubble.close();
        var place = autocomplete.getPlace();
        if (!place.geometry) {
            return;
        }

        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }

        // Set the position of the marker using the place ID and location.
        marker.setPlace({
            placeId: place.place_id,
            location: place.geometry.location
        });

        marker.setPosition(place.geometry.location);
        $('#pic_lat').val(place.geometry.location.lat().toFixed(7));
        $('#pic_lng').val(place.geometry.location.lng().toFixed(7));
        $('.google-search')[0].value = input.value;

        var contentString = '<div class="pop-up">' +
            '<div>' +
            '<div class="pop-up-title">' + place.name +
            '</div>' + '<div class="pop-up-content">' +
            '<p> Latitude: ' + place.geometry.location.lat().toFixed(7) + '</p>' +
            '<p> Longitude: ' + place.geometry.location.lng().toFixed(7) + '</p>' +
            '</div>' + '</div>';
        infoBubble.setContent(contentString);
        infoBubble.open(map, marker);
    });
}

function initMapSameDay() {
    google.maps.event.addDomListener(window, 'load', initMapSameDay);
    map = new google.maps.Map(document.getElementById("map_place_same_day"), mapOptions);

    var marker = new google.maps.Marker({
        position: centerLatLng,
        map: map,
        icon: '/assets/images/icon/marker_cream.png'
    });

    map.mapTypes.set('map_style', styledMap);
    map.setMapTypeId('map_style');

    google.maps.event.addListener(infoBubble, 'domready', function () {
        $('.pop-up').parent().parent().css('height', 'auto');
        $('.pop-up').parent().parent().css('top', '23px');
    });

    google.maps.event.addListener(map, "click", function (e) {
        infoBubble.close();
        marker.setMap(null);
        for (var i = 0; i < all_shops.length; i++) {
            var latLng = new google.maps.LatLng(all_shops[i].lat, all_shops[i].lng);
            var name = all_shops[i].name;
            var tel = all_shops[i].tel;
            var social = all_shops[i].social;
            var email = all_shops[i].email;
            var time = all_shops[i].time;
            addMarker(latLng, name, tel, social, email, time);
        }
        marker = new google.maps.Marker({
            position: e.latLng,
            map: map,
            icon: '/assets/images/icon/marker_cream.png'
        });
        all_markers.push(marker);
        placeMarker(e.latLng, map, marker);
    });

    for (var i = 0; i < all_shops.length; i++) {
        var latLng = new google.maps.LatLng(all_shops[i].lat, all_shops[i].lng);
        var name = all_shops[i].name;
        var tel = all_shops[i].tel;
        var social = all_shops[i].social;
        var email = all_shops[i].email;
        var time = all_shops[i].time;
        addMarker(latLng, name, tel, social, email, time);
    }

    function addMarker(latLng, name, tel, social, email, time) {
        var marker = new google.maps.Marker({
            position: latLng,
            map: map,
            title: name,
            content: tel + social + email + time,
            icon: '/assets/images/icon/marker_cream.png'
        });
        all_markers.push(marker);

        google.maps.event.addListener(marker, "click", function () {
            var contentString = '<div class="pop-up">' +
                '<div>' +
                '<div class="pop-up-title">' + name +
                '</div>' + '<div class="pop-up-content">' +
                '<p>' + tel + '</p>' + '<p>' + social + '</p>' +
                '<p>' + email + '</p>' + '<p>' + time + '</p>' + '</div>' +
                '</div>' + '</div>';

            infoBubble.setContent(contentString);
            infoBubble.open(map, marker);
        });
    }

    var input = document.getElementById("str-2");
    input.classList.remove('hidden');
    var autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo('bounds', map);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    autocomplete.addListener('place_changed', function () {
        infoBubble.close();
        var place = autocomplete.getPlace();
        if (!place.geometry) {
            return;
        }

        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }

        // Set the position of the marker using the place ID and location.
        marker.setPlace({
            placeId: place.place_id,
            location: place.geometry.location
        });

        marker.setPosition(place.geometry.location);
        $('#pic_lat').val(place.geometry.location.lat().toFixed(7));
        $('#pic_lng').val(place.geometry.location.lng().toFixed(7));
        $('.google-search')[2].value = input.value;

        var contentString = '<div class="pop-up">' +
            '<div>' +
            '<div class="pop-up-title">' + place.name +
            '</div>' + '<div class="pop-up-content">' +
            '<p> Latitude: ' + place.geometry.location.lat().toFixed(7) + '</p>' +
            '<p> Longitude: ' + place.geometry.location.lng().toFixed(7) + '</p>' +
            '</div>' + '</div>';
        infoBubble.setContent(contentString);
        infoBubble.open(map, marker);
    });
}


$('#warehouse').change(function () {
    var selectedBranch = {};

    for (var key in all_shops) {
        if (all_shops[key].id == $('#warehouse').val())
            selectedBranch = all_shops[key];
    }
    console.log(selectedBranch);

    const coors = new google.maps.LatLng(selectedBranch.lat, selectedBranch.lng);
    var markerFromSelect = new google.maps.Marker({
        position: coors,
        map: map,
        title: selectedBranch.name,
        content: selectedBranch.tel + selectedBranch.social + selectedBranch.email + selectedBranch.time,
        icon: '/assets/images/icon/marker_cream.png'
    });
    var contentString = '<div class="pop-up">' +
        '<div>' +
        '<div class="pop-up-title">' + selectedBranch.name +
        '</div>' + '<div class="pop-up-content">' +
        '<p>' + selectedBranch.tel + '</p>' + '<p>' + selectedBranch.social + '</p>' +
        '<p>' + selectedBranch.email + '</p>' + '<p>' + selectedBranch.time + '</p>' + '</div>' +
        '</div>' + '</div>';
    infoBubble.setContent(contentString);
    infoBubble.open(map, markerFromSelect);

    google.maps.event.addListener(map, "click", function (e) {
        infoBubble.close();
        placeMarker(e.latLng, map, markerFromSelect);
    });
});

$('#districts, #cities').change(function () {
    setTimeout(function () {
        $('.google-search')[1].value = $('#districts').find(':selected').data('distr');
        $('.google-search')[1].focus();
    }, 300);
    if ($('.google-search').length == 5) {
        $('.hidden-search').remove();
    }
});
$('#districts-same-day, #cities-same-day').change(function () {
    setTimeout(function () {
        if ($('.google-search').length == 3) {

            $('.str').append('<input type="text" class="hidden google-search hidden-search" placeholder="Street" value="">');
            $('.google-search')[3].value = $('#districts-same-day').find(':selected').data('distr');
            $('.google-search')[3].focus();
        } else if ($('.google-search').length == 4) {

            $('.google-search')[3].value = $('#districts-same-day').find(':selected').data('distr');
            $('.google-search')[3].focus();
        }
    }, 300);
});