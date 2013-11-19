About Able Polecat
--------------------------------------------------------------------------------
Able Polecat is a PHP class library intended to provide small to medium size 
businesses an affordable and effective alternative to enterprise middleware and 
EAI solutions.

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

Core System Requirements
--------------------------------------------------------------------------------
PHP 5.2.x

Data Transformation and Exchange
--------------------------------------------------------------------------------
Able Polecat is designed to handle different types of data exchange between 
business applications; from scheduled bulk data exchanges, to on-demand ACID 
transactions and field-level read-only refreshes.

Able Polecat provides three points of entry. The default is CLI or web browser. 
Otherwise, routines can be scheduled as Cron tasks or exposed as web services.

Coding Standards
--------------------------------------------------------------------------------
Able Polecat uses the Zend naming convention:
http://framework.zend.com/manual/1.12/en/coding-standard.naming-conventions.html

