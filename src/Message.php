<?php
namespace redisMail\mailQueue;
use Yii;

//重写\yii\swiftmailer\Message中的send()方法
class Message extends \yii\swiftmailer\Message
{
    public function Queue()
    {
        $redis = Yii::$app->redis;
        $mailer = Yii::$app->mailer;
        if (!$redis->select($mailer->db)) 
        {
            throw new \yii\base\InvalidConfigException('请在mailer中配置db');
        }
        //抓取邮件信息
        $message = [];
        $message['from'] = $this->getFrom();
        $message['to'] = $this->getTo();
        //Returns the Cc (additional copy receiver) addresses of this message.
        $message['cc'] = $this->getCc();
        //Returns the Bcc (hidden copy receiver) addresses of this message.
        $message['bcc'] = $this->getBcc();
        //Returns the reply-to address of this message.
        $message['reply_to'] = $this->getReplyTo();
        //Sets the character set of this message.
        $message['charset'] = $this->getCharset();
        $message['subject'] = $this->getSubject();

        //获取邮件信息及子信息
        $parts = $this->getSwiftMessage()->getChildren();
        if (!is_array($parts) || !sizeof($parts))
        {
            $parts = [$this->getSwiftMessage()];
        }
        foreach ($parts as $part)
        {
            //$par 是 Swift_MimePart Object 的实例
            if (!$part instanceof \Swift_Mime_Attachment)
            {
                switch ($part->getContentType())
                {
                    case "text/html" :
                        $message['html_body'] = $part->getBody();
                        break;
                    case "text/plain" :
                        $message['text_body'] = $part->getBody();
                        break;
                }
                //body的编码
                if (empty($message['charset']))
                {
                    $message['charset'] = $part->getCharset();
                }
            }
        }
        //print_r($message);
        //exit;
        return $redis->rpush($mailer->key, json_encode($message));
    }
    
}