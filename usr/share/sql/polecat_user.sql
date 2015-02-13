--
-- polecat_user.sql
-- Able Polecat user
--

GRANT 
  SELECT, 
  INSERT, 
  UPDATE, 
  DELETE, 
  CREATE, 
  DROP, 
  INDEX, 
  ALTER, 
  LOCK TABLES, 
  CREATE TEMPORARY TABLES 
ON `polecat`.* TO 'polecat'@'localhost' IDENTIFIED BY 'password';

INSERT INTO `polecat`.`user` (`userId`, `userAlias`, `clientId`, `userName`) VALUES ('0', 'System', 'Able Polecat', 'System');
INSERT INTO `polecat`.`user` (`userId`, `userAlias`, `clientId`, `userName`) VALUES ('1', 'Anonymous', 'Able Polecat', 'Anonymous');