#  AIML

## 0. 介绍

> 为了适应有些语言的特殊性，该aiml标准基于`AIML2.5`做了一些小改造，适合单词后缀根据不同的时态而改变的那些语言。此标准和[chatbot](https://github.com/kompasim/chatbot)配合使用可以搭建完整的聊天机器人程序。

## 1. 文件组织

> 由一个`chatbot.aiml`文件和多个`fileName.aiml`（其他名字的）aiml文件。

* chatbot.aiml


> 是主入口语料库文件。根标签是aiml,里面可以有多个category标签和一个default标签。

* fileName.aiml

> 次要语料库文件。根标签是`aiml`,里面只能有一个`topic`标签。

## 2. aiml

```xml
<?xml version="1.0" encoding="utf-8"?>
<aiml version="1.0">
</aiml>
```
> 每个aiml文件的基本单元，只能出现一次。chatbot.aiml文件里可以包含多个`category`标签和一个`defaul`t标签其他aiml文件里面只能包含一个`topic`标签。

## include

```xml
<include file="./fileName.aiml"/>
```

> `include`标签只能出现在chatbot.aiml文件的aiml标签里面，可以有多个，用来导入其他aiml文件。

## 4. topic

```xml
<topic name="value">

</topic> 
```

> `topic`标签只能出现在其他aiml文件（`cahtbot.aiml`以外的文件）的`aiml`标签里面。可以包含多个`category`标签。一个文件包含一个topic标签相当于一个文件要写一个主题相关的问答组合（category）。

## 5. category

```xml
<category>
    <pattern>hello1</pattern>
    <pattern>hello2</pattern>
    <pattern>hello3</pattern>
    <that>last message ...</that>
    <template>
        hello ...
    </template>
</category>
```

>  `category`标签是基本的问答单元，也就是表示用户输入和机器人回复的最小单位。可以出现在`aiml`标签和`category`标签里面。可以包含多个`pattern`标签和一个`that`标签。

## 6. default

```xml

<default>
    default message ...
</default>
```

> `default`是没有`category标签`匹配的时候生效的默认标签。可以出现在`aiml`标签和`topic`标签。当里面的内容是表示回复的文本。



## 7. 回复内容

> `default`和`template`标签里面内容是回复给用户的结果，可以包含`think`,`input`,`srai`,`random`,`lowercase`,`uppercase`,`star`,`condition`,`set`,`get`,`dell`,`user`,`bot`等标签。

## 8. think

```xml

<think>
    <set type="user" name="name">Me</set>
</think>
```

> `think`标签里面内容会执行，但返回值不会包含在结果里。

## 9.input

```xml
<input index="2"/>
```

> 表示用户倒数第二次的输入。


## 10. srai

```xml
<srai>用户输入</srai>
```

> 里面内容会当作用户输入重新执行程序，返回结果。


## 11. random

```xml
<random>
    <li>我不太理解你在说什么1  ...</li>
    <li>我不太理解你在说什么2  ...</li>
</random>
```

> 从`li`标签随机选一个返回结果。


## 12. lowercase

```xml
<lowcase>Abcd</lowcase>
```

> 表示把中间的英文变成小写。


## 13. uppercase

```xml
<uppercase>Abcd</uppercase>
```

> 表示把中间的英文变成大写。


## 14. star

```xml
<star index="2"/>
```

> 回去`pattern`标签第二个型号对应的用户输入。

## 15. condition

```xml
<condition type="user" name="name" value="value">你好 </condition>
<condition type="bot" name="name" contains="value"> 你好</condition>
<condition type="user" name="name" exists="value">你好 </condition>
```

```xml
        <condition type="bot" name="用户性别"> 
            <li value="女"> 漂亮阿！</li> 
            <li value="男"> 英俊阿！</li> 
            <li>好看啊</li>
        </condition> 
```

> 只有以上两种写法，type属性表示用户还是机器人，name表示属性名，value表示属性值，当对应用户或机器人的属性和属性值相等时候结果会包含对应标签的内容。


## 16. user

```xml
<!-- set -->
<user name="name1" value="value1">
<user name="name1">value1</user>

<!-- get -->
<user name="name1">
```

> 给用户设置/获取属性，返回值都会包含在结果里，不想包含就得放在`think`标签里。


## 17. bot

```xml
<bot name="name1" value="value1">
<bot name="name1">value1</bot>

<!-- get -->
<bot name="name1">
```

> 给机器人设置/获取属性，返回值都会包含在结果里，不想包含就得放在`think`标签里。


## 18. set

```xml
<set type="bot" name="name1" value="value1"/>
<set type="user" name="name1" value="value1"/>

<set type="bot" name="name1">value1</set>
<set type="user" name="name1">value1</set>
```

> 设置属性，返回值都会包含在结果里，不想包含就得放在`think`标签里。

## 19. get

```xml
<get type="bot" name="name1"/>
<get type="user" name="name1"/>
```

> 获取属性，返回值都会包含在结果里，不想包含就得放在`think`标签里。

## 20. dell

```xml
<del type="bot" name="name1"/>
<del type="user" name="name1"/>
```

> 删除属性，返回值都会包含在结果里，不想包含就得放在`think`标签里。

## 21. data

```xml
<data name="data1">
    <attr name="name1">value1</attr>
    <attr name="name2">value2</attr>
</data>
<data name="data2">
     <attr name="name3" value="value3"/>
     <attr name="name4" value="value4"/>
</data>
```

```php
[data1] => Array( 
    [name1] => "value1",
    [name2] => "value2"
),
[data2] => Array(
    [name3] => "value3",
    [name4] => "value4"
)
```

> 这标签的结果不会添加到回复结果，但是用聊天机器人api中的另一个变量的形式返回给客户，纯粹是用来给用户传递参数用的。


## 22. system

```xml
<category>
    <pattern>system</pattern>
    <template>
        <system>
            date_default_timezone_set('PRC');
            return date('Y/m/d H:i:s');
        </system>
    </template>
</category>
```

> `system`标签会把标签里面的内容用PHP解析器执行，再把返回值包含返回结果中。


## github 地址

> [github/kompasim](https://github.com/kompasim)





