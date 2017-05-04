<?php

require_once "Humans/User.php";
require_once "Humans/Bot.php";
require_once "Classes/Parser.php";
require_once "Classes/Context.php";
require_once "Database/Connection.php";



class Chatbot
{
    private $_unique;
    private $_config;
    private $_bots = array();
    private $_users = array();
    private $_data = array();

    /**
     * Chatbot constructor.
     * @param $config
     */
    function __construct($config, $unique)
    {
        LOG && print("chatbot constractor ...\n");
        $this->_unique = $unique;
        $this->_config = $config;
        Context::init($this->_config, $unique);
        Parser::init($this->_config->parserInfo);
        Connection::init($this->_config->connectionInfo);
    }

    /**
     * talk
     * @param $userInput
     * @return string
     */
    function talk($userInput)
    {
        LOG && print("chatbot talk ...\n");
        $user = $this->getUser($this->_unique);
        if ($this->_config->multiChatbot){
            $bot = $this->getBot($this->_unique);
        } else {
            $bot = $this->getBot("default");
        }
        // parse
        $response = Parser::Parse($user, $bot, $userInput);
        // we should call the parser:parse first , then wen can get response data successfully
        $this->_data = Parser::GetResponseData();
        // log
        $this->log($user, $userInput, $response, $bot);
        return $response . "";
    }


    /**
     * forget
     */
    function forget(){
        LOG && print("chatbot forget ...\n");
        $this->getUser($this->_unique)->clearAllProp();
    }


    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Return a Bot
     * @param string $unique - Unique Identification (AIML file in dir, without '.aiml')
     * @return Bot
     */
    public function getBot($unique)
    {
        if (!array_key_exists($unique, $this->_bots)) {
            $this->_bots[$unique] = new Bot($this, $unique);
        }
        return $this->_bots[$unique];
    }

    /**
     *  Load user by unique key (name+IP exemple)
     * @param $unique
     * @return mixed
     */
    function getUser($unique)
    {
        if (!array_key_exists($unique, $this->_users)) {
            $this->_users[$unique] = new User($this, $unique);
        }
        return $this->_users[$unique];
    }


    /**
     * @return int
     */
    public function countUsers()
    {
        return count($this->_bots);
    }

    /**
     * @return int
     */
    public function CountBots()
    {
        return count($this->_bots);
    }


    /**
     * @param User $user
     * @param $input
     * @param $response
     * @param Bot $bot
     */
    private function log(User $user, $input, $response, Bot $bot)
    {
        $input = mysqli_real_escape_string(Connection::$connIdent, utf8_decode($input));
        $response = mysqli_real_escape_string(Connection::$connIdent,utf8_decode(trim($response)));
        // log conversation
        $sql = "INSERT INTO log (user, bot, input, response, date) VALUE ('" . $user->getUnique() . "', '" . $bot->getUnique() . "', '" . trim($input) . "', '" . trim($response) . "', NOW());";
        Connection::Query($sql);
    }
}
