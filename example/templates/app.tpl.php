<?php
use PHPMICROLIB\Router\Route;
?><!doctype html>
<html>
  <head>
    <title><?php echo $this->esc($title); ?></title>
    <link rel="stylesheet" href="<?php echo Route::getFrontControllerPath(); ?>/css/app.css">
  </head>
  <body>
    <?php if (!empty($exception)): ?>
    <div class="error"><?php echo $this->esc($exception); ?></div>
    <?php endif; ?>
    <?php echo $content; ?>
  </body>
</html>