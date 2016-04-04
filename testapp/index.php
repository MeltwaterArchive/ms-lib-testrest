<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app->register(new SilexMemcache\MemcacheExtension(), array(
    'memcache.library' => 'memcached',
    'memcache.server' => array(
        array('127.0.0.1', 11211)
    )
));

$app->match('empty',
    function (Request $req) {
        return new JsonResponse(null, 204);
    }
);

$app->match(
    'echo',
    function (Request $req) use ($app) {
        $ret = array(
            'last-run' => $app['memcache']->get('last-run'),
            'warning' => 'Do not expose this service in production : it is intrinsically unsafe',
        );
        $app['memcache']->set('last-run', date('Y-m-d H:i:s'));

        $ret['method'] = $req->getMethod();

        // Forms should be read from request, other data straight from input.
        $requestData = $req->request->all();
        if (! empty($requestData)) {
            foreach ($requestData as $key => $value) {
                $ret[$key] = $value;
            }
        }

        /** @var string $content */
        $content = $req->getContent(false);
        if (! empty($content)) {
            $data = json_decode($content, true);
            if (! is_array($data)) {
                $ret['content'] = $content;
            } else {
                foreach ($data as $key => $value) {
                    $ret[$key] = $value;
                }
            }
        }

        $ret['headers'] = array();
        foreach ($req->headers->all() as $k => $v) {
            $ret['headers'][$k] = $v;
        }
        foreach ($req->query->all() as $k => $v) {
            $ret['query'][$k] = $v;
        }
        $response = new JsonResponse($ret);

        return $response;
    }
);

$app->run();
