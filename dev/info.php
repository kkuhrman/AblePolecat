<?php
/**
 * @file: info.php
 * Default landing page for user site in dev mode.
 */
$ABLE_POLECAT_ROOT = dirname(__DIR__);
require_once($ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'pathdefs.php');
require_once($ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'bootmode.php');

?>

<!DOCTYPE html>
<html>
<head>
  <title>Able Polecat | Developer Info</title>
</head>

<body>
  <div id="header" style="opacity:0.8;position:relative;left:12px;width:1020px;height:65px;background-color:grey">
    <!-- @todo logo -->
    <h2>Able Polecat &copy; Project</h2>
    <p>
      Copyright &copy; 2008-2013 <a href="http://www.abledistributors.com" target="new">Able Distributors Inc.</a>. All rights reserved.
    </p>
    <p>Script execution time was <?php echo ABLE_POLECAT_CLOCK_PRINT(); ?></p>
    <p>Able Polecat runtime context is <?php 
    echo sprintf("%1$032b", $runtime_context) . ' aka ' . print_r($runtime_context, TRUE) . ' (' . $ABLE_POLECAT_RUNTIME_CONTEXT_STR[$runtime_context] . ') '; ?>
    <a href="<?php print(ABLE_POLECAT_BASE_URL . 'dev/runctxt.php'); ?>"><small>change this</small></a>
    </p>
    <p><?php print_r($_COOKIE); ?></p>
  </div>
</body>
</html>