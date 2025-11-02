<?php

namespace ArthurSalenko\KasmClient\Facades;

use Illuminate\Support\Facades\Facade;

class Kasm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'kasm-client';
    }
}
