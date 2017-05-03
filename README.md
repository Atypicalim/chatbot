#  基于AIML的PHP聊天天机器人

## 0. 介绍

> 该聊天机器人是参考AIML 2.5和[Program-P](https://github.com/pe77/Program-P)而写成的，适用于UTF-8编码的，单词后缀根据不同的时态而改变的那些语言。感兴趣的朋友可以自己研究学习一下。

## 1. 下载安装

> `git clone https://github.com/kompasim/chatbot.git`，下载之后完成下面那些步骤就可以直接上传到自己的服务器了。

## 2. 数据库的配置

> 数据库用到了MySQL，可以在`chatbot`文件爱你家里面看到`Config.php`配置文件，并且在里面填写数据库有关信息。

## 3. 导入数据库备份文件

> 把根目录里面的`chatbot.sql`导入到数据库，里面是存储聊天机器人的配置信息，请求记录等信息的表。

## 4. 添加个性化语料库

> 我们把`aiml/chatbot.aiml`打开编辑或者新建一个`aiml`文件，添加我们的性化语料库，再把新的`aiml`文件include到`chatbot.aiml`。

## 5. 关于AIML文件

> * `aiml` 语料库文件都要放在根目录里面的`aiml`文件夹下面。`chatbot.aiml`值入口文件，里面的`aiml`根标签里面可以包含多个`category`标签和一个`default`标签，多个`include`标签。

> * 其他`aiml`文件在`aiml`根标签里面必须先包含`topic`标签，里面再包含多个`category`和一个`default`标签，不能有`include`标签。


## 6. 测试聊天机器人

> 打开`imdex.php`之后可以测试我们刚刚添加的语料库。

## 7. 调用api

> 如果希望在公众号或者自己的APP里面调用聊天机器人我们可以这样调用它的api `api.php?requestType=talk&input=你好`

## 8. 关于匹配规则

> 修改之后的匹配规则 :

```PHP
* ---> (\S+)
# ---> \S+
_ ---> .*
= ---> \S*
```

## 9. 关于AIML标签

> * 本聊天机器人的`aiml`标签和标准`aiml`标签有所不同，我们可以在[AIML.MD](AIML.md)文件里面查到更多本聊天机器人所支持的标签。


## 10. 项目github地址

> [https://github.com/kompasim/chatbot](https://github.com/kompasim/chatbot)

![chatbot](./web/img/demo.png)