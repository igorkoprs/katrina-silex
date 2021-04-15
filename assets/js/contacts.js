function showOnMap(markerkey) {
    map.setCenter(latLng[markerkey]);
    map.setZoom(14);
}

$(document).ready(function () {
    $('.menu-map').click(function () {
        showOnMap($(this).data('mapkey'));
        console.log($(this).data('mapkey'));
    });
});
var latLng = [
    {
        lat: 25.221829,
        lng: 55.434515
    },
    {
        lat: 25.1152201,
        lng: 55.19534
    },
    {
        lat: 25.2325937,
        lng: 55.3857605,
    },
    {
        lat: 25.0989525,
        lng: 55.2047854
    },
    {
        lat: 25.2552027,
        lng: 55.397251,
    },
    {
        lat: 25.1754482,
        lng: 55.3871286
    },
    {
        lat: 25.143454,
        lng: 55.206300
    },
    {
        lat: 24.333066,
        lng: 54.523828
    },
    {
        lat: 25.330868,
        lng: 55.429853
    },
    {
        lat: 24.312568,
        lng: 54.616897
    },
    {
        lat: 24.4880859,
        lng: 54.6085739,
    }
];
var all_markers = [];
var all_shops = [
    {
        lat: 25.221829,
        lng: 55.434515,
        name: 'Mirdif',
        tel: '04-251 55 77/ 04-284 71 81',
        social: '050-394 04 06',
        email: 'orders@katrinasweets.com',
        time: '7 a.m till 12 midnight every day'
//                ramadantime: '<b>' + 'Eid Timing' + '</b>' + '<br>' + '7 a.m till 12 midnight'
    },
    {
        lat: 25.1152201,
        lng: 55.19534,
        name: 'Al Barsha, Dubai',
        tel: '04-379 47 12/ 04-399 47 07',
        social: '056-573 17 27',
        email: 'katrinabarsha@gmail.com',
        time: '7 a.m till 12 midnight every day'
//                ramadantime: '<b>' + 'Eid Timing' + '</b>' + '<br>' + '7 a.m till 12 midnight'
    },
    {
        lat: 25.2325937,
        lng: 55.3857605,
        name: 'Al Rashidiya, Bin Sougat Centre, Dubai',
        tel: '04-284 09 61',
        social: '056-756 83 90',
        email: 'binsougat@katrinasweets.com',
        time: '7 a.m till 12 midnight every day'
//                ramadantime: '<b>' +  'Eid Timing' + '</b>' + '<br>' + '7 a.m till 12 midnight'
    },
    {
        lat: 25.0989525,
        lng: 55.2047854,
        name: 'Kiosk at Barsha Mall, Dubai',
        tel: '04-347 10 03',
        social: '',
        email: '',
        time: 'from 10 AM till 7:30 PM all days, temporary'
//                ramadantime: '<b>' + 'Eid Timing' + '</b>' + '<br>' +  '9 a.m. 10 p.m'
    },
    {
        lat: 25.2552027,
        lng: 55.397251,
        name: 'Kiosk at Emirates Coop, Dubai',
        tel: '056-640 82 92',
        social: '',
        email: '',
        time: 'from 9 AM till 11 PM all days, temporary'
//                ramadantime: '<b>' + 'Eid Timing' + '</b>'  + '<br>' +  '10 a.m till 11 p.m'
    },
    {
        lat: 25.1754482,
        lng: 55.3871286,
        name: 'Kiosk at Union Coop, Al Aweer',
        tel: '050-195-33-64',
        time: 'from 9 AM till 6 PM all days,  temporary',
        social: '',
        email: ''
//                ramadantime: ''
    },
    {
        lat: 25.143454,
        lng: 55.206300,
        name: 'Kiosk at Union Coop, Umm Suqeim',
        tel: '0501954471',
        social: '',
        email: '',
        time: 'from 9 AM till 11 PM all days, temporary'
//                ramadantime: ''
    },
    {
        lat: 24.333066,
        lng: 54.523828,
        name: 'Kiosk at Dalma Mall, Abu Dhabi',
        tel: '0508749169',
        social: '',
        email: '',
        time: 'suspended until Abu-Dhabi Gov. notice.'
    },
    {
        lat: 25.330868,
        lng: 55.429853,
        name: 'Al Shahba, Sharjah',
        tel: '065462131',
        social: 'https://wa.me/971503025720',
        email: 'ZawayaWalk@katrinasweets.com',
        time: 'Sat - Wed 10 am till 10 pm <br> Thu - Fri 10 am till 12 pm'
    },
    {
        lat: 24.312568,
        lng: 54.616897,
        name: 'Baniyas area, Abu-Dhabi',
        tel: '026321454',
        social: '050 134 7622',
        email: 'baniyas@katrinasweets.com',
        time: 'Sun - Wed 10 am till 10 pm <br> Thu - Sat 10 am till 12 pm'
    },
    {
        lat: 24.4880859,
        lng: 54.6085739,
        name: 'Yas Island, Abu-Dhabi',
        tel: '026212411',
        social: '0508467993',
        email: 'yasmall@katrinasweets.com',
        time: 'Sat - Wed 10 am till 10 pm <br> Thu - Fri 10 am till 12 pm'
    }
];

var map, infoBubble, to;


function initMap() {
    var styles = [
        {
            "featureType": "administrative",
            "elementType": "labels.text.fill",
            "stylers": [
                {
                    "color": "#444444"
                }
            ]
        },
        {
            "featureType": "landscape",
            "elementType": "all",
            "stylers": [
                {
                    "color": "#f2f2f2"
                }
            ]
        },
        {
            "featureType": "landscape.natural",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "on"
                },
                {
                    "color": "#e6e6e6"
                }
            ]
        },
        {
            "featureType": "poi",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "road",
            "elementType": "all",
            "stylers": [
                {
                    "saturation": -100
                },
                {
                    "lightness": 45
                }
            ]
        },
        {
            "featureType": "road",
            "elementType": "geometry.fill",
            "stylers": [
                {
                    "visibility": "on"
                },
                {
                    "hue": "#ff0000"
                }
            ]
        },
        {
            "featureType": "road.highway",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "simplified"
                }
            ]
        },
        {
            "featureType": "road.highway",
            "elementType": "geometry.fill",
            "stylers": [
                {
                    "color": "#d59f72"
                }
            ]
        },
        {
            "featureType": "road.highway",
            "elementType": "labels.text",
            "stylers": [
                {
                    "visibility": "simplified"
                }
            ]
        },
        {
            "featureType": "road.arterial",
            "elementType": "geometry.fill",
            "stylers": [
                {
                    "hue": "#ff0000"
                },
                {
                    "saturation": "1"
                }
            ]
        },
        {
            "featureType": "road.arterial",
            "elementType": "labels.icon",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "transit",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "transit.station",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "simplified"
                },
                {
                    "hue": "#ff0000"
                },
                {
                    "saturation": "-100"
                }
            ]
        },
        {
            "featureType": "transit.station.airport",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "on"
                }
            ]
        },
        {
            "featureType": "transit.station.bus",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "simplified"
                }
            ]
        },
        {
            "featureType": "transit.station.rail",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "simplified"
                },
                {
                    "hue": "#ff7e00"
                },
                {
                    "saturation": "-100"
                },
                {
                    "lightness": "19"
                }
            ]
        },
        {
            "featureType": "water",
            "elementType": "all",
            "stylers": [
                {
                    "color": "#d7d7d7"
                },
                {
                    "visibility": "on"
                }
            ]
        }
    ];

    var styledMap = new google.maps.StyledMapType(styles, {name: 'Styled Map'});

    var centerLatLng = new google.maps.LatLng(24.857125, 55.000000);
    var mapOptions = {
        zoom: 9,
        zoomControl: true,
        zoomControlOptions: {
            position: google.maps.ControlPosition.RIGHT_TOP
        },
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

    map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
    map.mapTypes.set('map_style', styledMap);
    map.setMapTypeId('map_style');


    infoBubble = new InfoBubble({
        map: map,
        position: new google.maps.LatLng(25.182732, 55.334515),
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

    google.maps.event.addListener(infoBubble, 'domready', function () {
        $('.pop-up').parent().parent().css('height', 'auto');
        $('.pop-up').parent().parent().css('top', '23px');
    });

    google.maps.event.addListener(map, "click", function () {
        infoBubble.close();
    });
    for (var i = 0; i < all_shops.length; i++) {
        var latLng = new google.maps.LatLng(all_shops[i].lat, all_shops[i].lng);
        var name = all_shops[i].name;
        var tel = all_shops[i].tel;
        var social = all_shops[i].social;
        var email = all_shops[i].email;
        var time = all_shops[i].time;
//                var ramadantime = all_shops[i].ramadantime;
        addMarker(latLng, name, tel, social, email, time);
    }
    $(window).resize(function () {
        clearTimeout(to);
        to = setTimeout(function () {
            map.setCenter(centerLatLng);
        }, 500);
    });
}

google.maps.event.addDomListener(window, "load", initMap);

function addMarker(latLng, name, tel, social, email, time) {
    var marker = new google.maps.Marker({
        position: latLng,
        map: map,
        title: name,
        content: tel + social + email + time,
//                ramadantime: ramadantime,
        icon: '/assets/images/icon/marker_cream.png'
    });
    all_markers.push(marker);
    google.maps.event.addListener(marker, "click", function () {
        var contentString = '<div class="pop-up">' +
            '<div>' +
            '<div class="pop-up-title">' + name +
            '</div>' + '<div class="pop-up-content">' +
            '<p>' + tel + '</p>' + '<p>' + social + '</p>' +
            '<p>' + email + '</p>' + '<p>' + time + '</p>'
            //                    '<p>' + ramadantime + '</p>'
            + '</div>' + '</div>' + '</div>';

        infoBubble.setContent(contentString);
        infoBubble.open(map, marker);
    });
}

$(document).ready(function () {
    $('.menu-map').click(function () {
        google.maps.event.trigger(all_markers[$(this).data('mapkey')], "click");
    });
});
$('.slides').on("click", function () {
    $('#metaslider_206').flexslider("next");
});

$('.slides').on("click", function () {
    $('#metaslider_207').flexslider("next");
});

$('.slides').on("click", function () {
    $('#metaslider_219').flexslider("next");
});

$('.slides').on("click", function () {
    $('#metaslider_220').flexslider("next");
});

$('.slides').on("click", function () {
    $('#metaslider_221').flexslider("next");
});
$('.slides').on("click", function () {
    $('#metaslider_334').flexslider("next");
});
$(".title-map-block").click(function () {
    $("#slide-panel").slideToggle("slow");
});
