<?php

require_once "Human.php";

class Bot extends Human
{
    public function __construct($chatbot, $unique)
    {
        parent::__construct($chatbot, $unique, "bot");
    }
}
