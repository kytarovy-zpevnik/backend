Rest app road map
=================

Routing
-------

* problems with leading and trailing / in url parts
* handle with ?query params in url
* support for array[index] in api Request getters
* return HTTP 510 Gone for old routes
* fix conflict in param mask with optional segment, eg. <id [0-9]+> evaluates as <id>(0-9)?+