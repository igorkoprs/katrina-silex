<?php

namespace Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class TranslationsControllerProvider implements ControllerProviderInterface
{
    /**
     * @param Application $app
     * @return mixed
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->match('/', function () use ($app) {
            $sql = "SELECT * FROM `translation_words`";
            $words = $app['db']->fetchAll($sql, array());

            foreach ($words as &$word){
                $sql = "SELECT `trans`.*, `lang`.`name`, `lang`.`code` FROM `translations` as `trans`
                LEFT JOIN `languages` as `lang` on `lang`.`id` = `trans`.`id_lang`
                WHERE `trans`.`id_word` = ? AND `trans`.`translation` != ''";
                $translations = $app['db']->fetchAll($sql, array((int)$word['id']));
                if($translations)
                    foreach ($translations as &$trans){
                        if($trans['code'] == 'en')
                            $word['original'] = strlen($trans['translation']) > 30 ? substr($trans['translation'], 0, 30). '...' : $trans['translation'];
                        else
                            $word['langs'][] = $trans['name'];
                    }unset($trans);
            }unset($word);

            return $app->json(
                array(
                    'error' => 0,
                    'message' => 'Success',
                    'data_list' => array(
                        'words' => $words
                    ),
                ));
        })->bind('wordsList');

        $controllers->match('/languages', function () use ($app) {
            $sql = "SELECT * FROM `languages`";
            $languages = $app['db']->fetchAll($sql, array());

            return $app->json(['data_list' => $languages, 'error' => 0, 'message' => 'Success']);
        })->bind('transLanguages');

        $controllers->match('/getLang', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);
            $sql = "SELECT * FROM `languages` WHERE `id` = ?";
            $language = $app['db']->fetchAssoc($sql, array((int)$data['id']));

            $language['is_deleted'] = (bool)$language['is_deleted'];
            return $app->json(['data_list' => $language, 'error' => 0, 'message' => 'Success']);
        })->bind('getLang');

        $controllers->match('/updateLang', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['lang']['full_name']) || empty($data['lang']['full_name'])) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t create without Full Name'
                ), 400);
            }

            if (!isset($data['lang']['name']) || empty($data['lang']['name'])) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t create without Short Name'
                ), 400);
            }

            if (!isset($data['lang']['code']) || empty($data['lang']['code'])) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t create without Code'
                ), 400);
            }

            $code = preg_replace('/[^a-zA-Z]/', "", str_replace(" ", "", strtolower($data['lang']['code'])));

            $sql = 'SELECT * FROM `languages` WHERE `code` = ? AND `id` != ?';
            $already_code = $app['db']->fetchAssoc($sql, array($code, (int)$data['lang']['id']));
            if ($already_code) {
                return $app->json(array(
                    'error' => 8,
                    'message' => 'This Code already exist in Language ID ' . $already_code['id']
                ), 400);
            }

            $app['db']->update('languages', array(
                'full_name' => $data['lang']['full_name'],
                'name' => $data['lang']['name'],
                'code' => $data['lang']['code'],
                'is_deleted' => !$data['lang']['is_active'],
            ), array('id' => (int)$data['lang']['id']));

            return $app->json(array(
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'id' => $data['lang']['id']
                ),
            ));
        })->bind('updateLang');

        $controllers->match('/createLang', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['lang']['full_name']) || empty($data['lang']['full_name'])) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t create without Full Name'
                ), 400);
            }

            if (!isset($data['lang']['name']) || empty($data['lang']['name'])) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t create without Short Name'
                ), 400);
            }

            if (!isset($data['lang']['code']) || empty($data['lang']['code'])) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t create without Code'
                ), 400);
            }

            $code = preg_replace('/[^a-zA-Z]/', "", str_replace(" ", "", strtolower($data['lang']['code'])));

            $sql = 'SELECT * FROM `languages` WHERE `code` = ?';
            $already_code = $app['db']->fetchAssoc($sql, array($code));
            if ($already_code) {
                return $app->json(array(
                    'error' => 8,
                    'message' => 'This Code already exist in Language ID ' . $already_code['id']
                ), 400);
            }

            $app['db']->insert('languages', array(
                'full_name' => $data['lang']['full_name'],
                'name' => $data['lang']['name'],
                'code' => $data['lang']['code']
            ));

            $uid = $app['db']->lastInsertId();

            $sql = 'SELECT * FROM `pages` WHERE `is_deleted` = 0';
            $pages = $app['db']->fetchAll($sql, array());

            foreach ($pages as $page) {
                $app['db']->insert('translations_pages', array(
                    'name' => '',
                    'content' => '',
                    'id_lang' => $uid,
                    'id_page' => (int)$page['id'],
                ));
            }

            $sql = 'SELECT * FROM `news` WHERE `is_deleted` = 0';
            $news = $app['db']->fetchAll($sql, array());

            foreach ($news as $n) {
                $app['db']->insert('translations_pages', array(
                    'name' => '',
                    'content' => '',
                    'id_lang' => $uid,
                    'id_news' => (int)$n['id'],
                ));
            }

            $sql = 'SELECT * FROM `translation_words`';
            $words = $app['db']->fetchAll($sql, array());

            foreach ($words as &$word){
                $app['db']->insert('translations', array(
                    'id_lang' => (int)$uid,
                    'id_word' => (int)$word['id'],
                    'translation' => '',
                ));
            }unset($lang);

            return $app->json(array(
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'id' => $uid
                ),
            ));
        })->bind('createLang');

        $controllers->match('/getTranslation', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['id']))
                return $app->json(array(
                    'error' => 1,
                    'message' => 'Receive incorrect ID'
                ));

            $sql = "SELECT * FROM `translation_words` WHERE `id` = ?";
            $word = $app['db']->fetchAssoc($sql, array((int)$data['id']));

            $sql = "SELECT * FROM `languages` WHERE `is_deleted` = 0 ORDER BY `name` ASC";
            $languages = $app['db']->fetchAll($sql, array());

            $sql = "SELECT * FROM `translations` WHERE `id_word` = ?";
            $translations = $app['db']->fetchAll($sql, array((int)$word['id']));

            if($translations)
                foreach ($languages as &$lang){
                    $lang['translation'] = '';
                    foreach ($translations as &$trans){
                        if($lang['id'] == $trans['id_lang'])
                            $lang['translation'] = $trans['translation'];
                    }unset($trans);
                }unset($lang);

            $word['languages'] = $languages;
            return $app->json(array(
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'word' => $word
                ),
            ));
        })->bind('getTranslation');

        $controllers->match('/getLanguages', function (Request $request) use ($app) {
            $sql = "SELECT `id`, `code`, `name`, `full_name` FROM `languages` WHERE `is_deleted` = 0 ORDER BY `name` ASC";
            $languages = $app['db']->fetchAll($sql, array());
            foreach ($languages as &$lang){
                $lang['translation'] = '';
            }unset($lang);

            $word['languages'] = $languages;
            return $app->json(['data_list' => $word, 'error' => 0, 'message' => 'Success']);
        })->bind('getLanguages');

        $controllers->match('/update', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['word']['slug']) || empty($data['word']['slug'])) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t create without Slug'
                ), 400);
            }
            $slug = preg_replace('/[^a-z\_0-9A-Z]/', "", str_replace(" ", "_", strtolower($data['word']['slug'])));

            $sql = 'SELECT * FROM `translation_words` WHERE `slug` = ?  AND `id` != ?';
            $already_slug = $app['db']->fetchAssoc($sql, array($slug, (int)$data['word']['id']));
            if ($already_slug) {
                return $app->json(array(
                    'error' => 8,
                    'message' => 'This Slug already exist in Slug ID ' . $already_slug['id']
                ), 400);
            }

            foreach ($data['word']['languages'] as &$lang){
                if($lang['code'] == 'en' && (!isset($lang['translation']) || $lang['translation'] == ''))
                    return $app->json(array(
                        'error' => 5,
                        'message' => 'English translation is required'
                    ), 400);
            }unset($lang);


            $app['db']->update('translation_words', array(
                'slug' => $slug,
            ), array('id' => $data['word']['id']));

            foreach ($data['word']['languages'] as &$lang){
                $sql = 'SELECT * FROM `translations` WHERE `id_lang` = ?  AND `id_word` = ?';
                $exist = $app['db']->fetchAssoc($sql, array((int)$lang['id'], (int)$data['word']['id']));
                if($exist) {
                    $app['db']->update('translations', array(
                        'translation' => $lang['translation'],
                    ), array('id_lang' => (int)$lang['id'], 'id_word' => (int)$data['word']['id']));
                } else {
                    $app['db']->insert('translations', array(
                        'id_lang' => (int)$lang['id'],
                        'id_word' => (int)$data['word']['id'],
                        'translation' => $lang['translation'],
                    ));
                }
            }unset($lang);


            return $app->json(array(
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'id' => $data['word']['id']
                ),
            ));
        })->bind('updateTranslation');

        $controllers->match('/create', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['word']['slug']) || empty($data['word']['slug'])) {
                return $app->json(array(
                    'error' => 5,
                    'message' => 'Can\'t create without Slug'
                ), 400);
            }
            $slug = preg_replace('/[^a-z\_0-9A-Z]/', "", str_replace(" ", "_", strtolower($data['word']['slug'])));

            $sql = 'SELECT * FROM `translation_words` WHERE `slug` = ?';
            $already_slug = $app['db']->fetchAssoc($sql, array($slug));

            if ($already_slug) {
                return $app->json(array(
                    'error' => 6,
                    'message' => 'This Slug already exist in Slug ID ' . $already_slug['id']
                ), 400);
            }

            foreach ($data['word']['languages'] as &$lang){
                if($lang['code'] == 'en' && (!isset($lang['translation']) || $lang['translation'] == ''))
                    return $app->json(array(
                        'error' => 5,
                        'message' => 'English translation is required'
                    ), 400);
            }unset($lang);

            $app['db']->insert('translation_words', array(
                'slug' => $slug,
            ));

            $uid = $app['db']->lastInsertId();

            foreach ($data['word']['languages'] as &$lang){
                $app['db']->insert('translations', array(
                    'id_lang' => (int)$lang['id'],
                    'id_word' => (int)$uid,
                    'translation' => $lang['translation'],
                ));
            }unset($lang);

            return $app->json(array(
                'error' => 0,
                'message' => 'Success',
                'data_list' => array(
                    'id' => $data['word']['id']
                ),
            ));
        })->bind('createTranslation');

        return $controllers;
    }
}
