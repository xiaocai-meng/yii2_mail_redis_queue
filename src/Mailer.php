<?php
namespace redisMail\mailQueue;
use Yii;

class Mailer extends \yii\swiftmailer\Mailer
{   
    //调用compose方法时候实例化指定的message类
    public $messageClass = 'redisMail\mailQueue\Message';
    //redis键名
    public $key = 'mails';
    //redis库
    public $db = '1';
    
    public function process()
    {
        $redis = Yii::$app->redis;
        $mailer = Yii::$app->mailer;
        if ($redis->select($mailer->db) && $messages = $redis->lrange($mailer->key, 0, -1))
        {
            $messageObj = new Message();
            foreach ($messages as $message)
            {
                $message = json_decode($message, TRUE);
                //print_r($message);exit;
                if (empty($message) || !$this->setMessage($messageObj, $message))
                {
                    throw new \yii\web\ServerErrorHttpException('message error');
                }
                if ($messageObj->send())
                {
                    $redis->lrem($this->key, 1, json_encode($message));
                }
            }
        }
        return TRUE;
    }

    public function setMessage($messageObj, $message)
    {
        if (!empty($message['from']) || !empty($message['to']) )
        {
            $messageObj->setFrom($message['from'])->setTo($message['to']);
            if (!empty($message['cc']))
            {
                $messageObj->setCc($message['cc']);
            }
            if (!empty($message['bcc']))
            {
                $messageObj->setBcc($message['bcc']);
            }
            if (!empty($message['reply_to']))
            {
                $messageObj->setReplyTo($message['reply_to']);
            }
            if (!empty($message['charset']))
            {
                $messageObj->setCharset($message['charset']);
            }
            if (!empty($message['subject']))
            {
                $messageObj->setSubject($message['subject']);
            }
            if (!empty($message['html_body']))
            {
                $messageObj->setHtmlBody($message['html_body']);
            }
            if (!empty($message['text_body']))
            {
                $messageObj->setTextBody($message['text_body']);
            }
            return TRUE;
        }
        return FALSE;
    }
}