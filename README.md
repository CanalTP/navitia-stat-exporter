# navitia-stat-exporter

POC for a JSON log file generator from a navitia stat database

## Installation

```
git clone https://github.com/vincentlepot/navitia-stat-exporter
cd navitia-stat-exporter
composer install
```

and copy and adapt the config.php.dist to config.php

## Execution

```
php exporter.php <date_to_export>
```

where <date_to_export> is a date in YYYY-MM-DD format