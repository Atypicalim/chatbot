<?php


class Config
{

    public $log = false;
    public $multiChatbot = true;

    public $connectionInfo = array(
        "host" => "localhost",
        "user" => "root",
        "pass" => "root",
        "dbName" => "chatbot"
    );

    public $parserInfo = array(
        "aimlDir" => "aiml"
    );

    public $botInfo =  array(
            'name' => "botName",
            'age' => "1",
            'owner' => "OLCHEM SOFTWARE TECKNOLOGY",
            'website' => "chatbot.github.io",
            'version' => "1.0"
        );

    public $userInfo = array(
        "name" => "userName",
        "age" => 20,
    );

}


