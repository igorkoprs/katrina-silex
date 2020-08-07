<?php

namespace Base;


class Auth extends Base
{

    public function __construct($app)
    {
        parent::__construct($app);
    }

    public function signUp($data = array(), $type = '')
    {
        $data['company_id'] = 1;
        $data['pin_code'] = substr(rand(), 0, 4);
        $res = JsonRPC::execute('updateCustomer', array($data));

        if ($res && strrpos($res, 'already used for other customer!') === false) {
            $req['phone'] = $data['main_phone'];
            $req['pin'] = $data['pin_code'];
            $var = $this->signIn($req);
            if($type == 'fb')
                return array('res' => true, 'login' =>  $var);
            else
                return true;
        }

        return $res;
    }

    public function updateCustomer($data = array())
    {
        $data['id'] = $this->app['userData']['id'];

        $res = JsonRPC::execute('updateCustomer', array($data));

        if ($res == $data['id']) {
            $req['phone'] = $this->app['userData']['main_phone'];
            $req['pin'] = $this->app['userData']['pin_code'];
            $this->logout();
            $this->signIn($req);

            return true;
        }

        return false;
    }

    public function resetCustomerPinCode($phone)
    {
        return JsonRPC::execute('resetCustomerPinCode', array($phone));
    }

    public function signIn($data)
    {
        $req[0] = $data['phone'];
        $req[1] = $data['pin'];
        $res = JsonRPC::execute('login', $req);

        if (!isset($res['data_list']['id']))
            return false;

        return $res['data_list'];
    }

    public function isLogged()
    {
        $res = JsonRPC::execute('is_logged', array(array()));

        return $res;
    }

    public function logout()
    {
        $res = JsonRPC::execute('logout', array(array()));

        return $res;
    }

    public function getCustomerData()
    {
        $res = JsonRPC::execute('getCustomerData', array(array()));

        return $res;
    }

    public function getUserBookings()
    {
        $data = [
            'perpage' => 100,
            'order' => 'id',
            'from_date' => date('Y-m-d', strtotime('- 2 year')),
            'to_date' => date('Y-m-d', strtotime('+ 2 year')),
        ];

        $res = JsonRPC::execute('posBookings', array($data));

        if ($res) {
            foreach ($res as &$item) {
                $item['base_64'] = base64_encode($item['id']);
            }
            unset($item);
        }

        return $res;
    }

    public function getBookingDetails($booking_id = null)
    {
        $res = JsonRPC::execute('getBookingDetails', array($booking_id));

        return $res;
    }

    public function setUserIdentifer()
    {
        if (!$this->app['session']->has('user_id')) {
            if (!isset($_COOKIE['identifer'])) {
                $this->app['session']->set('user_id', 0);
                $this->app['user'] = null;
            } else {
                $sql = "SELECT * FROM `users` WHERE `identifer` = ?";
                $user = $this->db->fetchAssoc($sql, array($_COOKIE['identifer']));
                if ($user) {
                    $this->db->update('users', array('last_login' => date('Y-m-d H:i:s')), array('id' => $user['id']));
                    $this->app['session']->set('user_id', $user['id']);
                    $this->app['user'] = $user;
                }
            }
        }

        if ($this->app['session']->get('user_id') > 0) {
            $sql = "SELECT * FROM `users` WHERE `id` = ?";
            $user = $this->db->fetchAssoc($sql, array($this->app['session']->get('user_id')));
            if ($user) {
                $this->db->update('users', array('last_login' => date('Y-m-d H:i:s')), array('id' => $user['id']));
            } else {
                $identifer = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME_FLOAT']);
                $this->db->insert('users', array('identifer' => $identifer, 'last_login' => date('Y-m-d H:i:s')));
                $uid = $this->db->lastInsertId();
                $this->app['session']->set('user_id', $uid);

                $sql = "SELECT * FROM `users` WHERE `id` = ?";
                $user = $this->db->fetchAssoc($sql, array($this->app['session']->get('user_id')));
            }
        } else {
            $identifer = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME_FLOAT']);
            $this->db->insert('users', array('identifer' => $identifer, 'last_login' => date('Y-m-d H:i:s')));
            $uid = $this->db->lastInsertId();
            $this->app['session']->set('user_id', $uid);

            $sql = "SELECT * FROM `users` WHERE `id` = ?";
            $user = $this->db->fetchAssoc($sql, array($this->app['session']->get('user_id')));

            $this->app['session']->set('user_id', $user['id']);
        }
        $this->app['user'] = $user;

        if (!isset($_COOKIE['identifer']) || (isset($_COOKIE['identifer']) && $this->app['user']['id'] != $_COOKIE['identifer'])) {
            setcookie('identifer', $this->app['user']['identifer'], strtotime('+100 days'), '/');
        }

    }

    /**
     * @param array $data
     * @return mixed
     */
    public function createWebBooking($data = array())
    {
        $res = JsonRPC::execute('createWebBooking', array($data));

        return $res;
    }

    public function clearAllCart()
    {
        $sql = "SELECT * FROM `custom_cakes` WHERE `id_user` = ?";
        $cakes = $this->db->fetchAll($sql, array($this->app['user']['id']));
        foreach ($cakes as $cake) {
            $this->db->delete('cake_prices', array('id_cc' => $cake['id']));
            $this->db->delete('custom_cakes', array('id' => $cake['id']));
        }

        $res = $this->db->delete('prices', array('id_user' => $this->app['user']['id']));

        return $res;
    }

    public function sendBookingReceivedEmail($booking_id)
    {
        $res = JsonRPC::execute('sendBookingReceivedEmail', array($booking_id));

        return $res;
    }

    public function registerSocial($data = array())
    {
        return $res = JsonRPC::execute('registerSocial', $data);
    }

    public function getCustomerBySocial($data = array())
    {
        return $res = JsonRPC::execute('getCustomerIdBySocial', $data);
    }

}