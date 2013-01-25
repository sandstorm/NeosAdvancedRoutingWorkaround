Neos Advanced Routing Workaround
================================

Make it possible to map TYPO3 Neos nodes to arbitrary URLs depending on the
node type.

Installation
------------

* Download this package `Sandstorm.NeosAdvancedRoutingWorkaround`
* Add the following as the first entry to the neos routes.yaml:

```
-
  name: 'TYPO3NeosWorkaround'
  uriPattern: '<WorkaroundSubroutes>'
  subRoutes:
    WorkaroundSubroutes:
      package: Sandstorm.NeosAdvancedRoutingWorkaround
```

* Migrate the database to create the new table

Usage
-----

Inside a TYPO3 Neos Content Type, you can use the new "uriPattern" option.
For example:
```
'RobertLemke.Plugin.Blog:Post':
	uriPattern: "'blog/' + str_replace('-', '/', node.getProperty('datePublished')) + '/' + node.name"
```

Carveats
--------

* currently, this only generates absolute URLs.
* URLs are only created on first access, not before.
* additional DB table for the mapping needed.

-> Works, but it is just a workaround for now.