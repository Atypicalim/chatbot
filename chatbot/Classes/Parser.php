<?php

require_once "Storage.php";
require_once dirname(__FILE__) . "/../Classes/Context.php";

class Parser
{
    static private $_user;
    static private $_bot;

    static private $_parserInfo;

    static private $_domDoc;
    static private $_domXPath;

    static private $_input;
    static private $_star = array();

    static private $_data;
    static private $_storage;

    static private $_responseData = array();

    static function GetResponseData()
    {
        return self::$_responseData;
    }

    static function init($parserInfo)
    {
        LOG && print("parser init ...\n");
        return self::$_parserInfo = $parserInfo;
    }

    /**
     * Get user question, parse and response
     * @param \User $user - User who is asking
     * @param \Bot $bot - bot are replying
     * @param string $input - User input
     * @return string - response
     */
    static function Parse(User $user, Bot $bot, $input)
    {
        /*
        $x = preg_match('/^(.*)voce tem irma(.*)$/i', "oi p voce tem irma");
        print_r($x);
        die();
        */

        // set bot and user
        self::$_user = $user;
        self::$_bot = $bot;

        // create and load xml handler
        self::$_domDoc = new \DOMDocument();
        self::$_domDoc->preserveWhiteSpace = false;
        self::$_domDoc->loadXML(Context::getAimlString($user, $bot, $_REQUEST['userInput']));
        self::$_domXPath = new \DomXPath(self::$_domDoc);

        // includes
        self::$_domDoc = self::includeMerge(self::$_domDoc);

        // clean up input
        $input = self::cleanUpPattern($input);
        LOG && print "input after pre parse : " . $input . "\n";


        // create a storage data instance
        self::$_storage = new Storage($user->getUnique());
        self::$_data = self::$_storage->Load();
        // set default topics, that, input
        isset(self::$_data['topics']) || self::$_data['topics'] = array();
        isset(self::$_data['that']) || self::$_data['that'] = array();
        isset(self::$_data['input']) || self::$_data['input'] = array();

        // get response
        $responseString = self::getResponseString($input);

        //
        // self::$_dataStorage->Clear();

        // set response for 'that'
        self::addThatToData($responseString);

        // save temp data
        self::$_storage->Save(self::$_data);

        // return response
        return $responseString;
    }

    // check if have any 'include' tag for mixin propose
    static private function includeMerge($domDoc)
    {
        $xpathQuery = '//include';
        $includeDomDocs = array();
        // get inlude nodes
        foreach (self::$_domXPath->query($xpathQuery, $domDoc) as $includeNode) {
            if ($includeNode->hasAttributes()) {
                foreach ($includeNode->attributes as $attr) {
                    switch ($attr->nodeName) {
                        case 'file':
                            $fileFullName = self::$_parserInfo['aimlDir'] . '/' . $attr->nodeValue;
                            // check if the file exists
                            if (!file_exists($fileFullName))
                                throw new \Exception("Include file AIML not found in : " . $fileFullName);
                            //
                            // read aiml file
                            $aimlString = file_get_contents($fileFullName);
                            // create domdoc
                            $includeDoc = new \DOMDocument;
                            $includeDoc->loadXML($aimlString);
                            // save
                            $includeDomDocs[] = $includeDoc;
                            break;
                    }
                }
            }
        }
        // import all include docs
        foreach ($includeDomDocs as $includeDoc) {
            // load topcs, category(with subtags too)
            $includeTags = array('category', 'topic');
            $domXPath = new \DomXPath($includeDoc);
            foreach ($includeTags as $tag) {
                foreach ($domXPath->query('/aiml/' . $tag, $includeDoc) as $node) {
                    // get node with its children
                    $node = $domDoc->importNode($node, true);
                    // move the node to domDocs root element
                    $domDoc->documentElement->appendChild($node);
                }
            }
        }
        return $domDoc;
    }

    static private function getResponseString($input)
    {
        $responseString = "";
        // set user input to data
        self::addInputToData($input);
        // if topic exist, try search inside topic before ,else search aiml root category
        if (count(self::$_data['topics']) > 0) {

            LOG && print "\ntopics : ";
            LOG && print_r(self::$_data['topics']);
            LOG && print "\n";

            $xpathQuery = "./aiml/topic[@name='" . self::getTopicFromData(0) . "']";
            if ($oneTopic = self::$_domXPath->query($xpathQuery, self::$_domDoc)->item(0)) {
                // find corresponding category
                if ($category = self::searchCategory($oneTopic)) {
                    LOG && print "found a category ...\n";
                    // pre-process template tag and set response
                    $responseString = self::processDomElement(
                        self::getAllTagsByName($category, './template', true)
                    );
                }
            }
        }
        if ($responseString == '') {
            //  process template if  match a cateegory in aiml root tag, else search all topics
            if ($category = self::searchCategory(self::$_domXPath->query("./aiml", self::$_domDoc)->item(0))) {
                // pre-process template tag and set response
                $responseString = self::processDomElement(
                    self::getAllTagsByName($category, './template', true)
                );
            } else {
                //
                $xpathQuery = "./aiml/topic";
                foreach (self::$_domXPath->query($xpathQuery, self::$_domDoc) as $oneTopic) {
                    // find corresponding category
                    if ($category = self::searchCategory($oneTopic)) {
                        // pre-process template tag and set response
                        $responseString = self::processDomElement(
                            self::getAllTagsByName($category, './template', true)
                        );
                        if ($responseString != "") {
                            break;
                        }
                    }
                }
            }
        }
        // if response string is empty return default in root aiml tag

        if ($responseString == "" && count(self::$_data['topics']) > 0) {
            $xpathQuery = "./aiml/topic[@name='" . self::getTopicFromData(0) . "']/default";
            if ($default = self::$_domXPath->query($xpathQuery, self::$_domDoc)->item(0)) {
                $responseString = self::processDomElement($default);
            }
        }
        if ($responseString == "") {
            if ($default = self::$_domXPath->query('./aiml/default', self::$_domDoc)->item(0)) {
                $responseString = self::processDomElement($default);
            } else {
                $responseString = "no category matched and no default tag ...";
            }
        }

        return $responseString;
    }

    static private function processDomElement(DOMElement $template)
    {
        // compile think
        self::compileThink($template);

        // compile system
        self::compileSystem($template);

        // compile srai
        self::compileSrai($template);

        // compile random
        self::compileRandom($template);

        // compile input
        self::compileInput($template);

        // compile star pattern
        self::compileStar($template);

        // compile set
        self::compileSet($template);

        // compile get
        self::compileGet($template);

        // compile user
        self::compileUser($template);

        // compile bot
        self::compileBot($template);

        // compile condition
        self::compileCondition($template);

        // compile lowercase
        self::compileLowercase($template);

        // compile uppercase
        self::compileUppercase($template);

        // compile del
        self::compileDel($template);

        // compile response data | <data>
        self::CompileResponseData($template);


        return (string)$template->nodeValue;
    }

    static private function compileStar(DOMElement $templateNode)
    {

        // search for star
        if ($stars = self::getAllTagsByName($templateNode, './star')) {
            foreach ($stars as $starNode) {
                $index = 0;
                // get index
                if ($starNode->getAttribute('index') != '') {
                    $index = intval($starNode->getAttribute('index'));
                    $index--;
                }
                $value = self::getStarFromParser($index);
                // get value
                if ($value == false) {
                    $value = '';
                }
                // replace child for the value
                $starNode->parentNode->replaceChild(self::$_domDoc->createTextNode($value), $starNode);
            }
        }
    }


    static private function CompileResponseData(DOMElement $node)
    {
        // check data tag
        if ($datas = self::getAllTagsByName($node, './data')) {
            foreach ($datas as $data) {
                if ($data->getAttribute("name") != '') {
                    $dataArr = array();
                    // loop all values
                    foreach ($data->childNodes as $attrTag) {
                        if ($attrTag->nodeName == "attr") {
                            $name = $attrTag->getAttribute("name");
                            $attributeValue = $attrTag->getAttribute('value');
                            $nodeValue = self::processDomElement($attrTag);
                            $value = $attributeValue != '' ? $attributeValue : ($nodeValue != '' ? $nodeValue : '');
                            if ($name != '' && $value != '') {
                                $dataArr[$name] = $value;
                            }
                        }
                    }
                    self::$_responseData[$data->getAttribute("name")] = $dataArr;
                }
                // clear node, get data only by method
                $node->removeChild($data);
            }
        }
    }

    static private function compileLowercase(DOMElement $node)
    {
        // check lowercase tag
        if ($lowers = self::getAllTagsByName($node, './lowercase')) {
            foreach ($lowers as $lowerTag) {
                // load value from db
                $newNode = self::$_domDoc->createTextNode(
                    strtolower(
                        self::processDomElement($lowerTag)
                    )
                );
                // replace child for the value
                $node->replaceChild($newNode, $lowerTag);
            }
        }
    }

    static private function compileUppercase(DOMElement $node)
    {
        // check lowercase tag
        if ($uppers = self::getAllTagsByName($node, './uppercase')) {
            foreach ($uppers as $upperTag) {
                // load value from db
                $newNode = self::$_domDoc->createTextNode(
                    strtoupper(
                        self::processDomElement($upperTag)
                    )
                );

                // replace child for the value
                $node->replaceChild($newNode, $upperTag);
            }
        }
    }


    static private function compileThink(DOMElement $node)
    {
        if ($thinkNodes = self::getAllTagsByName($node, './think')) {
            foreach ($thinkNodes as $think) {
                // process $think
                self::processDomElement($think);
                // remove think node
                $node->removeChild($think);
            }
        }
    }

    static private function compileSystem(DOMElement $node)
    {
        if ($SytemNodes = self::getAllTagsByName($node, './system')) {
            foreach ($SytemNodes as $system) {
                // process $system
                $output = eval(self::processDomElement($system));
                $newNode = self::$_domDoc->createTextNode(implode("\n", $output));
                // replace child for the value
                $node->replaceChild($newNode, $system);
            }
        }
    }


    static private function compileSet(DOMElement $node)
    {
        if ($sets = self::getAllTagsByName($node, 'set')) {
            foreach ($sets as $setNode) {
                $type = $setNode->getAttribute('type');
                $name = $setNode->getAttribute('name');
                $attributeValue = $setNode->getAttribute('value');
                $nodeValue = self::processDomElement($setNode);
                $human = $type == 'user' ? self::$_user : ($type == 'bot' ? self::$_bot : '');
                $value = $attributeValue != '' ? $attributeValue : ($nodeValue != '' ? $nodeValue : '');
                LOG && print "set " . $name . " to " . $type . " , value is " . $value . "\n";
                if ($name != "" && $human != '' && $value != '') {
                    $human->setProp($name, $value);
                    $human->saveAllProps();
                }
                $node->replaceChild(self::$_domDoc->createTextNode($value), $setNode);
            }
        }
    }

    static private function compileGet(DOMElement $node)
    {
        if ($gets = self::getAllTagsByName($node, 'get')) {
            foreach ($gets as $getNode) {
                $type = $getNode->getAttribute('type');
                $name = $getNode->getAttribute('name');
                $human = $type == 'user' ? self::$_user : ($type == 'bot' ? self::$_bot : '');
                $res = '';
                LOG && print "get " . $name . " from " . $type . "\n";
                if ($name != "" && $human != '') {
                    $res = $human->getProp($name);
                }
                $node->replaceChild(self::$_domDoc->createTextNode($res), $getNode);
            }
        }
    }

    static private function compileDel(DOMElement $node)
    {
        if ($dels = self::getAllTagsByName($node, 'del')) {
            foreach ($dels as $delNode) {
                $type = $delNode->getAttribute('type');
                $name = $delNode->getAttribute('name');
                $human = $type == 'user' ? self::$_user : ($type == 'bot' ? self::$_bot : '');
                LOG && print "del " . $name . " from " . $type . "\n";
                if ($name != "" && $human != '') {
                    $res = $human->getProp($name);
                    $human->delProp($name);
                    $human->saveAllProps();
                }
                $node->replaceChild(self::$_domDoc->createTextNode($res), $delNode);
            }
        }
    }


    static private function compileUser(DOMElement $node)
    {
        if ($users = self::getAllTagsByName($node, 'user')) {
            foreach ($users as $userNode) {
                $name = $userNode->getAttribute('name');
                $attributeValue = $userNode->getAttribute('value');
                $nodeValue = self::processDomElement($userNode);
                $value = $attributeValue != '' ? $attributeValue : ($nodeValue != '' ? $nodeValue : '');
                $res = '';
                if ($name == "") {
                    //
                } else {
                    if ($value == '') {
                        // get
                        $res = self::$_user->getProp($name);
                    } else {
                        // save
                        self::$_user->setProp($name, $value);
                        self::$_user->saveAllProps();
                        $res = $value;
                    }
                }
                $node->replaceChild(self::$_domDoc->createTextNode($res), $userNode);
            }
        }
    }

    static private function compileBot(DOMElement $node)
    {
        if ($bots = self::getAllTagsByName($node, 'bot')) {
            foreach ($bots as $botNode) {
                $name = $botNode->getAttribute('name');
                $attributeValue = $botNode->getAttribute('value');
                $nodeValue = self::processDomElement($botNode);
                $value = $attributeValue != '' ? $attributeValue : ($nodeValue != '' ? $nodeValue : '');
                $res = '';
                if ($name == "") {
                    //
                } else {
                    if ($value == '') {
                        // get
                        $res = self::$_bot->getProp($name);
                    } else {
                        // save
                        self::$_bot->setProp($name, $value);
                        self::$_bot->saveAllProps();
                        $res = $value;
                    }
                }
                $node->replaceChild(self::$_domDoc->createTextNode($res), $botNode);
            }
        }
    }


    static private function compileRandom(DOMElement $domNode)
    {
        if ($randomNodes = self::getAllTagsByName($domNode, './random')) {
            foreach ($randomNodes as $rNode) {
                // check if li tag exist
                if ($liNodes = self::getAllTagsByName($rNode, './li')) {
                    $lis = array();
                    foreach ($liNodes as $lNode) {
                        $lis[] = $lNode;
                    }
                    // select li node
                    $selectedLi = $lis[array_rand($lis, 1)];

                    // remove all others node from random
                    foreach ($lis as $lnode)
                        if (!$lnode->isSameNode($selectedLi))
                            $rNode->removeChild($lnode);
                    //
                    // change random node for selectedLi value
                    $domNode->replaceChild(
                        self::$_domDoc->createTextNode(self::processDomElement($selectedLi)),
                        $rNode
                    );

                }
            }
        }
    }

    static private function compileSrai(DOMElement $node)
    {
//
        if ($srais = self::getAllTagsByName($node, './srai')) {
            foreach ($srais as $srai) {
//                // fix for loop error in srai and that in same category
                self::addThatToData("");
//                // re-find another response for srai and replace
                $newNode = self::$_domDoc->createTextNode(self::getResponseString(self::processDomElement($srai)));
                $node->replaceChild($newNode, $srai);
            }
        }

    }

    static private function compileInput(DOMElement $node)
    {
        // search for input tag
        if ($inputs = self::getAllTagsByName($node, './input')) {
            foreach ($inputs as $inputNode) {
                $index = 0;
                // get index
                if ($inputNode->hasAttributes() && $inputNode->getAttribute('index') != '') {
                    $index = intval($inputNode->getAttribute('index'));
//                    $index--;
                }
                // get value
                $value = self::getInputFromData($index);
                if ($value == false) {
                    $value = '';
                }
                // replace child for the value
                $node->replaceChild(self::$_domDoc->createTextNode($value), $inputNode);
            }
        }
    }


    static private function compileCondition(DOMElement $node)
    {
        if ($conditions = self::getAllTagsByName($node, './condition')) {
            foreach ($conditions as $condition) {
                $res = "";
                $type = $condition->getAttribute('type');
                $name = $condition->getAttribute('name');
                $valueInCondition = $condition->getAttribute('value');
                $lis = self::getAllTagsByName($condition, 'li');
                if ($name == '' || $type == '' || ($lis == false && $valueInCondition == '')) {
                    continue;
                } else if ($valueInCondition != '' && $lis == false) {
                    // 若condition包含value属性
                    if ($type == "user") {
                        // check user prop
                        if (self::$_user->getProp($name) == $valueInCondition) {
                            $res = self::processDomElement($condition);
                        }
                    } else if ($type == "bot") {
                        // check bot prop
                        if (self::$_bot->getProp($name) == $valueInCondition) {
                            $res = self::processDomElement($condition);
                        }
                    }
                } else if ($valueInCondition == '' && $lis != false) {
                    // 若condition包含li元素
                    foreach ($lis as $li_1) {
                        $lisForRandom = array();
                        $valueInLi = $li_1->getAttribute('value');
                        // 若li元素包含value属性则检测条件
                        if ($valueInLi != '') {
                            if ($type == "user") {
                                if (self::$_user->getProp($name) == $valueInLi) {
                                    $res = self::processDomElement($condition);
                                    break;
                                }
                            } else if ($type == "bot") {
                                if (self::$_bot->getProp($name) == $valueInLi) {
                                    $res = self::processDomElement($condition);
                                    break;
                                }
                            }
                        } else {
                            // 若li元素不包含value属性则放到随机选择返回的数组
                            $lisForRandom[] = $li_1;
                        }
                        // 若res是空的，则随机选一个li返回他的 node value
                        if ($res == '') {
                            $selectedLi = $lisForRandom[array_rand($lisForRandom, 1)];
                            $res = self::processDomElement($selectedLi);
                        }
                    }
                }
                // replace condition by value of condition node value
                $newNode = self::$_domDoc->createTextNode($res);
                $node->replaceChild($newNode, $condition);
            }
        }
    }


    /**
     * Get valid category for input
     * @param DOMElement
     * @return Category|False
     */
    static private function searchCategory(DOMElement $domNode)
    {
        // check if categories exist
        if (!$categories = self::getAllTagsByName($domNode, './category')) {
            return false;
        }
        // check if any pattern in default patterns
        foreach ($categories as $category) {
            // check patterns
            if (self::CheckPattern($category)) {
                // set topic
                self::addTopicToData($domNode);
                return $category;
            }
        }
        return false;
    }

    /**
     * Check if pattern in category node is ok
     * @return boolean - if ok or not
     */
    static private function CheckPattern(DOMElement $category)
    {
        $patterns = self::getAllTagsByName($category, './pattern');
        $template = self::getAllTagsByName($category, './template', true);
        // search for any math pattern
        foreach ($patterns as $pattern) {
            if (self::ValidatePattern(self::$_input, $pattern->nodeValue, $template)) {
                // looking for that tag
                if ($that = self::getAllTagsByName($category, 'that', true)) {
                    if (count(self::$_data['that']) > 0) {
                        // check if same last response
                        return
                            self::ValidatePattern(
                                self::getThatFromData(0),
                                $that->nodeValue,
                                $template
                            );
                    } else {
                        // have a that tag but no last responses
                        return false;
                    }
                } else {
                    // no have that tag
                    return true;
                }
            }
        }
        // no have pattern tag
        return false;
    }


    static private function ValidatePattern($input, $pattern, $template)
    {
        $old_input = $input;
        $old_pattern = $pattern;

        // clean
        $input = trim($input);
        $input = strtolower($input);
        $input = self::cleanUpPattern($input);


        //
        $pattern = trim($pattern);
        $pattern = strtolower($pattern);
        // replace pattern to protect from clean up
        $pattern = str_replace(' * ', 'SpaceStarSpace', $pattern);
        $pattern = str_replace('* ', 'StarSpace', $pattern);
        $pattern = str_replace(' *', 'SpaceStar', $pattern);
        $pattern = str_replace('*', 'Star', $pattern);
        $pattern = str_replace(' # ', 'SpaceWellSpace', $pattern);
        $pattern = str_replace(' #', 'SpaceWell', $pattern);
        $pattern = str_replace('# ', 'WellSpace', $pattern);
        $pattern = str_replace('#', 'Well', $pattern);
        $pattern = str_replace(' _ ', 'SpaceLineSpace', $pattern);
        $pattern = str_replace('_ ', 'LineSpace', $pattern);
        $pattern = str_replace(' _', 'SpaceLine', $pattern);
        $pattern = str_replace('_', 'Line', $pattern);
        $pattern = str_replace(' = ', 'SpaceEqualSpace', $pattern);
        $pattern = str_replace('= ', 'EqualSpace', $pattern);
        $pattern = str_replace(' =', 'SpaceEqual', $pattern);
        $pattern = str_replace('=', 'Equal', $pattern);
        // clean
        $pattern = self::cleanUpPattern($pattern);


        // restore pattern
        $pattern = str_replace('SpaceStarSpace', '\s(\S+)\s', $pattern);
        $pattern = str_replace('StarSpace', '(\S+)\s', $pattern);
        $pattern = str_replace('SpaceStar', '\s(\S+)', $pattern);
        $pattern = str_replace('Star', '(\S+)', $pattern);

        $pattern = str_replace('SpaceWellSpace', '\s\S+\s', $pattern);
        $pattern = str_replace('SpaceWell', '\s\S+', $pattern);
        $pattern = str_replace('WellSpace', '\S+\s', $pattern);
        $pattern = str_replace('Well', '\S+', $pattern);

        $pattern = str_replace('SpaceLineSpace', '\s.*\s', $pattern);
        $pattern = str_replace('LineSpace', '.*\s', $pattern);
        $pattern = str_replace('SpaceLine', '\s.*', $pattern);
        $pattern = str_replace('Line', '.*', $pattern);

        $pattern = str_replace('SpaceEqualSpace', '\s\S*\s', $pattern);
        $pattern = str_replace('EqualSpace', '\S*\s', $pattern);
        $pattern = str_replace('SpaceEqual', '\s\S*', $pattern);
        $pattern = str_replace('Equal', '\S*', $pattern);

        // validate
        $regex = '/^' . $pattern . '$/i';
        $is_match = preg_match($regex, $input, $matches) ? true : false;


//        LOG && print "input in validate pattern is : " . $input . "\n";
//        LOG && print "regex in validate pattern is : " . $regex . "\n";


        // set star(s)
        if (count($matches) > 1) {

            LOG && print "input in validate pattern is : " . $input . "\n";
            LOG && print "regex in validate pattern is : " . $regex . "\n";
            LOG && print "\nmatches : ";
            LOG && print_r($matches);
            LOG && print "\n";

            array_shift($matches);
            self::$_star = $matches;
            self::compileStar($template);
        }

        if (LOG && $is_match) {
            print("\n\n=======matches=======\ninput : ");
            print_r($input);
            print("\n---------------\npattern : ");
            print_r($old_pattern);
            print("\n---------------\nregex : ");
            print_r($pattern);
            print("\n=====================\n\n");
        }


        // return match
        return $is_match;
    }


    static private function addInputToData($input)
    {
        self::$_input = $input;
        // add to data
        array_push(self::$_data['input'], $input);
        // if array length is more than 10, cut-off
        if (count(self::$_data['input']) > 10) {
            self::$_data['input'] = array_slice(self::$_data['input'], count(self::$_data['input']) - 10, 10);
        }
    }

    static private function addThatToData($lastResponse)
    {
        // add response
        array_push(self::$_data['that'], $lastResponse);
        // if array length is more than 10, cut-off
        if (count(self::$_data['that']) > 10) {
            self::$_data['that'] = array_slice(self::$_data['that'], count(self::$_data['that']) - 10, 10);
        }
    }

    static private function addTopicToData($node)
    {
        if ($node->nodeName != 'topic') {
            self::$_data['topics'] = array();
            return;
        } else {
            // add response
            array_push(self::$_data['topics'], $node->getAttribute('name'));
            // if array length is more than 10, cut-off
            if (count(self::$_data['topics']) > 10) {
                self::$_data['topics'] = array_slice(self::$_data['topics'], count(self::$_data['topics']) - 10, 10);
            }
            return;
        }
    }


    static private function getInputFromData($index = 0)
    {
        if ($index > count(self::$_data['input'])) {
            return false;
        } else {
            $reverseArray = array_reverse(self::$_data['input']);
            return count(self::$_data['input']) == 0 ? false : $reverseArray[$index];
        }
    }

    static private function getThatFromData($index = 0)
    {
        if (count(self::$_data['that']) == 0) {
            return '';
        } else {
            $reverseArray = array_reverse(self::$_data['that']);
            if (count($reverseArray) == 0) {
                return '';
            } else {
                $that = $reverseArray[$index];
                return $that;
            }
        }
    }

    static private function getTopicFromData($index = 0)
    {
        if ($index > count(self::$_data['topics'])) {
            return false;
        } else {
            $reverseArray = array_reverse(self::$_data['topics']);
            return count(self::$_data['topics']) == 0 ? false : $reverseArray[$index];
        }
    }


    static private function getStarFromParser($index = 0)
    {
        if ($index >= count(self::$_star)) {
            return false;
        } else {
            return count(self::$_star) == 0 ? false : self::$_star[$index];
        }
    }


    static public function cleanUpPattern($str)
    {
        $str = self::replaceInvalidCharacter($str);
        $str = self::removeLineBreak($str);
        $str = self::removePunctuation($str);
        return $str;
    }

    static public function replaceInvalidCharacter($str)
    {
        //
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
        $input = str_replace($a, $b, $str);
        return $input;
    }

    static public function removeLineBreak($str)
    {
        $str = preg_replace('/[\n|\r]/', ' ', $str);
        $str = preg_replace("/\s+/", " ", $str);
        return $str;
    }

    static public function removePunctuation($str)
    {
        $str = preg_replace("/[[:punct:]]/", " ", $str);
        $punctuations = array(
            '~',
            '»',
            '«',
            '￥',
            '–',
            '。',
            '،',
            '，',
            '、',
            '＇',
            '：',
            '∶',
            '；',
            '?',
            '‘',
            '’',
            '“',
            '”',
            '〝',
            '〞',
            'ˆ',
            'ˇ',
            '﹕',
            '︰',
            '﹔',
            '﹖',
            '!',
            '﹑',
            '·',
            '¨',
            '…',
            '.',
            '¸',
            ';',
            '´',
            '？',
            '！',
            '～',
            '—',
            'ˉ',
            '｜',
            '‖',
            '＂',
            '〃',
            '｀',
            '@',
            '﹫',
            '¡',
            '¿',
            '﹏',
            '﹋',
            '﹌',
            '︴',
            '々',
            '﹟',
            '#',
            '﹩',
            '$',
            '﹠',
            '&',
            '﹪',
            '%',
            '*',
            '﹡',
            '﹢',
            '﹦',
            '﹤',
            '‐',
            '￣',
            '¯',
            '―',
            '﹨',
            'ˆ',
            '˜',
            '﹍',
            '﹎',
            '+',
            '=',
            '<',
            '­',
            '­',
            '＿',
            '_',
            '﹉',
            '﹊',
            '（',
            '）',
            '〈',
            '〉',
            '‹',
            '›',
            '﹛',
            '﹜',
            '『',
            '』',
            '〖',
            '〗',
            '［',
            '］',
            '《',
            '》',
            '〔',
            '〕',
            '{',
            '}',
            '「',
            '」',
            '【',
            '】',
            '︵',
            '︷',
            '︿',
            '︹',
            '︽',
            '_',
            '﹁',
            '﹃',
            '︻',
            '︶',
            '︸',
            '﹀',
            '︺',
            '︾',
            'ˉ',
            '﹂',
            '﹄',
            '︼',
            '-',
            '\\',
            'ˇ',
            '؟',
            '؛'
        );
        $str = str_replace($punctuations, ' ', $str);
        $str = preg_replace("/\s+/", " ", $str);
        return $str;
    }


    /**
     * Search in node by tag
     * @param DOMElement $domNode
     * @param string $tagName - xpath model
     * @param boolean $getOne - if true, get only one element, not array ([0])
     * @return array<DOMElement>|DOMElement|False
     */
    static private function getAllTagsByName(DOMElement $domNode, $tagName, $getOne = false)
    {
        $arrResponse = array();
        foreach (self::$_domXPath->query($tagName, $domNode) as $node) {
            $arrResponse[] = $node;
        }
        return count($arrResponse) > 0 ? $getOne ? $arrResponse[0] : $arrResponse : false;
    }
}