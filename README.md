Composer global installer
=======================

This plugin allows to choose which packages should be installed in a shared directory with their version number as in this example:

Case 1: inside the package
```
vendor
├── autoload.php
├── composer
└── itscaro
    └── composer-global-installer

vendor-global/
└── zendframework
    ├── zendframework1-1.12.3
    └── zendframework1-1.12.6
```
Case 2: outside the package (globally shared for instance)
```
/package/
vendor
├── autoload.php
├── composer
└── itscaro
    └── composer-global-installer

/usr/share/php/
vendor-global/
└── zendframework
    ├── zendframework1-1.12.3
    └── zendframework1-1.12.6
```

Configuration

itscaro-global-installer is required to be present to activate this plugin, all nested properties are optional. By default, this plugin is active for "library" packages.

Default values

```
{
  "config": {
    "vendor-dir": "vendor",
    "itscaro-global-installer": {
      "vendor-global-dir": "vendor-global",
      "vendor-global-packages": [],
      "vendor-global-supported-types": [
        "library"
      ]
    }
  },
}
```

Only zendframework/zendframework1 package is installed in /usr/share/php
```
{
  "config": {
    "vendor-dir": "vendor",
    "itscaro-global-installer": {
      "vendor-global-dir": "/usr/share/php",
      "vendor-global-packages": [
        "zendframework/zendframework1"
      ]
    }
  },
}
```

Credits to Martin Hasoň <martin.hason@gmail.com>
