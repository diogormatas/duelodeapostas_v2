<?php

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {

            $paths = [

                __DIR__ . '/../app/Controllers/',
                __DIR__ . '/../app/Services/',
                __DIR__ . '/../app/Repositories/',
                __DIR__ . '/'

            ];

            foreach ($paths as $path) {

                $file = $path . $class . '.php';

                if (file_exists($file)) {
                    require_once $file;
                    return;
                }
            }
        });
    }
}