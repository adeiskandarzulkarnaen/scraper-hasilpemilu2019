<?php

namespace app\services;

class Service
{
    public function __construct(
        public $start,
        public $end,
        public $type,
        public $overwrite,
    )
    {
    }
}
