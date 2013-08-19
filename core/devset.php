<?php
/**
 * @file: devset.php
 * Simple form for managing Able Polecat development and testing settings.
 */

if (isset($_GET['runtime_context'])) {
  $runtime_context = $_GET['runtime_context'];
  header("Location: index.php?run=$runtime_context");
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Able Polecat</title>
</head>

<body>
  <div id="header" style="opacity:0.8;position:relative;left:12px;width:1020px;height:65px;background-color:grey">
    <!-- @todo logo -->
    <h2>Able Polecat &copy; Project</h2>
    <p>
      Copyright &copy; 2008-2013 <a href="http://www.abledistributors.com" target="new">Able Distributors Inc.</a>. All rights reserved.
    </p>
    <p>
    <form id="ABLE_POLECAT_DEVSET" action="devset.php" method="get">
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