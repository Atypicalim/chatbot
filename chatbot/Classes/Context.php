<?php

/**
 * Created by PhpStorm.
 * User: Alvido_bahor
 * Date: 2017/4/26
 * Time: 15:28
 */
class Context
{
    private static $_config;
    private static $_unique;

    static function init($config, $unique){
        //
        self::$_config = $config;
        self::$_unique = $unique;
    }

    static function getAimlString($user, $bot, $input){
        $fileFullName = self::$_config->parserInfo['aimlDir'] . '/chatbot.aiml';
        if (!file_exists($fileFullName)) {
            throw new \Exception("AIML file not found in : " . $fileFullName);
        }
        return file_get_contents($fileFullName);


    }

}