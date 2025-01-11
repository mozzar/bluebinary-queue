<?php

namespace App\Libraries;

use Redis;

class RedisClient
{
    protected $redis;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);

    }

    public function getInstance(): Redis
    {
        return $this->redis;
    }


    public function getCoasters()
    {
        $coasters = $this->redis->hMget('coasters', ['__ci_type', "__ci_value"]);
        if (!$coasters) {
            $coasters = [];
        } else {
            $coasters = $coasters["__ci_value"];
            if (json_validate($coasters)) {
                $coasters = json_decode($coasters, true);
            } else {
                $coasters = [];
            }
        }
        return $coasters;
    }


    public function getCoastersCount()
    {
        return $this->redis->get('coasters_count');
    }


    public function setCoastersCount($count): void
    {
        $this->redis->set('coasters_count', $count);
    }

    public function setCoasters(array $coasters = []): void{
        $this->redis
            ->hMset('coasters',
                [
                    '__ci_type' => 'string',
                    '__ci_value' => json_encode($coasters)
                ]
            );

    }

    public function reset():void {
        $this->redis->del('coasters');
        $this->redis->del('coasters_count');
    }
}
