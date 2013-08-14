<?php
/**
 * @file
 * Default point of entry to Able Polecat web interface.
 */

require_once('settings.php');

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Clock.php');
$Clock = new AblePolecat_Clock();
$Clock->start();

$ABLE_POLECAT_ENVIRONMENT_INCL_PATH = ABLE_POLECAT_PATH  . DIRECTORY_SEPARATOR . 'Environment'   . DIRECTORY_SEPARATOR . 'Default.php';
if (!is_file($ABLE_POLECAT_ENVIRONMENT_INCL_PATH)) {
  die("Able Polecat failed to start. Invalid path for environment given: $ABLE_POLECAT_ENVIRONMENT_INCL_PATH");
}
else {
  require_once($ABLE_POLECAT_ENVIRONMENT_INCL_PATH);
  AblePolecat_Environment_Default::bootstrap();
}
$ellapsed_time = $Clock->getElapsedTime(AblePolecat_Clock::ELAPSED_TIME_TOTAL_ACTIVE, TRUE);

//
// Logging examples
//
// $Environment = AblePolecat_EnvironmentAbstract::getCurrent();
// $Environment->logStatusMessage('This is an informational message.');
// $Environment->logWarningMessage('This is a warning message.');
// $Environment->logErrorMessage('This is an error message.');
// $Environment->dumpBacktrace('This should stop script and dump trace.');
?>

<!DOCTYPE html>
<html>
<head>
  <title>Able Polecat</title>
</head>

<body>
  <div id="header" style="opacity:0.8;position:relative;left:12px;width:1020px;height:65px;background-color:grey">
    <img id="logo" src="http://polecat.abledistributors.com/capex/img/able_logo.png" width="200" height="63">
    <h2>Able Polecat &copy; Project</h2>
    <p>
      Copyright &copy; 2008-2013 <a href="http://www.abledistributors.com" target="new">Able Distributors Inc.</a>. All rights reserved.
    </p>
    <p>
    Able Polecat &copy; is a lightweight, open-source middleware written in PHP. The intended purpose of the Able Polecat &copy; project 
    is to allow SMB businesses to integrate legacy ERP systems with Salesforce.com &reg; and Google.com &reg; Apps for Business.
    </p>
    <p>
      The core Able Polecat &copy; library is distributed under the <a href="http://opensource.org/licenses/BSD-3-Clause" target="new">Modified BSD License</a>. 
      It includes a simple Enterprise Service Bus, encapsulating the Salesforce.com &copy; PHP SOAP client and the Google &copy; APIs REST client library for PHP. 
      Those who would use Able Polecat &copy; to integrate a legacy ERP with Salesforce.com &copy; and/or Google.com &reg; Apps for Business need to use one of 
      available, open source ESB clients for their respective ERP or build their own client.
    </p>
    <p>
      For more information or if you wish to contribute: <a href="mailto:webmaster@abledistributors.com">webmaster@abledistributors.com</a>
    </p>
    <br />
    <h2>Application Settings</h2>
    <p><ul>
      <li type="square"><?php ABLE_POLECAT_IS_MODE(ABLE_POLECAT_LOCAL_HOST) ? print("LOCALHOST") : print("REMOTEHOST"); ?></li>
      <li type="square"><?php print "Error reporting is "; ABLE_POLECAT_IS_MODE(ABLE_POLECAT_ERROR_ON) ? print(" ON ") : print(" OFF "); ?></li>
      <li type="square"><?php print "System logging is "; ABLE_POLECAT_IS_MODE(ABLE_POLECAT_LOG_SYSTEM) ? print(" ON ") : print(" OFF "); ?></li>
      <li type="square"><?php print "FirePHP logging is "; ABLE_POLECAT_IS_MODE(ABLE_POLECAT_LOG_FIREPHP) ? print(" ON ") : print(" OFF "); ?></li>
      <li type="square"><?php print "Database logging is "; ABLE_POLECAT_IS_MODE(ABLE_POLECAT_LOG_DB) ? print(" ON ") : print(" OFF "); ?></li>
      <li type="square"><?php print "Apex logging is "; ABLE_POLECAT_IS_MODE(ABLE_POLECAT_LOG_APEX) ? print(" ON ") : print(" OFF "); ?></li>
      <?php ABLE_POLECAT_IS_MODE(ABLE_POLECAT_DB_MYSQL) ? print("<li type=\"square\">Using MySql database support.</li>") : NULL; ?>
      <?php ABLE_POLECAT_IS_MODE(ABLE_POLECAT_DB_MSSQL) ? print("<li type=\"square\">Using MS SQL Server database support.</li>") : NULL; ?>
      <?php ABLE_POLECAT_IS_MODE(ABLE_POLECAT_APEX_DEV) ? print("<li type=\"square\">Salesforce edition is developer sandbox.</li>") : NULL; ?>
      <?php ABLE_POLECAT_IS_MODE(ABLE_POLECAT_APEX_QA) ? print("<li type=\"square\">Salesforce edition is full sandbox.</li>") : NULL; ?>
      <?php ABLE_POLECAT_IS_MODE(ABLE_POLECAT_APEX_PRO) ? print("<li type=\"square\">Salesforce edition is Enterprise.</li>") : NULL; ?>
      <li type="square"><?php echo "Bootstrap completed in $ellapsed_time"; ?></li>
    </ul></p>
    <br />
    <h4>Background</h4>
    <p>
      <small>The name of the project draws its inspiration from the fabled "Skunk Works" R&D at Lockheed Martin and from an offensive system developed 
      by Glenn Ellison in the 1950s: 'Lonesome Polecat'. The latter is credited by some as having provided a foundation for the modern spread offense
      in American football. The name also gives a nod to the fact that the initial staffing of this project was limited to a single software architect 
      (the "lonesome polecat"), <a href="http://www.linkedin.com/in/kuhrman" target="new">Karl Kuhrman</a></small>.
    </p>
  </div>
</body>
</html>