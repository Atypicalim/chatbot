#  基于AIML的PHP聊天天机器人


--- 

### [English](README.md) 

---

## 0. 提醒

> 该聊天机器人是参考AIML 2.5和[Program-P](https://github.com/pe77/Program-P)而写成的，这聊天机器人实现的aiml标签和标准的aiml标签由一定的差距，所以你从网上下载的aiml语料库可能没法正常工作。适用于UTF-8编码的，单词后缀根据不同的时态而改变的那些语言。感兴趣的朋友可以自己研究学习一下。

## 1. 介绍

> 这是一个用PHP写的aiml解析器，目前在PHP5.4环境上能正常运行。

## 2. 数据库配置

> 本程序用了MySQL数据库，你需要把`chatbot.sql` 文件倒入到你的数据库，然后在`chatbot/Config.php`文件里修改相关数据库配置变量. 


## 4. aiml语料库资源

> 你可以直接编辑 `aiml/chatbot.aiml` 文件或者创建一个新的`aiml`文件然后在`aiml/chatbot.aiml`文件用`include`标签引入它。

## 5. 关于aiml文件

> * `aiml` 必须要放在 `aiml` 目录内. 

> * `chatbot.aiml` 是入口文件 ，它里面的 `aiml` 标签可以包含多个 `category` 标签 , 一个 `default`标签和多个 `include`标签。

> *  被你添加的其它 `aiml` 文件在根`aiml`标签里必须要包含一个 `topic` 标签 , 这个topic标签可以包含多个 `category` 标签和一个`default`标签 (这里的其他aiml文件不能写`include`标签，`include`标签只能出现在`chatbot.aiml`文件里)。


## 6. 测试聊天机器人

> 你可以打开`index.php`进行和机器人聊天做测试。


## 7. 调用聊天机器人

> 如果希望在自己的应用里面调用聊天机器人我们可以这样调用它的api `api.php?requestType=talk&input=你好`

## 8. 关于匹配规则

> 修改之后的匹配规则 :

```PHP
* ---> (\S+)
# ---> \S+
_ ---> .*
= ---> \S*
```

## 9. 关于AIML标签

> 本聊天机器人的`aiml`标签和标准`aiml`标签有所不同，我根据需求对标签做了一些个性化，我们可以在[AIML.MD](AIML.md)文件里面查到更多本聊天机器人所支持的标签。


## 10. 关于api.php的返回数据


```json
{
    "status": "success",
    "type": "talk",
    "message": "haha ...",
    "data": {
        "arr1": {
            "name11": "value11",
            "name12": "value12"
        },
        "arr2": {
            "name21": "value21",
            "name22": "value22"
        }
    }
}
```

> 当你访问 `api.php?requestType=talk&userInput=haha` 的时候能得到以上`json`数据 .

```xml
<category>
    <pattern>haha</pattern>
    <template>
        <data name="arr1">
            <attr name="name11">value11</attr>
            <attr name="name12">value12</attr>
        </data>
        <data name="arr2">
            <attr name="name21">value21</attr>
            <attr name="name22">value22</attr>
        </data>
        haha ...
    </template>
</category>
```

## 11. 关于userId

> 当你给`api.php`以`GET`方式发送请求的时候带了`userId`参数， 这个参数会当作用户的唯一标识。若你没给这个参数，程序会用用户的ip当作唯一标示。

```php
$userId = isset($_REQUEST['userId']) ? $_REQUEST['userId'] : $_SERVER['REMOTE_ADDR'];
```

## 12. 关于多个chatbot

> 如果你把'chatbot/Config.php'文件里面的`multiChatbot`设置成`false`，所有的用户会分享一个`default`的`chatbot`。当你设置成`true`的时候每个用户都会拥有独立的`chatbot`,这些`chatbot`会吧`userId`作为唯一标示，每个用户给自己的`chatbot`设置相关名字，性别，年龄之类的属性。当你在写一个语音助手之类的时候多个`chatbot`功能会特别好用，因为用户可以给自己的语音助手设置名字了。

```php
$user = $this->getUser($this->_unique);
if ($this->_config->multiChatbot){
    $bot = $this->getBot($this->_unique);
} else {
    $bot = $this->getBot("default");
}
```

## 13. 关于userInfo和botInfo

> userInfo和botInfo是指用户和聊天机器人的一些属性，例如姓名，年龄，性别等等。用户可以改变这些属性，当然这是我们当写aiml语料库的时候通过一些标签来实现的，我们可以从[AIML.MD](AIML.md) 学习set, get, del, user, bot等有关标签。

```xml
<category>
    <pattern>my name is *</pattern>
    <template>
        ok , your name is
        <star/>
        <set type="user" name="name">
            <star/>
        </set>
    </template>
</category>

<category>
    <pattern>what is my name</pattern>
    <template>
        oh , your name is
        <get type="user" name="name"/>
        , i remembered it last time ...
    </template>
</category>
```

## 14. 关于数据库

> `log` 存储日志的表 . 

> `property` 是存储`user`和`bot`的有关属性的表 , 相当于我们聊天机器人的脑袋，帮我们记住一些属性。 set ,get, del, user, bot 等标签是用来操作这个表的 .

> `data` 表用来存储用户的输入，机器人的回复，`input `, `that`, `topic` 等标签会操作`Parser::$_data`数组，每次程序开始运行的时候程序会从这个表加载数据到这个数组里，然后程序要结束的时候再会存储到这个表里面。表的唯一标示还是`userId`。



---

#  Enjoy it
