<?php

namespace Shela\RedfinParser;

class Client
{
    public $query;

    public function query($request)
    {
        $this->query = new API\Query($request);

        return $this->query;
    }
}
