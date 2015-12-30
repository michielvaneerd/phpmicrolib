<?php

namespace PHPMICROLIB\Database;

class Crypt implements \PHPMICROLIB\Database\PDOCrypt {

  private $ivSize;
  private $secret;
  private $cipher;
  private $mode;

  function __construct($secret,
      $cipher = MCRYPT_RIJNDAEL_256,
      $mode = MCRYPT_MODE_CBC) {
    $this->secret = $secret;
    $this->cipher = $cipher;
    $this->mode = $mode;
    $this->ivSize =
      mcrypt_get_iv_size($cipher, $mode);
  }

  private function createIV() {
    return mcrypt_create_iv($this->ivSize,
      MCRYPT_DEV_URANDOM);
  }

  public function encrypt($s) {
    $iv = $this->createIV();
    return base64_encode($iv .
      mcrypt_encrypt($this->cipher,
        $this->secret, $s, $this->mode, $iv));
  }

  public function decrypt($s) {
    $s = base64_decode($s);
    $s = rtrim($s, "\0");
    $iv = substr($s, 0, $this->ivSize);
    $s = substr($s, $this->ivSize);
    return rtrim(
      mcrypt_decrypt($this->cipher,
        $this->secret, $s, $this->mode, $iv));
   }

}