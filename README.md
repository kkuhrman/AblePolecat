About Able Polecat
--------------------------------------------------------------------------------
Able Polecat is a message-based middleware solution written in PHP intended to 
provide an affordable and effective means of integrating small to medium size 
businesses information systems.

The goal of Able Polecat is to give developers the ability to quickly implement 
integration solutions as web services written in PHP.

The core Able Polecat class library comprises:
1. Enterprise Service Bus (ESB)
2. Role-based Access Control
3. Message Queue
4. Transaction Management
5. Data Transformation and Exchange (DTX) Tools
6. A PHP class registry and lazy loader.
7. Tools for logging, exception and error handling
6. Framework for extending functionality

Extending Able Polecat
--------------------------------------------------------------------------------
As with any software framework, best practices discourage modifications to the 
Able Polecat core. Instead developers should extend functionality by writing 
modules, web services or server-side scripts.

An Able Polecat 'module' is an implementation of one or more core interfaces. 
Able Polecat modules are the primary means of extending core functionality.
Best practices encourage contributing added functionality to Able Polecat by 
releasing modules as open-source code for review and reuse by other developers. 
A few examples of contributed third-party Able Polecat modules include a logger 
for FireBug, which encapsulates the FirePHP class library; and a service client, 
which encapsulates the Google API class library for PHP.

A web service may also implement Able Polecat interfaces but, true to the name,
delivers a service over the web.

All PHP scripts are server-side by design. But Able Polecat uses the term to 
categorize any implementation of a core interface, which is not part of a module
or web service.

 
Core System Requirements
--------------------------------------------------------------------------------
PHP 5.2.x

Background and Acknowledgements
--------------------------------------------------------------------------------
Able Polecat grew from the need make data from a legacy ERP system, built in the 
ProvideX environment, available on the web. Initially, the web application was a
tightly-coupled consumer of this data, which extended Zend Framework classes.
This was later replaced with a Drupal module, but still tightly coupled.

The current class library is heavily influenced by its Zend and Drupal roots. The 
coding convention and file organization owe a debt to Zend, as Able Polecat began
as a Zend module. Both Zend and Drupal influenced its modular design. 

The name of the project draws its inspiration from an offensive system developed 
for American football by Glenn Ellison in the 1950s: 'Lonesome Polecat', which is 
often  is credited with providing the foundation for the modern spread offense. 
Able Polecat draws additional inspiration from the fabled "Skunk Works" R&D at 
Lockheed Martin and also the fact that the initial staffing of this project was 
limited to a single software architect (the "lonesome polecat").


Coding Standards
--------------------------------------------------------------------------------
Able Polecat uses the Zend naming convention:
http://framework.zend.com/manual/1.12/en/coding-standard.naming-conventions.html

