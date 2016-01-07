<?php
namespace Jack\System;
class Csrf
{


    protected $timeout = 300;


    public function csrf()
    {
        $token = $this->generateToken();

        return "<input type=\"hidden\" name=\"csrf\" value=\"$token\" />";
    }

    protected function calculateHash()
    {
        return sha1(implode('', $_SESSION['csrf']));
    }


    public function randomString($len = 10)
    {
        $rString = '';
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $charsTotal = strlen($chars);
        for ($i = 0; $i < $len; $i++) {
            $rInt = (integer)mt_rand(0, $charsTotal);
            $rString .= substr($chars, $rInt, 1);
        }

        return $rString;
    }

    public function generateToken()
    {
        $_SESSION['csrf'] = array();
        $_SESSION['csrf']['time'] = time();
        $_SESSION['csrf']['salt'] = $this->randomString(32);
        $_SESSION['csrf']['sessid'] = session_id();
        $_SESSION['csrf']['ip'] = $_SERVER['REMOTE_ADDR'];
        $hash = $this->calculateHash();

        return base64_encode($hash);
    }

    protected function checkTimeout($timeout = NULL)
    {
        if (!$timeout) {
            $timeout = $this->timeout;
        }

        return ($_SERVER['REQUEST_TIME'] - $_SESSION['csrf']['time']) < $timeout;
    }

    public function checkToken($timeout = 500)
    {


        if (isset($_SESSION['csrf'])) {
            if (!$this->checkTimeout($timeout)) {
                return FALSE;
            }

            if (session_id()) {
                $Csrf = isset($_POST['csrf']);


                if ($Csrf) {

                    $tokenHash = base64_decode($_POST['csrf']);
                    $generatedHash = $this->calculateHash();
                    if ($tokenHash and $generatedHash) {

                        return $tokenHash == $generatedHash;
                    }
                }
            }
        }


        throw new \Exception("CSRF Failed");
    }

}