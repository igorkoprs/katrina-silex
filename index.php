<?php
  
  require_once(__DIR__ . '/config/bootstrap.php');
  
  use Base\JsonRPC;
  use Symfony\Component\HttpFoundation\Response;
  use Symfony\Component\HttpFoundation\Request;
  
  $app['transport'] = (new Swift_SmtpTransport('email-smtp.eu-west-1.amazonaws.com', 25, 'tls'))
    ->setUsername('AKIAJB6RAFABLFECEN7A')
    ->setPassword('AvbuMmlceESCZvvQVBBm9DLvHHrqL3A9qXMNs6XJH73F')
    ->setAuthMode('login');
  
  $app['mailer'] = new Swift_Mailer($app['transport']);
  
  $app->get('/', function () use ($app, $base) {
    
    /*$news = $base->getMainPageNews();
    $main = $base->getPropertyMainContent();*/
    
    return $app['twig']->render('main.twig', ['news' => '', 'main' => '']);
  });
  
  $app->get('/getUserIdentifer', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {
    return $app->json(array('error' => 0, 'identifer' => $app['user']['identifer']));
  })->bind('gui');
  
  $app->match('/create-your-cake', function () use ($app, $base) {
    $warehouses = $base->getWarehouses();
    $cities = $base->getCities();
    
    return $app['twig']->render('pages/custom-cake.twig', array('warehouses' => $warehouses, 'cities' => $cities));
  })->bind('createYourCake');
  
  $app->match('/prices/{id}', function ($id) use ($app, $base) {
    $res = $base->getSiteProducts(array("site_category_id" => $id));
    /*if (!(bool)$res) return $app['twig']->render('pages/.twig');*/
    if (isset($_GET['dd']) && $_GET['dd'] == 1) {
      echo '<pre>';
      var_dump($res);
      die();
    }
    return $app['twig']->render('pages/categories.twig', array('products' => $res));
  })->bind('pricesProducts');
  
  $app->get('/prices', function () use ($app, $base) {
    return $app['twig']->render('pages/categories.twig', array('products' => 10));
  });
  
  $app->match('/search-prices/{inputValue}', function ($inputValue) use ($app, $base) {
//    $data = json_decode($request->getContent(), true);
    
    $products = $base->getSiteProductsByDescr($inputValue);
    
    if ($products)
      foreach ($products as &$item) {
        $item['descr'] = strtolower($item['descr']);
      }
    unset($item);
    return $app['twig']->render('pages/categories.twig', array('products' => $products));
    // return $app->json(['products' => $products]);
  })->bind('pricesSearchProducts');
  
  
  // https://katrina.ae/single/1827
  
  $app->get('/single/{id}', function ($id) use ($app, $base) {
    $res['products'] = $base->getSiteProductsByIds((int)$id);
    if (!(bool)$res['products'] && !(bool)$res['categories'])
      return $app['twig']->render('pages/404.twig');

    $product = $res['products'][0];

    if (isset($product['descr']))
      $app['title'] = $product['descr'];

    if (isset($_GET['dd']) && $_GET['dd'] == 1) {
      echo '<pre>';
      var_dump($product);
      die();
    }

    return $app['twig']->render('pages/product.twig', [
      'product' => $product,
    ]);

  })->bind('singleProduct');
  
  // https://katrina.ae/single?category=12&id=1827
  
//  $app->get('/single', function (Symfony\Component\HttpFoundation\Request $request) use ($app, $base) {
//    $id = $request->get('id');
//    $category = $request->get('category');
//    $res['products'] = $base->getSiteProductsByIds((int)$id);
//    if (!(bool)$res['products'] && !(bool)$res['categories'])
//      return $app['twig']->render('pages/404.twig');
//
//    $product = $res['products'][0];
//
//    if (isset($product['descr']))
//      $app['title'] = $product['descr'];
//
//    if (isset($_GET['dd']) && $_GET['dd'] == 1) {
//      echo '<pre>';
//      var_dump($product);
//      die();
//    }
//
//    return $app['twig']->render('pages/product.twig', [
//      'product' => $product,
//    ]);
//
//  })->bind('singleProduct');
  
  $app->post('/search', function (Request $request) use ($app, $base) {
    $data = json_decode($request->getContent(), true);
    
    $products = $base->getSiteProductsByDescr($data['descr']);
    
    if ($products)
      foreach ($products as &$item) {
        $item['descr'] = strtolower($item['descr']);
      }
    unset($item);
    
    return $app->json(['products' => $products]);
  })->bind('search');
  
  $app->post('/appendToCart', function (Symfony\Component\HttpFoundation\Request $request) use ($app, $base) {
    $item = $request->get('item');
    $item['date'] = date('Y-m-d H:i:s');
    $item['id_user'] = $app['user']['id'];
    $sql = "SELECT `id`, `qty`, `amount` FROM `prices` WHERE `id_user` = ? AND `code` = ?";
    $already = $app['db']->fetchAssoc($sql, array($app['user']['id'], $item['code']));
    if (empty($already)) {
      $item['img'] = trim($item['img']);
//        $app['db']->insert('prices', $item);
      $app['db']->insert('prices', array(
        'id_user' => $item['id_user'],
        'date' => $item['date'],
        'amount' => $item['amount'],
        'descr' => $item['descr'],
        'qty' => $item['qty'],
        'category' => $item['category'],
        'category_id' => $item['category_id'],
        'code' => $item['code'],
        'img' => $item['img'],
        'price' => $item['price'],
        'size' => $item['size'],
        'size_id' => $item['size_id'],
        'starterprice' => $item['starterprice'],
        'unit' => $item['unit'],
        'details' => $item['descr'],
        'category_location_id' => (int)$item['category_location_id']
      ));
    } else {
      if ($item['url'] == '/basket') {
        $app['db']->update('prices', array('qty' => $item['qty'], 'amount' => (float)$item['amount']), array('id' => $already['id']));
      } else {
        $app['db']->update('prices', array('qty' => $already['qty'] + $item['qty'], 'amount' => $already['amount'] + (float)$item['amount']), array('id' => $already['id']));
      }
    }
    
    $basket = $base->getUserCart();
    
    return $app->json(array(
      'error' => 0,
      'message' => 'All right',
      'data_list' => array(
        'basket' => $basket
      )));
  })->bind('appendToCart');
  
  $app->get('/login', function () use ($app, $base) {
    /*if(isset($app['userData']['id']) && (int)$app['userData']['id'] > 0)
        return $app->redirect('/');*/
    return $app['twig']->render('pages/auth.twig');
  });
  
  $app->post('/forgotPassword', function (Request $request) use ($app, $auth) {
    $data = json_decode($request->getContent(), true);
    $mess = $auth->resetCustomerPinCode($data['tel']);
    
    $errors = [];
    if ($mess == 'Phone No. not found!') {
      $errors = ['message' => $mess];
    }
    
    $app['errors'] = $errors;
    return $app->json(['data' => $data, 'error' => $errors, 'message' => $mess]);
  })->bind('forgotPassword');
  
  $app->post('/register', function (Request $request) use ($app, $auth, $base) {
    $data = json_decode($request->getContent(), true);
    
    $errors = [];
    $data['main_phone'] = str_replace(array(' ', '-'), '', $data['main_phone']);
    if (!isset($data['name']) || strlen($data['name']) < 1)
      $errors = array('message' => 'Empty name!');
    if (!isset($data['main_phone']) || strlen($data['main_phone']) < 13)
      $errors = array('message' => 'Empty phone!');
    if (!empty($data['main_phone'][0]) && $data['main_phone'][0] != '+')
      $errors = array('message' => 'Wrong format phone !');
    
    if (empty($errors)) {
      $req = [];
      $req['main_email'] = $data['main_email'];
      $req['company'] = $data['name'];
      $req['main_phone'] = $data['main_phone'];
      if (isset($data['date_on_board']) && $data['date_on_board'] != '')
        $req['date_on_board'] = $data['date_on_board'];
      
      $res = $auth->signUp($req);
      
      if ($res !== true)
        $errors = array('message' => $res);
    }
    
    $app['errors'] = $errors;
    
    return $app->json(['data' => $data, 'errors' => $errors]);
  })->bind('registerPost');
  
  $app->post('/logout', function () use ($app, $auth) {
    if (isset($_COOKIE['CUSTID'])) {
      $app['session']->remove('CUSTID');
      setcookie('CUSTID', '', time() - 36000, '/');
    }
    return $app->json(['res' => true, 'errors' => 0]);
  })->bind('logout');
  
  $app->post('/loginCustomer', function (Request $request) use ($app, $auth, $base) {
    $data = json_decode($request->getContent(), true);
    $error = array();
    if (!isset($data['phone']) || strlen($data['phone']) < 10)
      $error['phone'] = ['message' => 'Not valid Phone number!'];
    
    if (!isset($data['pin']) || strlen($data['pin']) > 4)
      $error['pin'] = ['message' => 'Not valid Password!'];
    
    $res = null;
    if (empty($error)) {
      $res = $auth->signIn($data);
    }
    
    if (!$res)
      $error['phone'] = ['message' => 'Something wrong! You are not login'];
    
    $app['errors'] = $error;
    
    return $app->json(['data' => $data, 'errors' => $error]);
  })->bind('loginCustomer');
  
  $app->get('/basket', function (Symfony\Component\HttpFoundation\Request $request) use ($app, $base) {
    return $app['twig']->render('pages/404.twig');
    $items = $base->getUserCart(true);
    $warehouses = $base->getWarehouses();
    $cities = $base->getCities();
    return $app['twig']->render('pages/basket.twig',
      array('error' => 0,
        'cart' => $items['cart'],
        'count' => $items['count'],
        'amount' => $items['amount'],
        'warehouses' => $warehouses,
        'cities' => $cities)
    );
  })->bind('basket');
  
  $app->post('/getCityDistricts', function (Request $request) use ($app, $base) {
    $data = json_decode($request->getContent(), true);
    
    $districts = $base->getCityDistricts($data['id']);
    
    return $app->json(['data_list' => $districts, 'message' => 'All right']);
  })->bind('getCities');
  
  $app->post('/removeFromCartP', function (Symfony\Component\HttpFoundation\Request $request) use ($app, $base) {
    $data = json_decode($request->getContent(), true);
    if (!isset($data['price_id']) || $data['price_id'] == '') {
      return $app->json(array('error' => $data['price_id'], 'message' => 'Receive incorrect ID'));
    }
    $sql = "SELECT * FROM `prices` WHERE `id` = ?";
    $price = $app['db']->fetchAssoc($sql, array($data['price_id']));
    if (!$price)
      return $app->json(array('error' => 2, 'message' => 'Price not found'));
    else {
      $app['db']->delete('prices', array('id' => (int)$data['price_id']));
    }
    
    return $app->json(array('error' => 0, 'message' => 'Success'));
  })->bind('rfcp');
  
  $app->post('/clearCart', function (Symfony\Component\HttpFoundation\Request $request) use ($app, $base) {
    $sql = "SELECT * FROM `custom_cakes` WHERE `id_user` = ?";
    $cakes = $app['db']->fetchAll($sql, array($app['user']['id']));
    foreach ($cakes as $cake) {
      $app['db']->delete('cake_prices', array('id_cc' => $cake['id']));
      $app['db']->delete('custom_cakes', array('id' => $cake['id']));
    }
    
    $app['db']->delete('prices', array('id_user' => $app['user']['id']));
    
    return $app->json(array('error' => 0, 'message' => 'Success'));
  })->bind('clearCart');
  
  $app->post('/checkout', function (Request $request) use ($app, $auth, $base) {
    $data = json_decode($request->getContent(), true);
    $req = [];
    $errors = [];
    $carts = $base->getUserCart(true);
    
    if (!isset($app['userData']) || empty($app['userData']) || empty($carts['cart'])) {
      $errors['not_login'] = ['message' => 'Error'];
    }
    
    if (!isset($data['type']) || strlen($data['type']) < 1) {
      $errors['delivery_type'] = ['message' => 'Wrong Delivery type!'];
    }
    
    if (!isset($data['name']) || strlen($data['name']) < 1) {
      $errors['name'] = ['message' => 'Empty Name!'];
    }
    
    if (isset($data['email']) && strlen($data['email']) > 1) {
      $data['email'] = $app['userData']['main_email'];
    }
    
    if ($data['type'] == 'pickup') {
      if (!isset($data['delivery_location_id']) || (int)$data['delivery_location_id'] < 1) {
        $errors['delivery_location_id'] = ['message' => 'Wrong Warehouse!'];
      } else {
        $req['delivery_location_id'] = $data['delivery_location_id'];
      }
    } elseif ($data['type'] == 'delivery') {
      if (!isset($data['delivery_city_id']) || (int)$data['delivery_city_id'] < 1) {
        $errors['delivery_city_id'] = ['message' => 'Wrong City!'];
      } else {
        $req['delivery_city_id'] = $data['delivery_city_id'];
      }
      if (!isset($data['delivery_district_id']) || (int)$data['delivery_district_id'] < 1) {
        $errors['delivery_district_id'] = ['message' => 'Wrong District!'];
      } else {
        $req['delivery_district_id'] = $data['delivery_district_id'];
      }
      if (!isset($data['delivery_address']) || strlen($data['delivery_address']) < 1) {
        $errors['delivery_address'] = ['message' => 'Wrong Street!'];
      } else {
        $req['delivery_address'] = $data['delivery_address'];
      }
    }
    if (!isset($data['delivery_date']) || strlen($data['delivery_date']) < 1) {
      $errors['delivery_date'] = ['message' => 'Wrong Date!'];
    } else {
      $req['delivery_date'] = DateTime::createFromFormat('d/M/Y H:i', $data['delivery_date'])->format('Y-m-d H:i:s');
    }
    
    if (isset($data['google_latitude']) && strlen($data['google_latitude']) > 1) {
      $req['google_latitude'] = $data['google_latitude'];
    }
    
    if (isset($data['google_longitude']) && strlen($data['google_longitude']) > 1) {
      $req['google_longitude'] = $data['google_longitude'];
    }
    
    $app['errors'] = $errors;
    
    $req['payments'] = array(); //TODO: разкоментить что бы букинг создавался с инвойсом
    $req['descr'] = $data['comments'];
    $req['customer_contact_tel'] = $app['userData']['main_phone'];
    $req['customer_contact_email'] = $data['email'];
    $req['customer_id'] = $app['userData']['id'];
    $req['delivery_type'] = $data['type'];
    $req['company_bank_id'] = 3;
    $req['subtype_id'] = 5;
    $req['department_id'] = 7;
    $req['sales_id'] = 7047;
    $req['location_id'] = 44;
    $req['order'] = array();
    $req['order']['complexity_id'] = 1;
    $req['order']['origin_location_id'] = 9;
    $req['items'] = array();
    
    foreach ($carts['cart']['prices'] as $key => $item) {
      $itm['product_id'] = $item['price'];
      $itm['price_code'] = $item['code'];
      $itm['prior'] = 1;
      $itm['size_id'] = $item['size_id'];
      $itm['qty'] = $item['qty'];
      $itm['amount'] = $item['amount'];
      $itm['price'] = (float)($item['amount'] / (int)$item['qty']);
      $itm['unit'] = $item['unit'];
      $itm['descr'] = $item['details'];
      $itm['category_id'] = $item['category_id'];
      $itm['category_location_id'] = $item['category_location_id'];
      $req['items'][] = $itm;
    }
    /*return $app->json(['data' => $data, 'req' => $req, 'errors' => $errors]);*/
    
    if (empty($app['errors'])) {
      $res = $auth->createWebBooking($req);
      
      $auth->clearAllCart();
      if ($data['email']) {
        $auth->sendBookingReceivedEmail($res);
      }
    }
    return $app->json(['data' => $data, 'req' => $req, 'errors' => $errors]);
  })->bind('createWebBooking');
  
  $app->post('/createCustomBooking', function (Request $request) use ($app, $auth, $base) {
    $data = json_decode($request->getContent(), true);
    $req = [];
    $errors = [];
    
    if (!isset($data['type']) || strlen($data['type']) < 1) {
      $errors['delivery_type'] = ['message' => 'Wrong Delivery type!'];
    }
    
    if (!isset($data['full_name']) || strlen($data['full_name']) < 1) {
      $errors['name'] = ['message' => 'Empty Name!'];
    }
    
    if (!isset($data['email']) || strlen($data['email']) < 1) {
      $errors['email'] = ['message' => 'Wrong Email!'];
    } else {
      $req['customer_contact_email'] = $data['email'];
    }
    
    if (!isset($data['phone']) || strlen($data['phone']) < 1) {
      $errors['phone'] = ['message' => 'Wrong Phone!'];
    } else {
      $req['customer_contact_tel'] = $data['phone'];
    }
    
    if ($data['type'] == 'pickup') {
      if (!isset($data['delivery_location_id']) || (int)$data['delivery_location_id'] < 1) {
        $errors['delivery_location_id'] = ['message' => 'Wrong Warehouse!'];
      } else {
        $req['delivery_location_id'] = $data['delivery_location_id'];
      }
    } elseif ($data['type'] == 'delivery') {
      if (!isset($data['delivery_city']) || (int)$data['delivery_city'] < 1) {
        $errors['delivery_city'] = ['message' => 'Wrong City!'];
      } else {
        $req['delivery_city'] = $data['delivery_city'];
      }
      if (!isset($data['delivery_district_id']) || (int)$data['delivery_district_id'] < 1) {
        $errors['delivery_district_id'] = ['message' => 'Wrong District!'];
      } else {
        $req['delivery_district_id'] = $data['delivery_district_id'];
      }
      if (!isset($data['delivery_address']) || strlen($data['delivery_address']) < 1) {
        $errors['delivery_address'] = ['message' => 'Wrong Street!'];
      } else {
        $req['delivery_address'] = $data['delivery_address'];
      }
    }
    if (!isset($data['delivery_date']) || strlen($data['delivery_date']) < 1) {
      $errors['delivery_date'] = ['message' => 'Wrong Date!'];
    } else {
      $req['delivery_date'] = DateTime::createFromFormat('d/M/Y H:i', $data['delivery_date'])->format('Y-m-d H:i:s');
    }
    
    $app['errors'] = $errors;
    
    $req['payments'] = array(); //TODO: разкоментить что бы букинг создавался с инвойсом
    $req['descr'] = $data['comments'];
    $req['delivery_type'] = $data['type'];
    $req['company_full_name'] = $data['full_name'];
    $req['company'] = $data['full_name'];
    $req['acc_notes'] = $data['full_name'];
    $req['company_id'] = 1;
    $req['online'] = 1;
    $req['location_id'] = 44;
    $req['change'] = 0;
    
    $req['items'] = array();
    $custom = array(
      'qty' => 0,
      'category_id' => 228,
      'product_id' => 1906,
      'price' => 0,
      'descr' => 'none',
      'amount' => 0);
    $req['items'][] = $custom;
    
    $booking = array();
    if (empty($app['errors'])) {
      $res = $auth->createWebBooking($req);
      
      if ((int)$res > 0) {
        $booking = $auth->getBookingDetails($res);
      }
    }
    return $app->json(['data' => $data, 'req' => $req, 'booking' => $booking, 'errors' => $errors]);
  })->bind('createCustomBooking');
  
  $app->post('/uploadAttachToACM', function (Request $request) use ($app, $auth, $base) {
    $data = json_decode($request->getContent(), true);
    $req = [];
    $errors = [];
    $response = [];
    $sendMail = [];
    if ($data['curl_upload'] == 'order_price_quote') {
      $data_id = $data['order_id'];
      $imgmassive = $data['images'];
      foreach ($imgmassive as $upl_file) {
        $file_path = $app['path_img'] . '/custom_cake/' . $upl_file;
        $cfile = new CURLFile($file_path, mime_content_type($file_path));
        $req = array("data_id" => $data_id, "upl_file" => $cfile);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
        curl_setopt($ch, CURLOPT_URL, "https://awery.katrina.ae/system/uploads/cake_order_file.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        $response = curl_exec($ch);
        if ($data['curl_upload'] == 'order_price_quote') {
          unlink($app['path_img'] . '/custom_cake/' . $upl_file);
        }
        $response = json_encode($response);
        curl_close($ch);
      }
      if ($data['booking_id']) {
        $sendMail = $auth->sendBookingReceivedEmail($data['booking_id']);
      }
    }
    
    return $app->json(['data' => $data, 'req' => $req, 'errors' => $errors, 'response' => $response, 'sendMail' => $sendMail]);
  })->bind('uploadAttachToACM');
  
  $app->get('/booking', function () use ($app, $auth) {
    
    /*if (!isset($_COOKIE['CUSTID']))
        return $app->redirect('/');

    $booking = $auth->getUserBookings();

    if (isset($_GET['dd']) && $_GET['dd'] == 1) {
        print_r($booking);
        die();
    }

    return $app['twig']->render('booking/booking-list.twig', ['booking' => $booking]);*/
    return $app['twig']->render('booking/booking-list.twig', ['booking' => array()]);
  })->bind('getBooking');
  
  $app->get('/booking/{base_64}', function ($base_64) use ($app, $auth) {
    $id = base64_decode($base_64);
    
    $errors = [];
    
    $booking = $auth->getBookingDetails($id);
    if (!isset($booking['booking']['id'])) {
      $errors['data'] = ['message' => 'Wrong Booking Data!'];
    }
    
    $app['errors'] = $errors;
    
    if (!isset($booking['paid_amount']))
      $booking['paid_amount'] = 0;
    
    if (isset($booking['booking']['amount'])) {
      $booking['booking']['cost'] = $booking['booking']['amount'] - $booking['booking']['paid_amount'] - $booking['booking']['discount'];
      $booking['booking']['min_pay'] = $booking['booking']['cost'] * 0.3;
    }
    
    return $app['twig']->render('booking/booking-single.twig', ['details' => $booking]);
  })->bind('getBookingById');
  
  $app->match('/migs_request', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {
    $req = [];
    $req['amount'] = ($request->get('amount') * 100);
    $req['booking_id'] = $request->get('booking_id');
    $req['user_id'] = $request->get('user_id');
    
    if (!isset($req['amount']) && !isset($req['booking_id']))
      return $app->json(['error' => 1, 'message' => 'Error. Receive wrong information', 'debug' => $req]);
    if ($req['amount'] <= 0)
      return $app->json(array('error' => 2, 'message' => 'Invalid amount', 'debug' => $req));
    if ($req['booking_id'] <= 0)
      return $app->json(array('error' => 3, 'message' => 'Invalid booking id', 'debug' => $req));
    
    $secretHash = "8D6290AE9071AB8DC35C1C08CD9C077E";
    $accessCode = '13D16EDD';
    $merchantId = '600617';
    
    $data = array(
      'vpc_AccessCode' => $accessCode,
      'vpc_Amount' => $req['amount'],
      'vpc_Command' => 'pay',
      'vpc_Locale' => 'en',
      'vpc_MerchTxnRef' => 'REF_' . time(),
      'vpc_Merchant' => $merchantId,
      'vpc_OrderInfo' => $req['booking_id'],
      'vpc_ReturnURL' => 'https://katrina.ae/booking-result',
      'vpc_Version' => '1',
      'vpc_SecureHashType' => 'SHA256',
    );
    
    ksort($data);
    $hash = null;
    foreach ($data as $k => $v) {
      if (in_array($k, array('vpc_SecureHash', 'vpc_SecureHashType'))) {
        continue;
      }
      if ((strlen($v) > 0) && ((substr($k, 0, 4) == "vpc_") || (substr($k, 0, 5) == "user_"))) {
        $hash .= $k . "=" . $v . "&";
      }
    }
    $hash = rtrim($hash, "&");
    
    $secureHash = strtoupper(hash_hmac('SHA256', $hash, pack('H*', $secretHash)));
    $paraFinale = array_merge($data, array('vpc_SecureHash' => $secureHash));
    $actionurl = 'https://migs.mastercard.com.au/vpcpay?' . http_build_query($paraFinale);
    
    //  return $app->redirect($actionurl);
    
    return $app->json(['req' => $req, 'return_link' => $actionurl, 'error' => 0]);
  })->bind('MiGs_Payment');
  
  $app->match('/booking-result', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {
    $path = realpath(__DIR__ . '/../log/payment_migs.log');
    
    $file = fopen($path, 'a') or die('Cannot!');
    fwrite($file, "New Payment " . date('Y-m-d H:i:s') . "\n");
    fwrite($file, json_encode($_GET) . "\n");
    foreach ($_GET as $key => $resp) {
      if ($key == 'vpc_Amount' || $key == 'vpc_SecureHashType' || $key == 'user_SessionId' || $key == 'vpc_OrderInfo' || $key == 'vpc_TxnResponseCode' || $key == 'vpc_TransactionNo' || $key == 'vpc_ReceiptNo' || $key == 'vpc_MerchTxnRef')
        fwrite($file, $key . '=' . $resp . "\n");
    }
    
    ksort($_GET);
    $hash = null;
    foreach ($_GET as $k => $v) {
      if (in_array($k, array('vpc_SecureHash', 'vpc_SecureHashType'))) {
        continue;
      }
      if ((strlen($v) > 0) && ((substr($k, 0, 4) == "vpc_") || (substr($k, 0, 5) == "user_"))) {
        $hash .= $k . "=" . $v . "&";
      }
    }
    $hash = rtrim($hash, "&");
    
    $secureHash = strtoupper(hash_hmac('SHA256', $hash, pack('H*', '8D6290AE9071AB8DC35C1C08CD9C077E')));
    
    if (isset($_GET['vpc_SecureHash']) && isset($_GET['vpc_TxnResponseCode'])) {
      if (($secureHash == $_GET['vpc_SecureHash']) && ($_GET['vpc_TxnResponseCode'] == '0')) {
        $req = array(
          'booking_id' => (int)$_GET['vpc_OrderInfo'],
          'payment_type_id' => 4,
          'payment_sub_type_id' => 75,
          'company_bank_id' => 259,
          'payment_doc_no' => null, //card
          'web_payment_reference' => $_GET['vpc_TransactionNo'], //auth code
          'payment' => floatval($_GET['vpc_Amount'] / 100),
          'tax_option_id' => 6,
          'payment_notes' => 'Payment on booking: ' . $_GET['vpc_OrderInfo'] . ' Online transaction reference number: ' . $_GET['vpc_MerchTxnRef'] . ' BatchNo: ' . $_GET['vpc_BatchNo'] . ' Amount: ' . floatval($_GET['vpc_Amount'] / 100)
        );
        $res = JsonRPC::execute('External_ProductBooking.createCakePayment', array($req));
        fwrite($file, json_encode($req) . "\n");
        fwrite($file, 'External_ProductBooking.createCakePayment ' . json_encode($res) . "\n\n");
        fclose($file);
        header('Location: https://katrina.ae/booking?pay=success');
      } else {
        fwrite($file, "Wrong Hash!!!\n");
        fclose($file);
        header('Location: https://katrina.ae/booking?pay=error');
      }
    } else {
      fwrite($file, "Wrong Hash 2!!!\n");
      fclose($file);
      header('Location: https://katrina.ae/booking?pay=error');
    }
    
    die();
  })->bind('booking-result');
  
  $app->post('/changeLang', function (Request $request) use ($app, $base) {
    $lang = $request->get('lang');
    
    $sql = 'SELECT * FROM `languages` WHERE `is_deleted` != 1 AND `code` = ?';
    $lang = $app['db']->fetchAssoc($sql, array($lang));
    
    if ($lang) {
      $app['session']->set('language', $lang['code']);
      $app['translations'] = $base->getTranslations($lang['code']);
      $app['lang'] = $lang['name'];
    }
    
    return $app->json(['data' => $app['translations'], 'lang' => $lang]);
  })->bind('changeLang');
  
  $app->get('/news', function () use ($app, $base) {
    $page = 1;
    $perpage = 4;
    if (isset($_GET['page']) && $_GET['page'] > 1) {
      $page = $_GET['page'];
    }
    $limits = 'LIMIT ' . (($page - 1) * $perpage) . ', ' . $perpage;
    $res = $base->getAllNews($limits);
    $numPagPage = ceil((int)$res['count'] / (int)$perpage);
    $paginations = array(
      'current' => $page,
      'list' => array(),
      'count' => $numPagPage
    );
    
    $paginations['list'] = $base->getPaginations($page, $numPagPage);
    
    return $app['twig']->render('pages/news.twig', ['newsList' => $res['news'], 'paginations' => $paginations]);
  });
  
  $app->match('/sitemap/generation', function () use ($app, $base) {
    
    /*define('BASE_URL', 'https://newkatrinasite.awery.com');*/
    define('BASE_URL', 'https://katrina.ae');
    
    if (file_exists(__DIR__ . '/sitemap.xml')) unlink(__DIR__ . '/sitemap.xml');
    
    
    $sql = 'SELECT * FROM `news` WHERE `is_deleted` != 1 AND `is_active` = 1 ORDER BY `published` DESC';
    $news = $app['db']->fetchAll($sql);
    
    $result = '';
    $result .= '<?xml version=\'1.0\' encoding=\'UTF-8\'?>' . PHP_EOL;
    $result .= '<urlset xmlns=\'http://www.sitemaps.org/schemas/sitemap/0.9\'>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '</loc>' . PHP_EOL;
    $result .= '<changefreq>monthly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/create-your-cake</loc>' . PHP_EOL;
    $result .= '<changefreq>monthly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/franchise</loc>' . PHP_EOL;
    $result .= '<changefreq>monthly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/companyprofile</loc>' . PHP_EOL;
    $result .= '<changefreq>monthly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/clients</loc>' . PHP_EOL;
    $result .= '<changefreq>monthly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/faq</loc>' . PHP_EOL;
    $result .= '<changefreq>monthly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/terms-conditions</loc>' . PHP_EOL;
    $result .= '<changefreq>monthly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/privacy-policy</loc>' . PHP_EOL;
    $result .= '<changefreq>yearly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/delivery</loc>' . PHP_EOL;
    $result .= '<changefreq>yearly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/loyalty-program</loc>' . PHP_EOL;
    $result .= '<changefreq>yearly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    $result .= '<url>' . PHP_EOL;
    $result .= '<loc>' . BASE_URL . '/news</loc>' . PHP_EOL;
    $result .= '<changefreq>yearly</changefreq>' . PHP_EOL;
    $result .= '<priority>1.00</priority>' . PHP_EOL;
    $result .= '</url>' . PHP_EOL;
    
    foreach ($app['categories'] as $category) {
      $result .= '<url>' . PHP_EOL;
      $result .= '<loc>' . BASE_URL . '/prices/' . $category['id'];
      $result .= '</loc>' . PHP_EOL;
      $result .= '<changefreq>yearly</changefreq>' . PHP_EOL;
      $result .= '<priority>1.00</priority>' . PHP_EOL;
      $result .= '</url>' . PHP_EOL;
      $products = $base->getSiteProducts(array("site_category_id" => $category['id']));
      
      foreach ($products as $product) {
        $result .= '<url>' . PHP_EOL;
        $result .= '<loc>' . BASE_URL . '/single/' . $product['id'];
        $result .= '</loc>' . PHP_EOL;
        $result .= '<changefreq>yearly</changefreq>' . PHP_EOL;
        $result .= '<priority>1.00</priority>' . PHP_EOL;
        $result .= '</url>' . PHP_EOL;
      }
    }
    
    foreach ($news as $item) {
      $result .= '<url>' . PHP_EOL;
      $result .= '<loc>';
      $result .= BASE_URL . '/news/' . $item['slug'];
      $result .= '</loc>' . PHP_EOL;
      $result .= '<changefreq>monthly</changefreq>' . PHP_EOL;
      $result .= '<priority>0.9</priority>' . PHP_EOL;
      $result .= '</url>' . PHP_EOL;
    }
    
    $result .= '</urlset>';
    file_put_contents((__DIR__ . '/sitemap/sitemap.xml'), $result);
    
    return $app->json(array(
      'error' => 0,
      'message' => 'Success',
      'file_exist' => file_exists(__DIR__ . '/sitemap/sitemap.xml'),
      'path' => __DIR__ . '/sitemap/sitemap.xml',
    ));
  })->bind('generationSitemap');
  
  $app->get('/news/{slug}', function ($slug) use ($app, $base) {
    $news = $base->getNewsBySlug($slug);
    
    return $app['twig']->render('pages/news-single.twig', array('news' => $news));
  });
  
  $app->get('/franchise', function () use ($app, $base) {
    $page = $base->getPageBySlug('franchise');
    
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/franchise/', function () use ($app, $base) {
    $page = $base->getPageBySlug('franchise');
    
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/companyprofile', function () use ($app, $base) {
    $page = $base->getPageBySlug('companyprofile');
    
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/clients', function () use ($app, $base) {
    $page = $base->getPageBySlug('clients');
    
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/faq', function () use ($app, $base) {
    $page = $base->getPageBySlug('faq');
    
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/404', function () use ($app, $base) {
    return $app['twig']->render('pages/404.twig');
  });
  
  $app->get('/salesb2b', function () use ($app, $base) {
    return $app['twig']->render('pages/404.twig');
  });
  
  $app->get('/booking-feedback/{id}', function ($id, Symfony\Component\HttpFoundation\Request $request) use ($app, $base) {
    return $app['twig']->render('pages/feedback.twig', array(
        'id' => base64_decode($id)
    ));
  });
  
  $app->get('/terms-conditions', function () use ($app, $base) {
    $page = $base->getPageBySlug('terms-conditions');
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/terms-conditions/', function () use ($app, $base) {
    $page = $base->getPageBySlug('terms-conditions');
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/privacy-policy', function () use ($app, $base) {
    $page = $base->getPageBySlug('privacy-policy');
    
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/delivery', function () use ($app, $base) {
    $page = $base->getPageBySlug('delivery');
    
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/delivery/', function () use ($app, $base) {
    $page = $base->getPageBySlug('delivery');
    
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/loyalty-program', function () use ($app, $base) {
    $page = $base->getPageBySlug('loyalty-program');
    
    return $app['twig']->render('pages/default-page.twig', array('page' => $page));
  });
  
  $app->get('/contact', function () use ($app, $base) {
    return $app['twig']->render('pages/contacts.twig');
  });
  
  $app->get('/contact/', function () use ($app, $base) {
    return $app['twig']->render('pages/contacts.twig');
  });
  
  $app->get('/user-profile', function () use ($app, $base) {
    if (!isset($app['userData']['id']) || (int)$app['userData']['id'] < 1)
      return $app->redirect('/');
    
    return $app['twig']->render('pages/user-profile.twig');
  });
  
  $app->post('/updateCustomer', function (Request $request) use ($app, $auth) {
    $data = json_decode($request->getContent(), true);
    if (!isset($app['userData']['id']) || (int)$app['userData']['id'] < 1)
      return $app->redirect('/');
    
    $errors = array();
    
    if (!isset($data['name']) || strlen($data['name']) < 1) {
      $errors['name'] = ['message' => 'Empty Name!'];
    }
    
    if (empty($app['errors'])) {
      $res = $auth->updateCustomer(array('company' => $data['name']));
    }
    
    return $app->json(['data' => $data, 'req' => $res, 'errors' => $errors]);
  });
  
  $app->get('/facebook/callback/register', function () use ($app) {
    
    try {
      $accessToken = $app['helper']->getAccessToken();
    } catch (\Facebook\Exceptions\FacebookResponseException $e) {
      echo 'Response Exception: ' . $e->getMessage();
      exit;
    } catch (\Facebook\Exceptions\FacebookSDKException $e) {
      echo 'SDK Exception : ' . $e->getMessage();
      exit;
    }
    
    $oAuth2Client = $app['fb']->getOAuth2Client();
    $app['session']->set('accessToken', (string)json_encode($accessToken));
    if (isset($accessToken)) {
      // Logged in!
      $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
      $response = $app['fb']->get('/me?fields=id,first_name,email,last_name', (string)$accessToken);
      $fb_user_data = $response->getGraphNode()->asArray();
      $app['fbLogin']->addInfo('fb_accessToken: ' . $accessToken);
      $app['fbLogin']->addInfo('fb_user_data: ' . json_encode($fb_user_data));
      $app['session']->set('fb_accessToken', (string)$accessToken);
      $app['session']->set('fb_userData', $fb_user_data);
    } elseif ($app['helper']->getError()) {
      $returnUrl = '/';
      return $app->redirect($returnUrl);
    }
    
    $returnUrl = '/';
    if (isset($_COOKIE['returnFbUrl']) && !empty($_COOKIE['returnFbUrl'])) {
      $returnUrl = $_COOKIE['returnFbUrl'];
    }
    
    return $app->redirect($returnUrl . '?phone_form=1');
  })->bind('FacebookCallbackRegister');
  
  $app->match('/facebook/callback/login', function (Request $request) use ($app, $auth) {
    /*$data = json_decode($request->getContent(), true);
    $app['fbLogin']->addInfo('asfasfasf');
    $errors = [];
    $access_code = $_GET['code'];
    var_dump($_GET['code']);
    if (isset($_COOKIE['CUSTID'])) {
        $errors['login'] = 'You are login already!';
    }

    if (empty($errors)) {
        $ch = curl_init();
        $endpoint = 'https://graph.facebook.com/debug_token';
        $params = array('input_token' => '', 'access_token' => $access_code);
        $url = $endpoint . '?' . http_build_query($params);
        $url = 'https://graph.facebook.com/v8.0/oauth/access_token?client_id=100667310561658&redirect_uri=https://newkatrinasite.awery.com/facebook/callback/login&client_secret=09de7052f06c3348ad558e8694b385dd&code='.$access_code;
        $access_token = json_decode(file_get_contents($url), true)['access_token'];
        $app['fbLogin']->addInfo($url);

        $app['fbLogin']->addInfo('access_token: ' . $access_token);
        $endpoint = 'https://graph.facebook.com/debug_token';
        $params = array('input_token' => $access_token, 'access_token' => $access_token);

        $url = $endpoint . '?' . http_build_query($params);
        $verified_token = json_decode(file_get_contents($url), true);

        $app['fbLogin']->addInfo('Token verification URL: ' . $url);
        $app['fbLogin']->addInfo('Token validation response: ' . json_encode($verified_token));
        if(isset($verified_token['data']['user_id'])){
            $endpoint = 'https://graph.facebook.com/'.$verified_token['data']['user_id'];
            $params = array('fields' => 'id,name,email', 'access_token' => $access_token);
            $url = $endpoint . '?' . http_build_query($params);
            $user_data = json_decode(file_get_contents($url), true);
            $app['fbLogin']->addInfo('Get user data URL: ' . $url);
            $app['fbLogin']->addInfo('User data response: ' . json_encode($user_data));
        }

    }*/
    
    $errors = [];
    
    if (isset($_COOKIE['CUSTID'])) {
      $errors['login'] = 'You are login already!';
    }
    
    if (empty($errors)) {
      try {
        $accessToken = $app['helper']->getAccessToken();
      } catch (\Facebook\Exceptions\FacebookResponseException $e) {
        echo 'Response Exception: ' . $e->getMessage();
        exit;
      } catch (\Facebook\Exceptions\FacebookSDKException $e) {
        echo 'SDK Exception : ' . $e->getMessage();
        exit;
      }
      $oAuth2Client = $app['fb']->getOAuth2Client();
      $app['fbLogin']->addInfo('oAuth2Client: ' . json_encode($oAuth2Client));
      
      if (isset($accessToken)) {
        // Logged in!
        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
        $response = $app['fb']->get('/me?fields=id,first_name,email,last_name', (string)$accessToken);
        $fb_user_data = $response->getGraphNode()->asArray();
        $app['fbLogin']->addInfo('fb_user_data: ' . json_encode($fb_user_data));
        
        $app['session']->set('fb_accessToken', (string)$accessToken);
        $app['session']->set('fb_userData', $fb_user_data);
      } elseif ($app['helper']->getError()) {
        $returnUrl = '/';
        return $app->redirect($returnUrl);
      }
    }
    
    $returnUrl = '/';
    if (isset($_COOKIE['returnFbUrl']) && !empty($_COOKIE['returnFbUrl'])) {
      $returnUrl = $_COOKIE['returnFbUrl'];
      setcookie('returnFbUrl', '', time() - 36000, '/');
    }
    
    if (!$app['session']->has('fb_accessToken') || !$app['session']->has('fb_userData')) {
      $errors['fb'] = 'Unknown Facebook user!';
    }
    $app['fbLogin']->addInfo(json_encode($errors));
    
    if (empty($errors)) {
      
      $login = $auth->getCustomerBySocial(array('facebook', $app['session']->get('fb_accessToken')));
      
      $app['fbLogin']->addInfo('$login$login$login! ' . $login);
      
      if (!isset($login['id']) || (int)$login['id'] <= 0) {
        $errors['login'] = $login;
        $errors['register'] = 'Unknown user!';
      }
    }
    $app['fbLogin']->addInfo($returnUrl);
    $app['fbLogin']->addInfo(json_encode($errors));
    if (!empty($errors)) {
      $returnUrl .= '?errors=' . json_encode($errors);
    }
    
    return $app->redirect($returnUrl);
  })->bind('FacebookCallbackLogin');
  
  $app->post('/fb-register', function (Request $request) use ($app, $auth) {
    $data = json_decode($request->getContent(), true);
    
    $errors = [];
    $app['fbLogin']->addInfo('has fb_accessToken: ' . json_encode($app['session']->has('fb_accessToken')));
    $app['fbLogin']->addInfo('has fb_userData: ' . json_encode($app['session']->has('fb_userData')));
    
    if (!$app['session']->has('fb_accessToken') || !$app['session']->has('fb_userData')) {
      $errors['fb'] = 'Unknown Facebook user!';
    }
    if (isset($_COOKIE['CUSTID'])) {
      $errors['login'] = 'You are login already!';
    }
    
    $exist_token = $auth->getCustomerBySocial(array('facebook', $app['session']->get('fb_accessToken')));
    if (is_array($exist_token) && !empty($exist_token)) {
      $errors['fb_login'] = 'Facebook user already exist!';
    }
    
    $data['main_phone'] = str_replace(array(' ', '-'), '', $data['main_phone']);
    if (!isset($data['main_phone']) || strlen($data['main_phone']) < 13)
      $errors['phone'] = 'Empty phone!';
    if (!empty($data['main_phone'][0]) && $data['main_phone'][0] != '+')
      $errors['phone'] = 'Wrong format phone !';
    
    if (empty($errors)) {
      $fb_userData = $app['session']->get('fb_userData');
      
      $req = [];
      if (isset($fb_userData['email']))
        $req['main_email'] = $fb_userData['email'];
      if (isset($fb_userData['first_name']) && isset($fb_userData['last_name']))
        $req['company'] = $fb_userData['first_name'] . ' ' . $fb_userData['last_name'];
      $req['main_phone'] = $data['main_phone'];
      
      $res = $auth->signUp($req, 'fb');
      
      if (!isset($res['res'])) {
        $errors['register'] = $res;
      } else {
        $app['userData'] = $auth->getCustomerData();
        $socialReq = array($res['login']['id'], 'facebook', $app['session']->get('fb_accessToken'), $app['config']['facebook_app_credentials']['app_id'], $app['config']['facebook_app_credentials']['app_secret']);
        
        $app['fbLogin']->addInfo('REQUEST data External_Customers.registerSocial: ' . json_encode($socialReq));
        $social = $auth->registerSocial($socialReq);
        $app['fbLogin']->addInfo('RESPONSE data External_Customers.registerSocial: ' . json_encode($social));
        
        if ($social != 1) {
          $errors['registerSocial'] = $social;
        }
        
        if (isset($_COOKIE['returnFbUrl'])) {
          setcookie('returnFbUrl', '', time() - 36000, '/');
        }
      }
    }
    
    return $app->json(['data' => $data, 'errors' => $errors]);
  })->bind('fbRegister');
  
  $app->match('/upload', function (Request $request) use ($app) {
    $file = $request->files->get('file');
    $type = $request->get('type');
    if ($file) {
      $fn = $file->getClientOriginalName();
      $str = explode(".", $fn);
      $ext = strtolower($str[count($str) - 1]);
      if (!in_array($ext, array('ai', 'eps', 'svg', 'png', 'psd', 'pdf', 'jpg', 'jpeg')))
        return $app->json(array(
          'error' => 1,
          'message' => 'Wrong Attach format!',
        ));
      
      $type != 'custom_cake' ? $directory = date('Y') . '/' . date('m') : $directory = '';
      $directory_full = $app['path_img'] . '/' . $type . '/' . $directory;
      
      if (!is_dir($directory_full))
        mkdir($directory_full, 0755, true);
      $random = uniqid(null, true);
      $file_name = substr($random, 0, 13) . substr($random, 15);
      $file->move($directory_full, $file_name . '.' . $ext);
    } else {
      return $app->json(array(
        'error' => 2,
        'message' => 'Please upload image',
        'data_list' => array(),
      ));
    }
    
    return $app->json(array(
      'error' => 0,
      'message' => 'File uploaded successfully',
      'data_list' => array(
        'filepath' => $directory . '/' . $file_name . '.' . $ext,
        'filename' => $file_name . '.' . $ext,
        'type' => $type,
      ),
    ));
  })->bind('uploadAttach');
  
  $app->before(function (Request $request) use ($app, $base, $auth) {
    $app['request'] = $request;
    $islogged = JsonRPC::loginRPC('decoration');
    if (!isset($islogged['user_data']) || empty($islogged['user_data']) || !isset($islogged['user_data']['id'])) {
      $log = JsonRPC::loginRPC('login_external', $app['config']['External_Udb']);
    }
    
    
    $auth->setUserIdentifer();
    
    /*if($app['session']->has('categories'))
        $app['categories'] = $app['session']->get('categories');
    else{
        $app['categories'] = $base->getSiteCategories();
        $app['session']->set('categories', $app['categories']);
    }*/
    
    $cat_path = __DIR__ . '/cache/categories.json';
    
//    if (file_exists($cat_path)) {
//      $app['categories'] = json_decode(file_get_contents($cat_path), true);
//    } else {
      $app['categories'] = $base->getSiteCategories();
      $directory_full = __DIR__ . '/cache';
      if (!is_dir($directory_full))
        mkdir($directory_full, 0755, true);
      fopen($directory_full . "/categories.json", "w");
      file_put_contents($directory_full . '/categories.json', json_encode($app['categories']));
//    }
    
    /*$app['prod'] =  JsonRPC::execute('External_ProductBooking.getSiteProducts', array(array("site_category_id" => 10)));
    echo '<pre>';
    var_dump($app['prod']);
    die();*/
    if ($request->getMethod() == 'OPTIONS') {
      return $app->json(array(
        'error' => 0,
        'message' => 'Preflight request success'
      ));
    }
    
    $app['name'] = 'Katrina';
    $app['title'] = 'Katrina Sweets & Confectionary, Bakery. Best flavor cakes, pastries and bread in proudly made in U.A.E.';
    $app['description'] = 'Katrina Sweets & Confectionary, Bakery. Best flavor cakes, pastries and bread in proudly made in U.A.E.';
    
    $items = $base->getUserCart();
    
    $app['carts'] = $items;
    
    if ($app['session']->has('languages'))
      $app['languages'] = $app['session']->get('languages');
    else {
      $app['languages'] = $base->getLanguages();
      $app['session']->set('languages', $app['languages']);
    }
    
    $file = file_get_contents('translations/en.json');
    $app['translations'] = json_decode($file, TRUE);
    $app['lang'] = 'en';
    if ($app['session']->has('language')) {
      /*$translations = $base->getTranslations($app['session']->get('language'));
      $app['translations'] = $translations['trans'];
      $app['lang'] = $translations['lang'];*/
      $app['menu'] = $base->generateMenu($app['session']->get('language'));
    } else {
      /* $translations = $base->getTranslations('en');
       $app['translations'] = $translations['trans'];
       $app['lang'] = $translations['lang'];*/
      $app['session']->set('lang', 'en');
      $app['menu'] = $base->generateMenu('en');
    }
    
    $app['errors'] = array();
    if (isset($_COOKIE['CUSTID'])) {
      $app['userData'] = $auth->getCustomerData();
      if ((int)$app['userData']['deleted'] == 1) {
        $app['session']->remove('CUSTID');
        setcookie('CUSTID', '', time() - 36000, '/');
      }
    }
    
    if (strpos($request->getPathInfo(), 'facebook') !== false) {
      $app['fb'] = new \Facebook\Facebook([
        'app_id' => $app['config']['facebook_app_credentials']['app_id'],
        'app_secret' => $app['config']['facebook_app_credentials']['app_secret'],
        'default_graph_version' => 'v8.0'
      ]);
      $app['helper'] = $app['fb']->getRedirectLoginHelper();
      if (isset($_GET['state'])) {
        $app['helper']->getPersistentDataHandler()->set('state', $_GET['state']);
      }
    }
  });
  
  $app->run();
