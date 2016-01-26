<?php
namespace SplitIO\Common;

class Service
{
    public static function registerLogger(\Psr\Log\LoggerInterface $logger)
    {
        Di::getInstance()->setLogger($logger);
    }

    public static function registerCache(\Psr\Cache\CacheItemPoolInterface $cache)
    {
        Di::getInstance()->setCache($cache);
    }
}