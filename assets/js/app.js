const toast = new siiimpleToast();
var link = window.location.origin;
var rpc = {};
var categories_p = new Array();
/*var link = 'https://awery.katrina.ae/booking_cake/index.php';*/
var min_pay = 0.3;

$(document).ready(function () {
    getCurrentYear();
    // обработка ответа при оплате
    const pay_res = window.location.href.split('?');
    if (pay_res[1]) {
        if (pay_res[1] == 'pay=success') {
            console.log('success');
            sweet_Alert('Success', 'success', true, false);
        } else if(pay_res[1].indexOf('pay') > -1){
            console.log('error');
            sweet_Alert('Error', 'error', true, false);
        }
    }
    if (window.location.hash && window.location.hash == '#_=_') {
        window.location.hash = '';
    }
    if (window.location.href.indexOf('phone_form=1') > -1) {
        $('#phone_form').modal();
    }
    if (window.location.href.indexOf('errors') > -1) {

        var url = new URL(window.location.href);
        var obj = url.searchParams.get('errors');

        errors = JSON.parse(obj);
        /*options.timeOut = 5000 * Object.keys(errors).length;*/
        showErrors(errors);
    }

    //Фиксация хедера
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('.fixed-header-container').addClass("fixed-scroll");
            $('.header-search-block').addClass('fixed');
        } else if ($(this).scrollTop() <= 100) {
            $('.fixed-header-container').removeClass("fixed-scroll");

            $('.header-search-block').removeClass('fixed');
        }
    });

    $('#menu-categories-fixed').height($('#sidebar-menu-dropdown').height() + 5);

    //Скрыть dropdown при клике на Login или User name
    $(document).on('click', function (e) {
        if ($(e.target).closest('.header-user-login').length === 0) {
            $('.login-block-dropdown').removeClass('active');
        }
        if ($(e.target).closest('#search-result-container').length === 0) {
            $('#search-result-container').hide();
        }
    });

    //Скролл при наведении на пункт меню на странице продуктов
    $(document).on("mouseenter", ".sidebar-link-wrap .hidden-dropdown", function (e) {
        $('#sidebar-menu-dropdown').animate({scrollTop: e.currentTarget.offsetTop}, 1000);
    });

    //Инициализация слайдера на главной странице
    if ($("div").is(".main-slide-container")) {
        $('.main-slide-container').slick({
            arrow: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            dots: false,
            speed: 1000,
            fade: true,
            prevArrow: '<button class="slick-prev slick-arrow" type="button" style="display: block;"></button>',
            nextArrow: '<button class="slick-next slick-arrow" type="button" style="display: block;"></button>',
            responsive:
                [
                    {
                        breakpoint: 850,
                        settings: {
                            slidesToShow: 1,
                            centerPadding: '100px',
                        }
                    },
                    {
                        breakpoint: 500,
                        settings: {
                            slidesToShow: 1,
                            centerPadding: '60px',
                            arrows: false,
                            autoplay: true
                        }
                    },
                    {
                        breakpoint: 350,
                        settings: {
                            slidesToShow: 1,
                            centerPadding: '40px',
                        }
                    },
                ]
        });
        $('.main-slide-container').show();
    }

    // Задать позиционирование вложеного списка меню в хедере.
    // Тоесть при наведении на пункт меню со вложеностями, нужно чтобы стрелочка этого пункта указывала на средину вложеного списка
    $(document).on("mouseenter", ".product-link > a", function (e) {
        $('.header-children-dropdown').each(function (index, elem) {
            var height = $(elem).height();
            var topPosition = height / 2 - height + 20;
            $(elem).css({top: topPosition > $('.header-menu-dropdown').offset().top ? $('.header-menu-dropdown').offset().top : topPosition + 'px'});
        });
    });


    // Инициализаци инпутов на странице регистрации
    if ($("input").is(".user-phone")) {
        $("#user-phone").intlTelInput({
            autoPlaceholder: "off",
            preferredCountries: ['ae', 'us', 'en'],
            separateDialCode: false,
            utilsScript: "/assets/libs/utils.js"
        });

    }

    $('.basket-header').on("click", function () {
        $("#delivery-conditions").modal('show');
        $('.delivery-close-modal-button').on("click", function () {
            window.location.href = '/basket';
        });
        return false;
    });

    $('.parent-link').on("click", function () {
        $(this).parent().children(".header-menu-dropdown").toggleClass('show-menu-item');
    });

    $('.hidden-dropdown span').on("click", function () {
        $(this).parent().children(".header-children-dropdown").toggleClass('show-menu-item');
    });

    $('#clickPay').on("click", function () {
        $('#pay_merchant').show();
    });

    $('#migs_sub').click(function () {
        getMigsPaylink();
    })

    $('.image-building').on("click", function () {
        var img = $(this).find('img')[0];
        var city_address = $(this).find('.city-address').val();
        var building_address = $(this).find('.building-address').val();
        $('.modal-building-image').attr('src', $(img).attr('src').replace('small.png', 'big.jpg'));
        $('.modal-city-address').html(city_address);
        $('.modal-building-address').html(building_address);
        $("#contact-building-image").modal('show');

    });
});

function showSideNavProdDetails(prod) {
    console.log(prod);
    $('body').toggleClass('blur-mask');
    var img = '';
    if (prod.image_id != null && prod.image_id > 0) {
        img = '<img src="https://awery.katrina.ae/system/downloads/cake_product_file.php?file_id=' + prod.image_id + '&thumb=2" class="img" >';
    }
    var price = 0;
    if (prod.price != null) {
        if (prod.price[prod.price.length - 1] == 0) {
            price = prod.price.slice(0, -1);
        } else {
            price = prod.price;
        }
        if (prod.price[prod.price.length - 2] == 0) {
            if (prod.price[prod.price.length - 3] == '.') {
                price = prod.price.slice(0, -3);
            } else {
                price = prod.price.slice(0, -2);
            }
        }
        price += ' AED';
    } else {
        price = '';
    }
    $('.product-details-items').html('');
    $('.product-details-items').append('\
                    <div class="details-img img">\
                         ' + img + '\
                    </div>\
                    <input class="hidden category" value="' + prod.category + '">\
                    <input class="hidden category_id" value="' + prod.category_id + '">\
                    <input class="hidden unit" value="' + prod.unit + '">\
                    <input class="hidden code" value="' + prod.code + '">\
                    <input class="hidden category_location_id" value="' + prod.category_location_id + '">\
                    <input type="number"  class="hidden" style="vertical-align: middle" id="for-price-details" value="' + prod.price.split(" ")[0] + '">\
                    <div class="descr" id="modal-descr-details">' + prod.descr + '</div>\
                    <div class="product-modal-remarks">' + prod.remarks + '</div>\
                    <input class="price" id="modal-price-details" value="' + price + '">\
                    <a class="prod_link" href="https://newkatrinasite.awery.com/single/' + prod.id + '"> Read more </a>\
                     <div class="qty" id="modal-qty-details">\
                        <button class="plus-minus" id="minus-details">-</button>\
                        <input type="text" id="modal-number-details" class="form-control" value="1">\
                        <button class="plus-minus" id="plus-details">+</button>\
                    </div>\
                    <div class="amount" id="modal-amount-details">' + price + ' </div>\
                   <button class="button-details-to-cart" onclick="appendToCart($(this).parent(), \'createItem\')">To cart</button>\
                ');

    $('#modal-number-details').keyup(function () {
        var $this = $(this);
        if ($this.val().length > 3)
            $this.val($this.val().substr(0, 3));
    });
    $('#modal-number-details').keyup(function () {
        var quantity = $('#modal-number-details').val();
        // console.log(quantity);
        var price = $('#for-price-details').val();
        // console.log(price);
        price = parseFloat(price) * parseFloat(quantity);
        // console.log(price);
        $('#modal-amount-details').text('');
        $('#modal-amount-details').text(price.toFixed(2) + ' AED');
    });
    $('#plus-details, #prod-plus-details').click(function () {
        var quantity = $('#modal-number-details').val();
        quantity++;
        $('#modal-number-details').val(quantity);
        var price = $('#for-price-details').val();
        console.log(price);
        console.log(quantity);
        price = parseFloat(price) * parseFloat(quantity);
        $('#modal-amount-details').text('');
        $('#modal-amount-details').text(price.toFixed(2) + ' AED');

    });
    $('#minus-details, #prod-minus-details').click(function () {
        var quantity = $('#modal-number-details').val();
        quantity--;
        if (quantity <= 1) {
            quantity = 1;
            price = parseFloat($('#for-price-details').val());
            $('#modal-number-details').val(1);
            $('#modal-amount-details').text(price.toFixed(2) + ' AED');
        }
        else {
            $('#modal-number-details').val(quantity);
            var price = $('#for-price-details').val();
            price = parseFloat(price) * parseFloat(quantity);
            $('#modal-amount-details').text('');
            $('#modal-amount-details').text(price.toFixed(2) + ' AED');
        }
    });
}

function appendToCart(element, type) {
    console.log('add to cart');
    console.log(element);
    var itm = {};
    itm['code'] = element.find('.code').val();
    itm['qty'] = element.find('.qty').find('input').val();
    itm['amount'] = element.find('.amount').html();
    itm['url'] = window.location.pathname;
    if (type === 'createItem') {
        itm['size'] = '';
        itm['size_id'] = 0;
        itm['category'] = element.children('.category').val();
        itm['category_id'] = element.children('.category_id').val();
        itm['unit'] = element.children('.unit').val();
        itm['price'] = element.children('.price').val();
        itm['starterprice'] = element.children('.price').val();
        itm['category_location_id'] = element.children('.category_location_id').val();
        itm['qty'] = element.children('.qty').children('input').val();

        itm['img'] = element.children('.img').html();
        itm['descr'] = element.children('.descr').html();
    }

    console.log(itm);

    $.ajax({
        type: "POST",
        async: true,
        data: {
            item: itm
        },
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/appendToCart',
        success: function (data) {
            console.log(data);
            if (data.error != 0) {
                alert(data.message);
            } else {
                $('#amount-purchases').text(data.data_list.basket.count);
                if (type === 'updateItem') {
                    $('#basket-total').text('Total Order: ' + data.data_list.basket.amount.toFixed(0) + ' AED');
                    $('#basket-total').attr('data-total', data.data_list.basket.amount.toFixed(0));
                    showSuccessProducts('Changes saved');
                } else if (type === 'createItem') {
                    element.parent().addClass('bounce');
                    element.parent().addClass('animated');
                    showSuccessProducts('Added to cart: ' + itm['descr']);
                }
            }
        },
        error: function (data) {
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

function Login(form) {
    var phone = form.find('#login').val();
    var pin = form.find('#pin').val();

    if (phone === '') {
        var text = 'Please enter your mobile phone No.';
        sweet_Alert(text, 'error', true, false);
    } else if (pin === '') {
        var text = 'Please enter your PIN';
        sweet_Alert(text, 'error', true, false);
    }
    var req = {
        phone: phone,
        pin: pin
    };
    $.ajax({
        type: "POST",
        async: true,
        data: JSON.stringify(req),
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/loginCustomer',
        success: function (data) {
            console.log('success: ', data);
            if (data.errors.length === 0) {
                document.location = '/';
            } else {
                const text = 'Customer not found or pin-code incorrect';
                sweet_Alert(text, 'error', true, false);
            }
        },
        error: function (data) {
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

function Register() {
    var mail_pattern = /[a-zA-Z0-9][a-zA-Z0-9\_\-]*@[a-zA-Z\_\-0-9]+\.[a-zA-Z]{2,6}/;
    var phone_pattern = /^[\+]?[(]?[0-9]{3}[)]?[0-9]{3}?[0-9]{4,6}$/im;
    var phone = $('#user-phone').val();
    var name = $('#user-name').val();
    var email = $('#user-email').val();

    if (name === '') {
        const text = 'Please enter your mobile phone No. to get SMS with new PIN';
        sweet_Alert(text, 'error', true, false);
    } else if (phone.substring(0, 1) !== '+' || !phone_pattern.test(phone)) {
        sweet_Alert('Please enter your mobile phone No. to get SMS with new PIN', 'error', true, false);
    } else if (!mail_pattern.test(email)) {
        sweet_Alert('Email not valid !', 'error', true, false);
    } else if (name === '') {
        sweet_Alert('Name can\'t be empty ', 'error', true, false);
    }

    var req = {
        name: name,
        main_phone: phone,
        main_email: email
    };

    var birthday = $('#user-birthday').val();
    if (birthday !== '') {
        var date_on_board = birthday.split("-");
        req['date_on_board'] = '1980-' + date_on_board[1] + '-' + date_on_board[0] + ' 00:00:00';
    }

    $.ajax({
        type: "POST",
        async: true,
        data: JSON.stringify(req),
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/register',
        success: function (data) {
            console.log('success: ', data);
            if (data.errors.length === 0) {
                document.location = '/';
            } else {
                sweet_Alert(data.errors.message, 'error', true, false);
            }
        },
        error: function (data) {
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

function registerByFb() {
    var phone = $('#fb_login').val();
    if (phone.substring(0, 1) !== '+') {
        sweet_Alert('Please enter your mobile phone No. to get SMS with new PIN', 'error', true, false);
    }
    $.ajax({
        type: 'POST',
        async: true,
        data: JSON.stringify({main_phone: $('#fb_login').val()}),
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: link + '/fb-register',
        success: function (data) {
            console.log(data);
            if (Object.keys(data.errors).length > 0) {
                showErrors(data.errors);
            } else {
                var reload = window.location.href.replace(window.location.search, '');
                reload = reload.replace(window.location.hash, '');
                window.location.href = reload;
            }
        },
        error: function (data) {
            console.log('error: ', data);
        }
    });
}

function Logout() {
    $.ajax({
        type: "POST",
        async: true,
        data: {},
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/logout',
        success: function (data) {
            console.log('success Logout: ', data);
            window.location = '/';
        },
        error: function (data) {
            console.log('error Logout: ', data);
        },
        dataType: 'json'
    });
}

function forgotPassword(tel) {
    var req = {
        tel: tel,
    };
    console.log('rpc request: ', req);
    $.ajax({
        type: "POST",
        async: true,
        data: JSON.stringify(req),
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/forgotPassword',
        success: function (data) {
            if (data.error.length === 0) {
                sweet_Alert(data.message, 'success', true, false);
                $('#forgot').prop('checked', false);
            } else {
                data.error.message ? sweet_Alert(data.error.message, 'error', true, false) : sweet_Alert('Something went wrong', 'error', true, false);
                $('#forgot').prop('checked', false);
            }
            console.log('success: ', data);
        },
        error: function (data) {
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

document.querySelector('#header-search-input').addEventListener('keyup', () => {
    setTimeout(function () {
        searchProducts($('.header-search').val());
    }, 2000)
});

function searchProducts(val) {
    $('#search-result-container').html('');
    if (val.length >= 3) {
        var req;
        req = JSON.stringify({descr: val});
        $('.icon-search').toggleClass('pending-processing');
        console.log('rpc request: ', req);
        $.ajax({
            type: "POST",
            async: true,
            data: req,
            xhrFields: {
                withCredentials: true
            },
            crossDomain: true,
            url: '/search',
            success: function (data) {
                $('#search-result-container').html('');
                const products = data.products;
                console.log('success: ', data);
                $('.icon-search').toggleClass('pending-processing');
                if (products) {
                    $('#search-result-container').show();
                    for (var key in products) {

                        var price = 0;
                        if (products[key].price != null) {
                            if (products[key].price[products[key].price.length - 1] == 0) {
                                price = products[key].price.slice(0, -1);
                            } else {
                                price = products[key].price;
                            }
                            if (products[key].price[products[key].price.length - 2] == 0) {
                                if (products[key].price[products[key].price.length - 3] == '.') {
                                    price = products[key].price.slice(0, -3);
                                } else {
                                    price = products[key].price.slice(0, -2);
                                }
                            }
                            price += ' AED';
                        } else {
                            price = '';
                            products[key].descr = '';
                        }

                        // console.log(products[key]);

                        $('#search-result-container').append('<div class="full-size search-result-block">\
                            <a class="full-size search-result-button" href="/single/' + products[key].id + '">\
                            <div class="search-image-place">\
                            <img class="full-size" src="https://awery.katrina.ae/system/downloads/cake_product_file.php?file_id=' + products[key].image_id + '&thumb=1">\
                            </div>\
                            <div class="search-result-info">\
                            <p class="title">' + products[key].descr + '</p>\
                            <div class="like-info-block">\
                           ' + price + '</div></div>\
                            </div>\
                            </a>\
                            </div>');
                    }
                }

            },
            error: function (data) {
                $('.icon-search').toggleClass('pending-processing');
                console.log('error: ', data);
            },
            dataType: 'json'
        });
    } else {
        $('#search-result-container').show();
        $('#search-result-container').append('No result');
    }
}

function removePriceFromCart(id) {
    var req = {
        price_id: id
    };

    $.ajax({
        type: "POST",
        data: JSON.stringify(req),
        async: true,
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/removeFromCartP',
        success: function (data) {
            console.log('success: ', data);
            if (data.error) {
                sweet_Alert(data.message, 'error', true, false);
            } else {
                window.location.reload();
            }
        }, error: function (data) {
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

function clearCart() {
    $.ajax({
        type: "POST",
        data: {},
        async: true,
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/clearCart',
        success: function (data) {
            console.log('success: ', data);
            if (data.error) {
                sweet_Alert(data.message, 'error', true, false);
            } else {
                window.location.reload();
            }
        }, error: function (data) {
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

function getCityDistricts(id) {

    var req = JSON.stringify({id: id});
    //console.log('rpc request: ', req);
    $.ajax({
        type: "POST",
        async: true,
        data: req,
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/getCityDistricts',
        success: function (data) {
            console.log('success: ', data);
            var districts = data.data_list;
            $('#districts').html('');
            $('#districts-same-day').html('');
            for (var key in districts) {
                $('#districts').append('<option value="' + districts[key].id + '" data-min_amount="' + districts[key].min_amount + '" data-distr="' + districts[key].district + '">' + districts[key].district + '</option>');
                $('#districts-same-day').append('<option value="' + districts[key].id + '" data-min_amount="' + districts[key].min_amount + '" data-distr="' + districts[key].district + '">' + districts[key].district + '</option>');
            }
        },
        error: function (data) {
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

function checkout() {
    var data = {};
    var pic_lat = $('#pic_lat').val();
    var pic_lng = $('#pic_lng').val();
    $('.get-quotation').toggleClass('pending-processing');
    data['type'] = $('input[name=type]:checked').val();
    if (pic_lat !== '')
        data['google_latitude'] = pic_lat;
    if (pic_lng !== '')
        data['google_longitude'] = pic_lng;

    if (data['type'] === 'pickup') {
        data['delivery_location_id'] = $('#warehouse').val();
        data['delivery_date'] = $('#ddate').val();
        data['delivery_address'] = $('#str').val();
    } else if (data['type'] === 'delivery') {
        data['delivery_date'] = $('#ddate_2').val();
        data['delivery_city_id'] = $('#cities').val();
        data['delivery_district_id'] = $('#districts').val();
        data['delivery_address'] = $('#str').val();
    } else if (data['type'] === 'delivery_same_day') {
        data['delivery_date'] = $('#ddate_2_sameday').val();
        data['delivery_city_id'] = $('#cities-same-day').val();
        data['delivery_district_id'] = $('#districts-same-day').val();
        data['delivery_address'] = $('#str-2').val();
        data['type'] = 'delivery';
    }
    data['comments'] = $('#prices_notes').val();
    data['email'] = $('#email-ord').val();
    data['name'] = $('#name-ord').val();

    var req = JSON.stringify(data);
    console.log('rpc request: ', req);

    $.ajax({
        type: "POST",
        async: true,
        data: req,
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/checkout',
        success: function (data) {
            console.log('posBookingsSync: ', data);
            $('.get-quotation').toggleClass('pending-processing');
            if (Object.keys(data.errors).length > 0) {
                showErrors(data.errors);
            } else {
                sweet_Alert_Success('Thank you, we prepare the quotation for your cake and will call you soon', 'success', true, false);

                setTimeout(function () {
                    window.location.href = '/';
                }, 2000);
            }
        },
        error: function (data) {
            $('.get-quotation').toggleClass('pending-processing');
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

function createCustomBooking(images) {
    var req = {};

    if ($('#current-tab').val() == 'pickup') {
        req['delivery_location_id'] = $('#warehouse').val();
        req['type'] = 'pickup';
        req['delivery_date'] = $('#ddate').val();
    } else {
        req['type'] = 'delivery';
        req['delivery_district_id'] = $('#districts').val();
        req['delivery_address'] = $('#address').val();
        req['delivery_city'] = $('#cities').val();
        req['delivery_date'] = $('#ddate2').val();
    }

    req['comments'] = $('#customcakemessage').val();
    req['full_name'] = $('#customcakecompany').val();
    req['phone'] = $('#customcakecphone').val();
    req['email'] = $('#customcakeemail').val();

    console.log('rpc request: ', req);
    $.ajax({
        type: "POST",
        async: true,
        data: JSON.stringify(req),
        crossDomain: true,
        url: '/createCustomBooking',
        success: function (data) {
            console.log('success: ', data);
            if (Object.keys(data.errors).length > 0) {
                showErrors(data.errors);
            } else {
                sweet_Alert_Success('Thank you, your order is accepted', 'success', true, false);
                const bookingDetails = data.booking;
                const orders = bookingDetails.orders;
                const order_id_image = orders[0].id;
                const booking_id = bookingDetails.booking.id;
                console.log('succes get order id:', order_id_image);
                console.log('succes get imgmass:', images);
                sendCustomImg(order_id_image, images, booking_id);
            }
        },
        error: function (data) {
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

function sendCustomImg() {
    /*console.log('booking_id : ', booking_id);
    console.log('images : ', images);
    console.error('order_id : ', order_id);*/
    var req = {};
    req['curl_upload'] = 'order_price_quote';
    /*req['order_id'] = order_id;
    req['images'] = images;
    req['booking_id'] = booking_id;*/
    req['order_id'] = 1009298;
    req['images'] = ['5eaab0cee7ffc65207500.jpg'];
    req['booking_id'] = 955067;

    $.ajax({
        type: "POST",
        async: true,
        data: JSON.stringify(req),
        crossDomain: true,
        url: '/uploadAttachToACM',
        success: function (data) {
            console.log('upload success', data)
            /*setTimeout(function () {
                window.location.href = '/';
            }, 2000);*/
        },
        error: function (data) {
            console.log('error: ', data);
        },
        dataType: 'json'
    });
}

function getMigsPaylink() {
    var pay_booking_id = $('#booking_id_for_pay').val();
    var pay_amount = $('#pam').val();
    if (pay_booking_id > 0 && pay_amount > 0) {
        $.ajax({
            type: "POST",
            async: true,
            data: {
                amount: pay_amount,
                booking_id: pay_booking_id,
            },
            xhrFields: {
                withCredentials: true
            },
            crossDomain: true,
            url: '/migs_request',
            success: function (data) {
                console.log(data);
                if (data.error == 0) {
                    swal({
                        title: 'Waiting!',
                        timer: 5000,
                        showConfirmButton: false,
                        showCancelButton: false,
                    });
                    window.location.href = data.return_link;
                } else {
                    alert(data.message);
                }
            },
            error: function (data) {
                console.log('error: ', data);
            },
            dataType: 'json'
        });
    }

}

function saveName() {
    var req = {};
    req['name'] = $('#new-user-name').val();
    console.log('rpc request: ', req);

    $.ajax({
        type: "POST",
        async: true,
        data: JSON.stringify(req),
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        url: '/updateCustomer',
        success: function (data) {
            if (Object.keys(data.errors).length > 0) {
                showErrors(data.errors);
            } else {
                setTimeout(function () {
                    window.location.href = '/';
                }, 2000);
            }
        }
    });
}

function showErrors(errors) {
    var mess = '';
    for (var key in errors) {
        mess += (errors[key].message ? errors[key].message : errors[key]) + '<br>';
    }

    sweet_Alert(mess, 'error', true, false);
}

function list_to_tree(list) {
    var map = {}, node, roots = [], i;
    for (i = 0; i < list.length; i += 1) {
        map[list[i].id] = i;
        list[i].children = [];
    }
    for (i = 0; i < list.length; i += 1) {
        node = list[i];
        if (node.pid !== "0") {
            list[map[node.pid]].children.push(node);
        } else {
            roots.push(node);
        }
    }
    return roots;
}

function ifForgot(val) {
    if ($('#login').val() == '' || $('#login').val() == null) {
        var text = 'Please enter your mobile phone No.';
        sweet_Alert(text, 'error', true, false);
        val.prop('checked', false);
    } else {
        if (val.is(':checked')) {
            console.log('checked');
            forgotPassword($('#login').val());
        }
    }
}

function changeAuthBlock(operation) {
    if (operation === 'login') {
        $('.sign-up-btn').removeClass('active');
        $('.sign-in-btn').addClass('active');

        $('.signup-inputs-block').removeClass('active');
        $('.signin-inputs-block').addClass('active');
    } else {
        $('.sign-up-btn').addClass('active');
        $('.sign-in-btn').removeClass('active');

        $('.signup-inputs-block').addClass('active');
        $('.signin-inputs-block').removeClass('active');
    }
}

function sweet_Alert(text, status, confirm, cancel) {
    swal({
        title: '<p class="error-text">' + text + '</p>',
        type: status,
        showConfirmButton: confirm,
        showCancelButton: cancel,
        confirmButtonColor: '#5cb85c',
        html: true
    });
}

function sweet_Alert_Success(text, status, confirm, cancel) {
    swal({
        title: '<p class="error-text">' + text + '</p>',
        type: status,
        showConfirmButton: confirm,
        showCancelButton: cancel,
        confirmButtonColor: '#5cb85c',
        html: true
    }, function (isConfirm) {

    });
}

function showSuccessProducts(mess) {
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    toastr.options.timeOut = 2000;
    toastr.success(mess, 'Success');
}

function share_fb() {
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + link, 'facebook-share-dialog', "width=626,height=436");
}

function share_twitter() {
    window.open('http://twitter.com/home?status=+' + link);
}

function headClickPrices() {
    $('.head').click(function () {
        /*getPricesByCategory($(this).data('id'));*/
        /*current_hash='#'+$(this).data('id');*/
        console.log('CLICKED!');
        window.location.href = window.location.origin + '/prices' + '#' + $(this).data('id');
        // getSiteProducts($(this).data('id'));

    });
}

function getCurrentYear() {
    document.querySelector('.current-year').innerText = new Date().getFullYear()
    ;
}
