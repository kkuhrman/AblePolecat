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
        
        //
        // BEGIN JUNK
        // Example of how to create message with object query, dispatch via service bus and handle response
        //
        
        //
        // 1. Get access control agent
        //    @todo: server, application agents should not be public - user agent
        //
        $Agent = AblePolecat_Server::getServerMode()->getEnvironment()->getAgent();
        
        //
        // 2. Create the object query
        //
        $Account = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Node_ObjectReference');
        $Account->Id = array('find this...', 'and this...', 'and even this...',);
        
        //
        // 3. Create the request message and embed the object request
        //
        $GetAccounts = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Request_Get');
        $GetAccounts->setResource(AblePolecat_Service_Client_Apex::getId());
        $GetAccounts->Account = $Account;
        
        //
        // 4. Dispatch the message.
        //
        $Response = AblePolecat_Server::getServiceBus()->dispatch($Agent, $GetAccounts);
        
        //
        // 5. Unpack data from the response.
        
        if (200 == $Response->getStatusCode()) {
          foreach($Response->data as $record) {
            //
            // do something...
            //
            // var_dump($record);
          }
        }
        
        //
        // END JUNK
        //
      ?>
    </p>
  </div>
</body>
</html>