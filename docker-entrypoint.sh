#!/bin/bash

set -ex

php -dmemory_limit=-1 exporter.php "$@"
