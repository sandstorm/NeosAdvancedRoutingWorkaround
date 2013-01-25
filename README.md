Neos Advanced Routing Workaround
================================

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
