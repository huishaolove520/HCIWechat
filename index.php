<?php

define("TOKEN", "weixin");
$wechatObj = new hciWechatCallbackapi();
if (isset($_GET['echostr'])) {
    $wechatObj->valid();
}else{
    $wechatObj->responseMsg();
}

class hciWechatCallbackapi
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            
            //获得收到信息的类型
            $msgType = trim($postObj->MsgType);
            
            //根据不同信息类型处理
            switch ($msgType) {
                case "event":
                    $this->recieveEvent($postObj);
                    break;
                
                case "text":
                    $this->recieveText($postObj);
                    break;

                default:
                    $this->showHelpInfo($postObj);
                    break;
            }

            
        }else{
            echo "";
            exit;
        }
    }

    //对于获取时间信息的处理
    public function recieveEvent($object)
    {
        switch ($object->Event) {
            case "subscribe":
                $content = "欢迎关注HCI\n\n".
                   "请输入数字1~3:\n:".
                   "1.HCI相关信息\n".
                   "2.正方系统信息查询\n". 
                   "3.学习文章推荐";

                $resultStr = $this->transmitText($object, $content);
                break;
            
            case "unsubcribe":
                $content = "欢迎你下次关注HCI";

                $resultStr = $this->transmitText($object, $content);
                break;

            default:
                $resultStr = "";
                break;
        }

        echo $resultStr;
    }

    //对于获取文本信息的处理
    public function recieveText($object)
    {
            $content = trim($object->Content);
            $content = explode("@", $content);
            if (count($content) <= 1) {
                  switch ($content) {
                      case '1':
                         $resultStr = $this->showHciFile($object);
                         break;
                
                      default:
                         $resultStr = $this->showHelpInfo($object);
                         break;
                    }
                    echo $resultStr;
            }else{
                  echo $this->login($object, $content);
            }
    }
    
    //显示HCI的相关信息
    public function showHciFile($object)
    {
            $content = "[HCI微博]：华农HCI\n
                        [HCI邮箱]：scauhci@sina.cn\n
                        [HCI官网]：scauhci.org\n";

            return $this->transmitText($object, $content);
    }

    //显示帮助信息
    public function showHelpInfo($object)
    {
        $content = "请输入数字1~3:\n".
                   "1.HCI相关信息\n".
                   "2.正方系统信息查询\n". 
                   "3.学习文章推荐" ;

        return $this->transmitText($object, $content);
    }

    //显示登录信息
    public function showLoginMessage($object)
    {
        $content = "请发送 学号@密码 进行验证登陆";
        return $this->transmitText($object, $content);
    }

    //显示推荐博客信息
    public function showRecommendBlogs()
    {

    }

    //登录功能
    public function login($object, $user)
    {
        $id = $user[0];
        $pwd = $user[1];
        $content = $id."@".$pwd;
        return $this->transmitText($object, $content);
    }

    //传输文本
    public function transmitText($object, $content)
    {
            $fromUserName = $object->FromUserName;
            $toUserName = $object->ToUserName;
            $time = time();
            $msgType = "text";
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
            $result = sprintf($textTpl, $fromUserName, $toUserName, $time, $msgType, $content);
            return $result;
    }
}
?>