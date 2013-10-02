<?php
/**
 * @file: index.php
 * Handles redirection based on runtime context.
 * @todo: URL rewrites
 */

require_once('boot.php');
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
    <p><?php 
        AblePolecat_Server::log(AblePolecat_LogInterface::STATUS, "Application mode loaded.");
        echo "<p>PHP Version: " . phpversion() . "</p>";
        $ApexClient = AblePolecat_Server::getServiceBus()->getClient('f5ec7a80-7159-11e2-bcfd-0800200c9a666');
      ?>
    </p>
  </div>
</body>
</html>