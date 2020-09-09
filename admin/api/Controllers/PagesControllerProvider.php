<?php

namespace Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;


class PagesControllerProvider implements ControllerProviderInterface
{
    /**
     * @param Application $app
     * @return mixed
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->match('/', function () use ($app) {
            $sql = "SELECT `p`.*, `tp`.`name` FROM `pages` as `p`
            LEFT JOIN `translations_pages` as `tp` on `tp`.`id_page` = `p`.`id`
            LEFT JOIN `languages` as `l` on `l`.`id` = `tp`.`id_lang`
            WHERE `p`.`is_deleted` != 1 AND `l`.`code` = 'en' ORDER BY `p`.`id` ASC";
            $pages = $app['db']->fetchAll($sql, array());

            foreach ($pages as &$page) {
                $page['id'] = (int)$page['id'];
                unset($page['is_deleted']);
                if ($page['pid'] != 0)
                    $page['children'] = $app['db']->fetchAssoc('SELECT `name` FROM `pages` WHERE `id` = ?', array($page['pid']))['name'];
            }unset($page);

            return $app->json(array(
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'pages' => $pages
                )
            ));
        })->bind('pageList');

        $controllers->match('/get', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['id']))
                return $app->json(array(
                    'error' => 1,
                    'message' => 'Receive incorrect ID'
                ));

            $sql = "SELECT * FROM `pages` WHERE (`id` = ? OR `pid` = 0) AND `is_deleted` != 1";
            $allPages = $app['db']->fetchAll($sql, array((int)$data['id']));

            $currentPage = array();
            foreach ($allPages as $key => $value) {
                $allPages[$key]['is_show'] = (bool)$allPages[$key]['is_show'];
                $allPages[$key]['is_special'] = (bool)$allPages[$key]['is_special'];
                if((int)$allPages[$key]['id'] == (int)$data['id']){
                    $currentPage = $allPages[$key];
                    unset($allPages[$key]);
                }
            };

            $translations = array();
            if(isset($currentPage['id'])) {
                $sql = 'SELECT `tp`.`name` as `trans_name`, `tp`.`content`, `lang`.`name`, `lang`.`code`, `lang`.`id` as `id_lang`, `lang`.`full_name` as `lang` FROM `translations_pages` as `tp`   
                LEFT JOIN `languages` as `lang` on `lang`.`id` = `tp`.`id_lang`
                WHERE `tp`.`id_page` = ? AND `lang`.`is_deleted` = 0 ORDER BY `lang`.`name` ASC';
                $translations = $app['db']->fetchAll($sql, array((int)$currentPage['id']));
            }

            return $app->json(array(
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'page' => $currentPage,
                    'allPages' => array_values($allPages),
                    'translations' => $translations
                )
            ));
        })->bind('getPage');

        $controllers->match('/languages', function (Request $request) use ($app) {
            $sql = "SELECT `name`, `full_name`, `code`, `id` as `id_lang`, `is_deleted` FROM `languages` WHERE `is_deleted` != 1 ORDER BY `name` ASC";
            $languages = $app['db']->fetchAll($sql, array());

            foreach ($languages as &$lang){
                $lang['lang'] = $lang['full_name'];
                $lang['content'] = '';
                $lang['trans_name'] = '';
            }unset($lang);

            return $app->json(['data_list' => $languages, 'error' => 0, 'message' => 'Success']);
        })->bind('languages');

        $controllers->match('/create', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);
            $page_name = '';
            foreach ($data['page']['translations'] as $trans) {
                if ($trans['code'] == 'en') {
                    $page_name = $trans['trans_name'];
                    if ((!isset($trans['trans_name']) || empty(trim($trans['trans_name']))) && $trans['code'] == 'en') {
                        return $app->json(array(
                            'error' => 1,
                            'message' => 'Can\'t create without ' . $trans['lang'] . ' ' . ' Name'
                        ), 400);
                    }
                }
            }

            if (!isset($data['page']['slug']) || empty($data['page']['slug'])) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t create without Slug'
                ), 400);
            }
            $slug = preg_replace('/[^a-z\-0-9A-Z]/', "", str_replace(" ", "-", strtolower($data['page']['slug'])));

            $sql = 'SELECT * FROM `pages` WHERE `slug` = ? AND `is_deleted` = 0';
            $already_slug = $app['db']->fetchAssoc($sql, array($slug));

            if ($already_slug) {
                return $app->json(array(
                    'error' => 6,
                    'message' => 'This Slug already exist in Pages ID ' . $already_slug['id']
                ), 400);
            }

            $prior = 'SELECT `prior` FROM `pages` WHERE `is_deleted` != 1 ORDER BY `prior` DESC LIMIT 1';
            $last_prior = $app['db']->fetchAssoc($prior, array());

            $app['db']->insert('pages', array(
                'name' => $page_name,
                'slug' => $slug,
                'prior' => $last_prior['prior'] + 1,
                'is_show' => (bool)$data['page']['is_show'],
                'is_special' => (bool)$data['page']['is_special'],
                'pid' => !isset($data['page']['pid']) ? 0 : (int)$data['page']['pid']
            ));

            $uid = $app['db']->lastInsertId();

            foreach ($data['page']['translations'] as $trans) {
                $app['db']->insert('translations_pages', array(
                    'name' => $trans['trans_name'],
                    'content' => $trans['content'],
                    'id_lang' => (int)$trans['id_lang'],
                    'id_page' => $uid,
                ));
            }

            return $app->json(['data_list' => $data, 'error' => 0, 'message' => 'Success']);
        })->bind('createPage');

        $controllers->match('/update', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            foreach ($data['page']['translations'] as $trans) {
                if ((!isset($trans['trans_name']) || empty($trans['trans_name'])) && $trans['code'] == 'en') {
                    return $app->json(array(
                        'error' => 1,
                        'message' => 'Can\'t create without '. $trans['lang']. ' '. ' Name'
                    ), 400);
                }
            }

            if (!isset($data['page']['slug']) || empty($data['page']['slug'])) {
                return $app->json(array(
                    'error' => 2,
                    'message' => 'Can\'t edit without Slug'
                ), 400);
            }
            $slug = preg_replace('/[^a-z\-0-9A-Z]/', "", str_replace(" ", "-", strtolower($data['page']['slug'])));

            $sql = 'SELECT * FROM `pages` WHERE `slug` = ?  AND `id` != ? AND `is_deleted` = 0';
            $already_slug = $app['db']->fetchAssoc($sql, array($slug, (int)$data['page']['id']));
            if ($already_slug) {
                return $app->json(array(
                    'error' => 3,
                    'message' => 'This Slug already exist in Pages ID ' . $already_slug['id']
                ), 400);
            }

            $app['db']->update('pages', array(
                'slug' => $slug,
                'prior' => (int)$data['page']['prior'],
                'is_show' => (bool)$data['page']['is_show'],
                'is_special' => (bool)$data['page']['is_special'],
                'pid' => !isset($data['page']['pid']) ? 0 : (int)$data['page']['pid']
            ), array('id' => $data['page']['id']));

            foreach ($data['page']['translations'] as $trans) {
                $app['db']->update('translations_pages', array(
                    'name' => $trans['trans_name'],
                    'content' => $trans['content'],
                ), array('id_page' => (int)$data['page']['id'], 'id_lang' => (int)$trans['id_lang']));
            }

            return $app->json(['data_list' => $data, 'error' => 0, 'message' => 'Success']);
        })->bind('updatePage');

        $controllers->match('/delete', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['id']))
                return $app->json(array(
                    'error' => 1,
                    'message' => 'Receive incorrect ID'
                ), 400);

            $app['db']->update('pages', array('is_deleted' => 1), array('id' => (int)$data['id']));

            return $app->json([
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'id' => $data['id']
                )
            ]);
        })->bind('deleteStaticPage');

        $controllers->match('/page_up', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['item']['prior']) || (int)$data['item']['prior'] <= 1) {
                return $app->json(array(
                    'error' => 1,
                    'message' => 'Receive incorrect Prior'
                ), 400);
            }

            $sql = 'SELECT `id`, `prior` FROM `pages` WHERE `prior` = ? AND `id` = ?';
            $current = $app['db']->fetchAssoc($sql, array($data['item']['prior'], $data['item']['id']));

            $sql_old = 'SELECT `id`, `prior` FROM `pages` WHERE `prior` < ? AND `pid` = ? AND `is_deleted` != 1 ORDER BY `prior` DESC lIMIT 1';
            $past = $app['db']->fetchAssoc($sql_old, array($data['item']['prior'], $data['item']['pid']));
            if ($past == false) {
                return $app->json(array(
                    'error' => 2,
                    'message' => 'Can\'t change Prior'
                ));
            }

            $app['db']->update('pages', array('prior' => $current['prior']), array('id' => $past['id']));
            $app['db']->update('pages', array('prior' => $past['prior']), array('id' => $current['id']));

            return $app->json(['error' => 0, 'message' => 'Success', 'data_list' => ['current' => $current, 'past' => $past]]);

        })->bind('pageUp');

        $controllers->match('/page_down', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['item']['prior'])) {
                return $app->json(array(
                    'error' => 1,
                    'message' => 'Receive incorrect Prior'
                ), 400);
            }

            $sql = 'SELECT `id`, `prior` FROM `pages` WHERE `prior` = ? AND `id` = ?';
            $current = $app['db']->fetchAssoc($sql, array($data['item']['prior'], $data['item']['id']));

            $sql_old = 'SELECT `id`, `prior` FROM `pages` WHERE `prior` > ? AND `pid` = ? AND `is_deleted` != 1 ORDER BY `prior` ASC lIMIT 1';
            $past = $app['db']->fetchAssoc($sql_old, array($data['item']['prior'], $data['item']['pid']));
            if (!$past) {
                return $app->json(array(
                    'error' => 2,
                    'message' => 'Can\'t change Prior'
                ));
            }

            $app['db']->update('pages', array('prior' => $current['prior']), array('id' => $past['id']));
            $app['db']->update('pages', array('prior' => $past['prior']), array('id' => $current['id']));

            return $app->json(['error' => 0, 'message' => 'Success', 'data_list' => ['current' => $current, 'past' => $past]]);
        })->bind('pageDown');

        $controllers->match('/parent_pages', function () use ($app) {
            $sql = "SELECT `id`, `pid`, `name` FROM `pages` as `p` WHERE  `pid` = 0 AND `is_deleted` = 0";
            $parents = $app['db']->fetchAll($sql, array());

            return $app->json([
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'parents' => $parents
                )
            ]);
        })->bind('getParentPages');

        $controllers->match('/children_pages', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['pid'])) {
                return $app->json(array(
                    'error' => 1,
                    'message' => 'Receive incorrect ID'
                ), 400);
            }

            $sql = "SELECT `p`.*, `tp`.`name` FROM `pages` as `p`
            LEFT JOIN `translations_pages` as `tp` on `tp`.`id_page` = `p`.`id`
            LEFT JOIN `languages` as `l` on `l`.`id` = `tp`.`id_lang`
            WHERE  `p`.`pid` = ? AND `p`.`is_deleted` = 0 AND `l`.`code` = 'ua' ORDER BY `p`.`id` ASC";
            $parent = $app['db']->fetchAll($sql, array($data['pid']));

            foreach ($parent as &$p) {
                $sql = 'SELECT COUNT(`id`) as `count` FROM `pages` 
                    WHERE `pid` = ? AND `is_deleted` = 0 
                    ORDER BY `id` ASC';
                $child = $app['db']->fetchAssoc($sql, array($p['id']));

                $p['child_count'] = $child['count'];
            }
            unset($p);

            return $app->json(['error' => 0, 'data_list' => $parent, 'message' => 'Success']);
        })->bind('getChildrenPages');

        $controllers->match('/upload_photo', function (Request $request) use ($app) {
            $file = $request->files->get('file');
            if ($file) {
                $fn = $file->getClientOriginalName();
                $str = explode(".", $fn);
                $ext = strtolower($str[count($str) - 1]);
                if (!in_array($ext, array('jpg', 'jpeg', 'png')))
                    return $app->json(array(
                        'error' => 1,
                        'message' => 'Wrong News logo format! Needed - jpg, jpeg, png',
                    ), 400);
                $directory = date('Y') . '/' . date('m');
                $directory_full = $app['path_img'] . '/pages/' . $directory;
                if (!is_dir($directory_full))
                    mkdir($directory_full, 0755, true);
                $random = uniqid(null, true);
                $file_name = substr($random, 0, 13) . substr($random, 15);
                $file->move($directory_full, $file_name . '.' . $ext);
            }

            return $app->json([
                'error' => 0,
                'imageUrl' => 'https://katrina.ae/uploads/pages/' . $directory . '/' . $file_name . '.' . $ext,
                'data_list' => array(
                    'filepath' => $directory . '/' . $file_name . '.' . $ext,
                ),
                'message' => 'Success']);
        })->bind('uploadImagePages');


        return $controllers;
    }
}
