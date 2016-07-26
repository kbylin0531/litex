<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/26/16
 * Time: 2:50 PM
 */

namespace PLite\Util\Helper;
use Vendor\PHPMailer\SMTP;

class Email {
    /**
     * @var SMTP
     */
    private $smtp = null;


    private $smtp_server_host = null;
    private $smtp_server_port = 25;
    private $username = null;
    private $passwd = null;

    private $error = null;

    /**
     * check if smtp server is loginable
     * @param string $hello
     * @return bool
     */
    public function checkSmtp($hello = 'localhost'){
        try {
            $this->smtp or $this->smtp = new SMTP();
            //Connect to an SMTP server
            if ($this->smtp->connect($this->smtp_server_host, $this->smtp_server_port)) {
                //Say hello
                if ($this->smtp->hello($hello)) { //Put your host name in here
                    //Authenticate
                    if ($this->smtp->authenticate($this->username, $this->passwd)) {
                        return true;
                    } else {
                        $this->error = 'Authentication failed: ' . $this->smtp->getLastReply();
                    }
                } else {
                    $this->error = 'HELLO failed: '. $this->smtp->getLastReply();
                }
            } else {
                $this->error = 'Connect failed';
            }
        } catch (\Exception $e) {
            $this->error = 'SMTP error: '. $e->getMessage();
        }
        //Whatever happened, close the connection.
        $this->smtp and $this->smtp->quit(true);
        return false;
    }

    public function send(){
        
    }


}