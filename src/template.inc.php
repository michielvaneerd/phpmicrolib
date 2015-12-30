<?php

namespace PHPMICROLIB;

/** Simple template class. */
class Template {

  /** Array to save key-value pairs. */
  private $vars;

  /** Path to template file. */
  private $file;

  /**
   * Constructor.
   * @param string $file Path to template file.
   * @param array $vars Optional key-value pair of variables.
   */
  function __construct($file, $vars = array()) {
    $this->file = $file;
    $this->vars = $vars;
  }

  /**
   * Sets key-value pairs for this template.
   * @param string $key Name of variable.
   * @param string $value Value of variable.
   * @return void
   */
  public function set($key, $value) {
    $this->vars[$key] = $value;
  }

  /**
   * Returns parsed content of template.
   * @return string Parsed content of template.
   */
  public function parse() {
    ob_start();
    extract($this->vars);
    include($this->file);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
  }
  
  /**
   * Escapes for HTML.
   * To be used in the template as an instance method, see example.
   *
   * <pre>echo $this->esc($someHTML);</pre>
   *
   * @param string $s String to escape.
   * @return string Escaped string. 
   */
  public function esc($s) {
    return htmlspecialchars($s);
  }
}

?>
