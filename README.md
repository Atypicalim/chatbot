#  An aiml parser for PHP

--- 


### [中文](README_CH.md) 

---

## 0. notice

> the chatbot is writen accordign to AIML 2.5 and [Program-P](https://github.com/pe77/Program-P)，it uses utf-8 and i made some customizaton , there is some differences between standard aiml tags and the tags uses in this program.

## 1. description

> it is working well on PHP5.4 and Apache server .

## 2. database configuration

> you should use MySQL database and import the `chatbot.sql` file to your database . then, you should configure your database information in `chatbot/Config.php` file . 


## 4. aiml sourses

> you can edit `aiml/chatbot.aiml` fiel directly or create an aiml file in aiml directory and include it in `aiml/chatbot.aiml` file。

## 5. about aiml

> * `aiml` files should be paleced in `aiml`directory. 

> * `chatbot.aiml`is the entrance ，the `aiml` tag in it can contain various `category` tag , a `default`tag and various `include`tag。

> * other `aiml` files writen by you should contain a `topic` tag in root `aiml` tag , and the topic can contain various `category` tag and a `default` tag (it should not have `include` tag)。


## 6. test

> you can visit `index.php` or `api.php?requestType=talk&userInput=hello`。


## 8. about the regular expressions

> Modified matching rules :

```PHP
* ---> (\S+)
# ---> \S+
_ ---> .*
= ---> \S*
```

## 9. about aiml tags

> * it is different from the standard aiml，i made some customizaton to aiml tags , you can see the tag rules in [AIML.MD](AIML.md) file。


## 10. Enjoy it

> [https://github.com/kompasim/chatbot](https://github.com/kompasim/chatbot)

![chatbot](./web/img/demo.png)