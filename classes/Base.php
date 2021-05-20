<?php

namespace Base;

class Base
{
    public $breadcrumbs;
    public $all_categories;
    protected $db;
    protected $app;
    protected $json_rpc;

    CONST perPage = 1;

    public function __construct($app)
    {
        $this->app = $app;
        $this->db = $app['db'];
        $this->breadcrumbs = array();
    }

    public function generateMenu($lang)
    {

        $sql = 'SELECT `p`.`id`, `tp`.`name`, `p`.`slug`, `p`.`pid`, `p`.`is_special` FROM `pages` AS `p`
                LEFT JOIN `languages` as `lang` on `lang`.`code` = ?
                LEFT JOIN `translations_pages` as `tp` on `tp`.`id_page` = `p`.`id` AND `tp`.`id_lang` = `lang`.`id`
                WHERE `p`.`is_deleted` != 1 AND  `p`.`is_show` = 1 ORDER BY `prior` ASC';
        $menu = $this->db->fetchAll($sql, array($lang));

        $result = $this->getTree($menu, 0);
        /*echo '<pre>';
        var_dump($result);
        die();*/
        return $result;
    }

    public function getPageBySlug($page_slug)
    {
        $sql = "SELECT `p`.`id`, `tp`.`content` as `content` FROM `pages` as `p`   
                LEFT JOIN `translations_pages` as `tp` on `tp`.`id_page` = `p`.`id`
                LEFT JOIN `languages` as `lang` on `lang`.`id` = `tp`.`id_lang`
                WHERE `lang`.`code` = ? AND `p`.`slug` = ? AND `p`.`is_show` = 1 AND `p`.`is_deleted` = 0";
        //$page = $this->app['db']->fetchAssoc($sql, array(strtolower($this->app['lang']), $page_slug));
        $page = $this->app['db']->fetchAssoc($sql, array('en', $page_slug));

        return $page;
    }

    public function getAllNews($limits)
    {
        $sql = "SELECT `n`.`id` as `id_news`,`n`.`image`, `tp`.`content` as `content`, `tp`.`name` as `title`, `n`.`published`, `n`.`slug` FROM `news` as `n`   
                LEFT JOIN `translations_pages` as `tp` on `tp`.`id_news` = `n`.`id`
                LEFT JOIN `languages` as `lang` on `lang`.`id` = `tp`.`id_lang`
                WHERE `lang`.`code` = ? AND `n`.`is_active` = 1 AND `n`.`is_deleted` = 0 ORDER BY `n`.`published` DESC $limits";
        //$news = $this->app['db']->fetchAll($sql, array(strtolower($this->app['lang'])));
        $news = $this->app['db']->fetchAll($sql, array('en'));

        foreach ($news as &$n) {
            $n['published'] = date("j F Y", strtotime($n['published']));
            $n['short_descr'] = mb_strimwidth(strip_tags($n['content']), 0, 350, "...");
        }unset($n);

        $sql = "SELECT COUNT(*) as `count` FROM `news` as `n`   
                LEFT JOIN `translations_pages` as `tp` on `tp`.`id_news` = `n`.`id`
                LEFT JOIN `languages` as `lang` on `lang`.`id` = `tp`.`id_lang`
                WHERE `lang`.`code` = ? AND `n`.`is_active` = 1 AND `n`.`is_deleted` = 0";
        //$count = $this->app['db']->fetchAssoc($sql, array(strtolower($this->app['lang'])));
        $count = $this->app['db']->fetchAssoc($sql, array('en'));

        return array('news' => $news, 'count' => $count['count']);
    }

    public function getLatestNews($id)
    {
        $sql = 'SELECT * FROM `news`WHERE `is_deleted` != 1 AND `is_active` = 1 AND `id` != ? AND `published` >= ? AND `published` < ? LIMIT 5';
        $news_latest = $this->db->fetchAll($sql, array($id, date('Y-m-d', strtotime("-30 days")), date('Y-m-d', time())));

        foreach ($news_latest as &$n) {
            $n['published'] = date("F j Y", strtotime($n['published']));
        }
        unset($n);

        return $news_latest;
    }

    public function getMainPageNews()
    {
        $sql = 'SELECT * FROM `news` WHERE `is_deleted` != 1 AND `is_active` = 1 LIMIT 3';
        $news = $this->db->fetchAll($sql, array());

        foreach ($news as &$n) {
            $n['published'] = date("F j, Y", strtotime($n['published']));
        }
        unset($n);

        $this->addBreadCrumbs('News', '/news/');

        return $news;
    }

    public function getNewsBySlug($news_slug)
    {
        $sql = "SELECT `n`.`id` as `id_news`,`n`.`image`, `tp`.`content` as `content`, `tp`.`name` as `title`, `n`.`published`, `n`.`slug` FROM `news` as `n`   
                LEFT JOIN `translations_pages` as `tp` on `tp`.`id_news` = `n`.`id`
                LEFT JOIN `languages` as `lang` on `lang`.`id` = `tp`.`id_lang`
                WHERE `lang`.`code` = ? AND `n`.`slug` = ? AND `n`.`is_active` = 1 AND `n`.`is_deleted` = 0";
        $news = $this->app['db']->fetchAssoc($sql, array(strtolower($this->app['lang']), $news_slug));

        if ($news)
            $news['published'] = date("j F Y", strtotime($news['published']));

        return $news;
    }

    public function getTranslations($lang)
    {
        $sql = 'SELECT * FROM `languages` WHERE `is_deleted` != 1 AND `code` = ?';
        $language = $this->db->fetchAssoc($sql, array($lang));
        $word_translations = [];
        if ($lang) {
            $sql = 'SELECT `translation`, `id_word` FROM `translations` WHERE `id_lang` = ?';
            $translations = $this->db->fetchAll($sql, array($language['id']));
            if ($translations) {
                $sql = 'SELECT * FROM `translation_words`';
                $words = $this->db->fetchAll($sql, array($language['id']));

                if ($words) {
                    foreach ($words as &$word) {
                        $word_translations[$word['slug']] = '';
                        foreach ($translations as &$trans) {
                            if ($trans['id_word'] == $word['id']) {
                                $word_translations[$word['slug']] = $trans['translation'];
                            }
                        }
                        unset($trans);
                    }
                    unset($word);
                }
            }
        }

        return array('trans' => $word_translations, 'lang' => $language['name']);
    }

    public function getLanguages()
    {
        $sql = 'SELECT * FROM `languages` WHERE `is_deleted` != 1';
        $languages = $this->db->fetchAll($sql, array());

        return $languages;
    }

    public function getPropertyMainContent()
    {
        $sql = 'SELECT * FROM `pages` WHERE `is_deleted` != 1';
        $main = $this->db->fetchAll($sql, array('all'));

        return $main;
    }


    private function findCategory($search = '', $field = 'descr')
    {
        foreach ($this->all_categories as $cat) {
            if (trim($cat[$field]) == trim($search)) {
                return $cat;
            }
        }

        return false;
    }

    public function addBreadCrumbs($name, $url, $category = '')
    {
        $this->breadcrumbs[] = array('name' => $name, 'url' => $url, 'category' => $category);
    }

    public function getWarehouses()
    {
        $res = JsonRPC::execute('External_ProductBooking.getWarehouses', array('all'));

        return $res['data_list'];
    }

    public function getCities()
    {
        $res = JsonRPC::execute('External_ProductBooking.getCities', array('all'));

        return $res['data_list'];
    }

    public function getCityDistricts($city_id = 0)
    {
        $res = JsonRPC::execute('External_ProductBooking.getCityDistricts', array($city_id));

        return $res['data_list'];
    }

    public function getSiteCategories()
    {
        $cat = JsonRPC::execute('External_ProductBooking.getSiteCategories', array(array()));

        $categories = array();
        foreach ($this->getTree($cat['data_list']) as &$cat) {

            /*// Удаление подкатегорий без продуктов
            if ((float)$cat['min_product_price'] == 0 && $cat['level'] > 1) {
                continue;
            }*/
            //$cat['descr'] = preg_replace('/[^a-z\-0-9A-Z]/', '', str_replace(' ', '-', strtolower(!empty($cat['descr']) ? $cat['descr'] : $cat['name'])));
            if ($cat['id'] == '6')
                $categories = $cat['children'];
        }
        unset($cat);

        $this->all_categories = $categories;

        return $this->all_categories;
    }

    public function getSiteProducts($data = array())
    {
        $products = JsonRPC::execute('External_ProductBooking.getSiteProducts', array($data));

        foreach ($products['data_list'] as $key => $product){
            if(!isset($product['price']) || (float)$product['price'] <= 0) unset($products['data_list'][$key]);
        }

        return $products['data_list'];
    }

    public function getSiteProductsByDescr($descr = '')
    {
        $products = JsonRPC::execute('External_ProductBooking.getSiteProducts', array(array('descr' => $descr)));
        foreach ($products['data_list'] as $key => &$prod) {
            if((bool)$prod['deleted'])
                unset($products['data_list'][$key]);
        }unset($prod);
        return $products['data_list'];
    }

    public function getSiteProductsByIds($prod_id)
    {
        $products = JsonRPC::execute('External_ProductBooking.getSiteProducts', array(array('ids' => $prod_id)));

        foreach ($products['data_list'] as &$prod) {
            $prod['image_ids'] = explode(',', $prod['image_id']);
            $prod['price_fl'] = (float)$prod['price'];
        }
        unset($prod);

        return $products['data_list'];
    }


    public function getUserCart($cart_include = false)
    {
        $cart = array();
        $cart['prices'] = array();
        $cart['cc'] = array();

        $count = 0;
        $total = 0;

        $sql = "SELECT * FROM `prices` WHERE `id_user` = ? ORDER BY `date` DESC";
        $cart['prices'] = $this->db->fetchAll($sql, array($this->app['user']['id']));
        foreach ($cart['prices'] as $price) {
            $count += $price['qty'];
            $total += $price['amount'];
        }

        $sql = "SELECT * FROM `custom_cakes` WHERE `id_user` = ? ORDER BY `date` DESC";
        $cart['cc'] = $this->db->fetchAll($sql, array($this->app['user']['id']));
        foreach ($cart['cc'] as &$cake) {
            $sql = "SELECT * FROM `cake_prices` WHERE `id_cc` = ?";
            $cake['prices'] = $this->db->fetchAll($sql, array($cake['id']));
            foreach ($cake['prices'] as $price) {
                $total += $price['amount'];
            }
            $count += 1;
        }
        unset($cake);


        return array('error' => 0, 'count' => $count, 'amount' => $total, 'cart' => $cart_include ? $cart : []);
    }

    private function getTree($ar = array(), $parent = 0)
    {
        $out = array();
        foreach ($ar as $v) {
            if ($v['pid'] == $parent) {
                $q = $this->getTree($ar, $v['id']);
                $v['children'] = [];
                if (!empty($q)) {
                    $v['children'] = $q;
                }
                $out[] = $v;
            }
        }
        return $out;
    }

    public function getPaginations($active, $num){
        $array = array();
        $num = (int)$num;
        if($num > 8){
            if($active >= $num - 3){
                $array[0][1] = 1;
                $array[1] = '...';
                $start = $active - 2;
                $j = 0;
                for($i = $start; $i <= $num; $i++){
                    $array[2][$i] = $start + $j;
                    $j++;
                }
            }else if($active > 4){
                $array[0][1] = 1;
                $array[1] = '...';
                $start = $active - 2;
                $j = 0;
                for($i = $start; $i <= $start + 4; $i++){
                    $array[3][$i] = $start + $j;
                    $j++;
                }
                $array[4] = '...';
                $array[5][$num] = $num;
            }else if($active > 1){
                $start = 1;
                $j = 0;
                for($i = $start; $i <= $active + 2; $i++){
                    $array[0][$i] = $start + $j;
                    $j++;
                }
                $array[2] = '...';
                $array[3][$num] = $num;
            } else {
                for($i = 1; $i <= 3; $i++){
                    $array[0][$i] = $i;
                }
                $array[1] = '...';
                $array[2][$num] = $num;
            }
        } else {
            for($i = 1; $i <= $num; $i++){
                $array[0][$i] = $i;
            }
        }
        return $array;
    }

}
