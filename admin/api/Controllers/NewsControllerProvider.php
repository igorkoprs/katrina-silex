<?php

namespace Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class NewsControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->match('/', function () use ($app) {
            $sql = 'SELECT * FROM `news` WHERE `is_deleted` != 1 ORDER BY `published` DESC';
            $news = $app['db']->fetchAll($sql);

            return $app->json(array(
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'news' => $news
                )
            ));
        })->bind('newsList');

        $controllers->match('/get', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            $sql = 'SELECT * FROM `news` WHERE `id` = ? AND `is_deleted` != 1';
            $news = $app['db']->fetchAssoc($sql, array($data['id']));

            if (!isset($data['id']) || $news === false)
                return $app->json(array(
                    'error' => 1,
                    'message' => 'Receive incorrect ID'
                ), 400);
            $news['is_active'] = (bool)$news['is_active'];

            $translations = array();
            if(isset($news['id'])) {
                $sql = 'SELECT `tp`.`name` as `trans_name`, `tp`.`content`, `lang`.`name`, `lang`.`code`, `lang`.`id` as `id_lang`, `lang`.`full_name` as `lang` FROM `translations_pages` as `tp`   
                LEFT JOIN `languages` as `lang` on `lang`.`id` = `tp`.`id_lang`
                WHERE `tp`.`id_news` = ? AND `lang`.`is_deleted` = 0 ORDER BY `lang`.`name` ASC';
                $translations = $app['db']->fetchAll($sql, array((int)$news['id']));
            }
            if(empty($translations)){
                $sql = "SELECT `name`, `full_name`, `code`, `id` as `id_lang`, `is_deleted` FROM `languages` WHERE `is_deleted` != 1 ORDER BY `name` ASC";
                $languages = $app['db']->fetchAll($sql, array());
                foreach ($languages as &$lang){
                    $lang['lang'] = $lang['full_name'];
                    $lang['content'] = '';
                    $lang['trans_name'] = '';
                    $translations[] = $lang;
                }unset($lang);
            }

            return $app->json(array(
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'news' => $news,
                    'translations' => $translations
                )
            ));
        })->bind('getNews');

        $controllers->match('/create', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);
            $news_name = '';
            foreach ($data['news']['translations'] as $trans) {
                if ($trans['code'] == 'en') {
                    $news_name = $trans['trans_name'];
                    if ((!isset($trans['trans_name']) || empty($trans['trans_name'])) && $trans['code'] == 'en') {
                        return $app->json(array(
                            'error' => 1,
                            'message' => 'Can\'t create without ' . $trans['lang'] . ' ' . 'Name'
                        ), 400);
                    }
                }
            }

            if (!isset($data['news']['slug']) || empty($data['news']['slug'])) {
                return $app->json(array(
                    'error' => 2,
                    'message' => 'Can\'t edit without Slug'
                ), 400);
            }
            $slug = preg_replace('/[^a-z\-0-9A-Z]/', "", str_replace(" ", "-", strtolower($data['news']['slug'])));

            $sql = 'SELECT * FROM `news` WHERE `slug` = ?  AND `id` != ? AND `is_deleted` = 0';
            $already_slug = $app['db']->fetchAssoc($sql, array($slug, (int)$data['news']['id']));
            if ($already_slug) {
                return $app->json(array(
                    'error' => 3,
                    'message' => 'This Slug already exist in News ID ' . $already_slug['id']
                ), 400);
            }

            if (!isset($data['news']['image']) || empty(trim($data['news']['image']))) {
                return $app->json(array(
                    'error' => 4,
                    'message' => 'Can\'t edit without Image'
                ), 400);
            }

            if (!isset($data['news']['published']) || empty(trim($data['news']['published']))) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t edit without Date'
                ), 400);
            }

            $slug = preg_replace('/[^a-z\-0-9A-Z]/', "", str_replace(" ", "-", strtolower($data['news']['slug'])));

            $data['news']['published'] = date("Y-m-d", strtotime($data['news']['published']));
            $app['db']->insert('news', array(
                'name' => $news_name,
                'slug' => $slug,
                'is_active' => (bool)$data['news']['is_active'],
                'published' => $data['news']['published'],
                'image' => $data['news']['image'],
            ));

            $uid = $app['db']->lastInsertId();

            foreach ($data['news']['translations'] as $trans) {
                $app['db']->insert('translations_pages', array(
                    'name' => $trans['trans_name'],
                    'content' => $trans['content'],
                    'id_lang' => (int)$trans['id_lang'],
                    'id_news' => $uid,
                ));
            }

            return $app->json(['error' => 0, 'data_list' => $data, 'message' => 'Success']);
        })->bind('createNews');

        $controllers->match('/delete', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['id']))
                return $app->json(array(
                    'error' => 1,
                    'message' => 'Receive incorrect ID'
                ), 400);

            $app['db']->update('news', array('is_deleted' => '1'), array('id' => $data['id']));

            return $app->json(['error' => 0, 'message' => 'Success', 'data_list' => ['id' => $data['id']]]);
        })->bind('deleteNews');

        $controllers->match('/update', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);
            $news_name = '';
            foreach ($data['news']['translations'] as $trans) {
                if ($trans['code'] == 'en') {
                    $news_name = $trans['trans_name'];
                    if ((!isset($trans['trans_name']) || empty($trans['trans_name'])) && $trans['code'] == 'en') {
                        return $app->json(array(
                            'error' => 1,
                            'message' => 'Can\'t create without ' . $trans['lang'] . ' ' . 'Name'
                        ), 400);
                    }
                }
            }

            if (!isset($data['news']['slug']) || empty($data['news']['slug'])) {
                return $app->json(array(
                    'error' => 2,
                    'message' => 'Can\'t edit without Slug'
                ), 400);
            }
            $slug = preg_replace('/[^a-z\-0-9A-Z]/', "", str_replace(" ", "-", strtolower($data['news']['slug'])));

            $sql = 'SELECT * FROM `news` WHERE `slug` = ?  AND `id` != ? AND `is_deleted` = 0';
            $already_slug = $app['db']->fetchAssoc($sql, array($slug, (int)$data['news']['id']));
            if ($already_slug) {
                return $app->json(array(
                    'error' => 3,
                    'message' => 'This Slug already exist in News ID ' . $already_slug['id']
                ), 400);
            }

            if (!isset($data['news']['image']) || empty(trim($data['news']['image']))) {
                return $app->json(array(
                    'error' => 4,
                    'message' => 'Can\'t edit without Image'
                ), 400);
            }

            if (!isset($data['news']['published']) || empty(trim($data['news']['published']))) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t edit without Date'
                ), 400);
            }

            $slug = preg_replace('/[^a-z\-0-9A-Z]/', "", str_replace(" ", "-", strtolower($data['news']['slug'])));

            $data['news']['published'] = date("Y-m-d", strtotime($data['news']['published']));
            $app['db']->update('news', array(
                'name' => $news_name,
                'slug' => $slug,
                'is_active' => (bool)$data['news']['is_active'],
                'published' => $data['news']['published'],
                'image' => $data['news']['image'],
            ), array('id' => (int)$data['news']['id']));

            foreach ($data['news']['translations'] as $trans) {
                $app['db']->update('translations_pages', array(
                    'name' => $trans['trans_name'],
                    'content' => $trans['content'],
                ), array('id_news' => (int)$data['news']['id'], 'id_lang' => (int)$trans['id_lang']));
            }

            return $app->json(
                array(
                    'error' => 0,
                    'message' => 'Success',
                    'data_list' => array(
                        'id' => $data['news']['id']
                    ),
                )
            );
        })->bind('updateNews');

        $controllers->match('/upload', function (Request $request) use ($app) {
            $file = $request->files->get('file');
            if ($file) {
                $fn = $file->getClientOriginalName();
                $str = explode(".", $fn);
                $ext = strtolower($str[count($str) - 1]);
                if (!in_array($ext, array('jpg', 'jpeg', 'png')))
                    return $app->json(array(
                        'error' => 1,
                        'message' => 'Wrong News image format!',
                    ));
                $directory = date('Y') . '/' . date('m');
                $directory_full = $app['path_img'] . '/news/' . $directory;
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
                ),
            ));
        })->bind('uploadImageNews');

        return $controllers;
    }
}