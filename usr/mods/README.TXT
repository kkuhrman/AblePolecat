################################################################################
Able Polecat modules comprise at least one class, which implements an Able Polecat 
interface; and a module configuration file, which names the classes to be added 
to the class registry, provides module name/id etc and information about any 
third party class libraries used.

Modules should be installed in a preset module directory, which must always be 
located in ./ABLE_POLECAT_USR/mod. ABLE_POLECAT_USR is a constant defined in 
/core/Server.php but can be overidden prior to bootstrapping the server.

The basic directory structure of a module:

./usr/mod/[module directory name]/-+--conf/module.xml
                                    |
                                    +-MyClass.php...

