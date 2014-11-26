All Able Polecat Project software is released under the BSD 2 License.
Copyright (c) 2008-2014 by Karl Kuhrman. All rights reserved.

@see: LICENSE.md

About Able Polecat
--------------------------------------------------------------------------------
Able Polecat is a message-based middleware solution written in PHP intended to 
provide an affordable and effective means of integrating small to medium size 
businesses information systems.

The goal of Able Polecat is to give developers the ability to quickly implement 
integration solutions as web services written in PHP.

At the heart of the Able Polecat project is the Enterprise Service Bus (ESB), 
which manages a message queue and a transaction queue. Transactions are classes 
in Able Polecat, whose work it is to return a Resource class, which in turn can 
be used to enlist other Transactions in the work-flow or generate an HTTP response.

Extending Able Polecat
--------------------------------------------------------------------------------
As with any software framework, best practices discourage modifications to the 
Able Polecat core. Instead developers should extend functionality by writing 
modules, web services or server-side scripts.

An Able Polecat 'module' is an implementation of one or more core interfaces. 
Able Polecat modules are the primary means of extending core functionality.
Best practices encourage contributing added functionality to Able Polecat by 
releasing modules as open-source code for review and reuse by other developers. 

A web service may also implement Able Polecat interfaces but, true to the name,
delivers a service over the web.

All PHP scripts are server-side by design. But Able Polecat uses the term to 
categorize any implementation of a core interface, which is not part of a module
or web service.

 
Core System Requirements
--------------------------------------------------------------------------------

System requirements are given at: 
https://github.com/kkuhrman/AblePolecat/wiki/Getting-Started

Basic Installation Instructions
--------------------------------------------------------------------------------

More detailed instrcutions can be found at:
https://github.com/kkuhrman/AblePolecat/wiki/Getting-Started

Background and Acknowledgements
--------------------------------------------------------------------------------
Able Polecat grew from the need make data from a legacy ERP system, built in the 
ProvideX environment, available on the web. Initially, the web application was a
tightly-coupled consumer of this data, which extended Zend Framework classes.
This was later replaced with a Drupal module, but still tightly coupled.

The current class library is heavily influenced by its Zend and Drupal roots. The 
coding convention and file organization owe a debt to Zend, as Able Polecat began
as a Zend module. Both Zend and Drupal influenced its modular design.

However, though Able Polecat owes a debt to both projects when it comes to design 
patterns, software coding conventions and practices, neither was designed from the 
ground up to be a message-based middleware solution within a SOA. Zend was designed 
to be an MVC web application framework and Drupal was designed to be a web content 
management framework. Able Polecat, is designed to provide a message-based middleware
solution on a web server.

The name of the project draws its inspiration from an offensive system developed 
for American football by Glenn Ellison in the 1950s: 'Lonesome Polecat', which is 
often  is credited with providing the foundation for the modern spread offense. 
Able Polecat draws additional inspiration from the fabled "Skunk Works" R&D at 
Lockheed Martin and also the fact that the initial staffing of this project was 
limited to a single software architect (the "lonesome polecat").

The Zend Framework (http://http://framework.zend.com/) is released under the 
New BSD License.

The Drupal Comtent Management Framework (https://drupal.org/) is released under
the GNU GPL v2 License.
