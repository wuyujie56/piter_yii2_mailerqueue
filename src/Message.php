<?php
namespace piter\mailerqueue;

use Yii;

class Message extends \yii\swiftmailer\Message
{
    public function queue()
    {
        $redis = Yii::$app->redis;
        if (empty($redis)) {
            throw new \yii\base\InvalidConfigException('redis not found in config.');
        }
        // 0 - 15  select 0 select 1
        // db => 1
        $mailer = Yii::$app->mailer;
        if (empty($mailer) || !$redis->select($mailer->db)) {
            throw new \yii\base\InvalidConfigException('db not defined.');
        }
        $message = [];
        $message['from'] = array_keys($this->from);
        $message['to'] = array_keys($this->getTo());
//        var_dump($message,$this->from);exit;
        $message['cc'] = $this->getCc() ? array_keys($this->getCc()) : null;
        $message['bcc'] = $this->getBcc() ? array_keys($this->getBcc()) : null;
        $message['reply_to'] = $this->getReplyTo() ? array_keys($this->getReplyTo()) : null;
        $message['charset'] = $this->getCharset() ? array_keys($this->getCharset()) : null;
//        var_dump($this->getSubject());exit;
        $message['subject'] = $this->getSubject() ? $this->getSubject() : null;
        $parts = $this->getSwiftMessage()->getChildren();
        if (!is_array($parts) || !sizeof($parts)) {
            $parts = [$this->getSwiftMessage()];
        }
        foreach ($parts as $part) {
            if (!$part instanceof \Swift_Mime_Attachment) {
                switch($part->getContentType()) {
                    case 'text/html':
                        $message['html_body'] = $part->getBody();
                        break;
                    case 'text/plain':
                        $message['text_body'] = $part->getBody();
                        break;
                }
                if (!$message['charset']) {
                    $message['charset'] = $part->getCharset();
                }
            }
        }
        return $redis->rpush($mailer->key, json_encode($message));
    }
}

