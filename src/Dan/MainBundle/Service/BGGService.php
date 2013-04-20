<?php

namespace Dan\MainBundle\Service;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Cache\NullCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Dan\MainBundle\Entity\Game;

class BGGService
{

    private $cache;
    private $guzzleClient;

    public function __construct(Cache $cache = null, GuzzleClient $guzzleClient = null)
    {
        $this->cache = $cache;

        if (!$guzzleClient) {
            $guzzleClient = new GuzzleClient();
        }

        $guzzleClient->setBaseUrl('http://www.boardgamegeek.com/xmlapi/');

        $adapter = new DoctrineCacheAdapter($cache);
//        $adapter = new NullCacheAdapter();
        $cachePlugin = new CachePlugin(array(
                    'adapter' => $adapter,
                ));

        $guzzleClient->addSubscriber($cachePlugin);

        $this->guzzleClient = $guzzleClient;
    }

    public function getGames()
    {
        $guzzleClient = $this->guzzleClient;
        $cache = $this->cache;
        $users = array(
            'ventoonirico' => 'Ventoonirico',
            'danielsan80' => 'Danilo',
            'mcevoli' => 'Marco',
            'rotilio' => 'Rollo',
            'f4br1z10' => 'Fabri',
        );

        $requests = array();
        foreach ($users as $username => $name) {
            if (false !== ($collection = $cache->fetch('collection.' . $username))) {
                $collections[$username] = $collection;
                continue;
            }
            $request = $guzzleClient->get('collection/' . $username);
            $request->getParams()->set('cache.override_ttl', 300);
            $requests[] = $request;
        }

        $responses = $guzzleClient->send($requests);

        foreach ($responses as $response) {
            $url = $response->getRequest()->getUrl();
            preg_match('/(?P<username>\w+)$/', $url, $matches);
            $username = $matches['username'];
            $name = $users[$username];
            $xml = $response->xml();
            $games = array();

            $items = $xml->xpath('/items/item');

            foreach ($items as $item) {
                $game = new Game($item, array('user' => $name));
                if ($game->isOwned()) {
                    $games[$game->getId()] = $game;
                }
            }
            $collections[$username] = $games;
            $cache->save('collection.' . $username, $games, 3600); //TTL 1h
        }

        $games = array();
        foreach ($collections as $username => $collection) {
            $name = $users[$username];
            foreach ($collection as $id => $game) {
                if (isset($games[$id])) {
                    $games[$id]->addOwner($name);
                } else {
                    $games[$id] = $game;
                }
            }
        }
        
        $games = $this->sortGames($games);

        return $games;
    }

    private function sortGames($games)
    {
        $games2 = $games;
        uasort($games2, array($this, 'compare'));
        return $games2;
    }

    private function compare($a, $b)
    {
        $aName = $a->getName();
        $bName = $b->getName();
        if ($aName == $bName) {
            return 0;
        }
        return ($aName < $bName) ? -1 : 1;
    }

    public function shiftGames($games)
    {
        $now = new \DateTime();
        $year = $now->format('Y') - 2010;
        $days = $now->format('z') + ($year * 365);
        $offset = $days % count($games);
        $slice = array_slice($games, $offset, null, true);
        $games = array_slice($games, 0, $offset, true);
        $games = array_merge($slice, $games);
        return $games;
    }

}