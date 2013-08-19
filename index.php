<?php
/**
 * @file: index.php
 * Handles redirection based on runtime context.
 * @todo: URL rewrites
 */

$ABLE_POLECAT_ROOT = __DIR__;
require_once($ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'pathdefs.php');
require_once($ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'bootmode.php');

$ABLE_POLECAT_ENVIRONMENT_INCL_PATH = ABLE_POLECAT_PATH  . DIRECTORY_SEPARATOR . 'Environment'   . DIRECTORY_SEPARATOR . 'Default.php';
if (!is_file($ABLE_POLECAT_ENVIRONMENT_INCL_PATH)) {
  die("Able Polecat failed to start. Invalid path for environment given: $ABLE_POLECAT_ENVIRONMENT_INCL_PATH");
}
else {
  require_once($ABLE_POLECAT_ENVIRONMENT_INCL_PATH);
  AblePolecat_Environment_Default::bootstrap();
}

//
// Redirect to dev or qa
//
$runtime_context = AblePolecat_EnvironmentAbstract::getCurrent()->getRuntimeContext();
switch ($runtime_context) {
  default:
    break;
  case ABLE_POLECAT_RUNTIME_USER:
    $default_url = ABLE_POLECAT_BASE_URL . 'user/sites/default/index.php';
    header("Location: $default_url");
    break;
  case ABLE_POLECAT_RUNTIME_DEV:
    $dev_url = ABLE_POLECAT_BASE_URL . 'dev/info.php';
    header("Location: $dev_url");
    break;
  case ABLE_POLECAT_RUNTIME_QA:
    $qa_url = ABLE_POLECAT_BASE_URL . 'qa/info.php';
    header("Location: $qa_url");
    break;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Able Polecat | Runtime Context Error</title>
</head>

<body>
  <div id="header" style="opacity:0.8;position:relative;left:12px;width:1020px;height:65px;background-color:grey">
    <!-- @todo logo -->
    <h2>Able Polecat &copy; Project</h2>
    <p>
      Copyright &copy; 2008-2013 <a href="http://www.abledistributors.com" target="new">Able Distributors Inc.</a>. All rights reserved.
    </p>
    <p>Able Polecat runtime context <?php echo sprintf("%1$032b", $runtime_context) . 
      ' (' . print_r($runtime_context, TRUE) . ') is not recognized. '; ?>
    <a href="<?php print(ABLE_POLECAT_BASE_URL . '/dev/runctxt.php'); ?>"><small>change this</small></a>
    </p>
  </div>
</body>
</html>