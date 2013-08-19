<?php
/**
 * @file: runctxt.php
 * Simple form for changing Able Polecat runtime context.
 */

$ABLE_POLECAT_ROOT = dirname(__DIR__);
require_once($ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'pathdefs.php');
require_once($ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'bootmode.php');

if(isset($_GET['runtime_context'])) {
  $runtime_context = $_GET['runtime_context'];
  header("Location: " . ABLE_POLECAT_BASE_URL . "?run=$runtime_context");
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Able Polecat | Runtime Context</title>
</head>

<body>
  <div id="header" style="opacity:0.8;position:relative;left:12px;width:1020px;height:65px;background-color:grey">
    <!-- @todo logo -->
    <h2>Able Polecat &copy; Project</h2>
    <p>
      Copyright &copy; 2008-2013 <a href="http://www.abledistributors.com" target="new">Able Distributors Inc.</a>. All rights reserved.
    </p>
    <p>
    <form id="ABLE_POLECAT_DEVSET" action="<?php ABLE_POLECAT_BASE_URL . '/runctxt.php' ?>" method="get">
      <span>Runtime Context</span>
      <select name="runtime_context">
        <option value="dev">Development</option>
        <option value="qa">Quality Assurance</option>
        <option value="use">User</option>
      </select>
      <input type="submit" value="submit" />
    </form>
    </p>
  </div>
</body>
</html>