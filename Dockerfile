FROM php:7.0-cli

RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get -yq install \
        libpq5 && \
    rm -rf /var/lib/apt/lists/*

RUN set -xe && \
    buildDeps=" \
        libpq-dev \
        " && \
    apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get -yq install $buildDeps && \
    docker-php-ext-install pdo_pgsql && \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false -o APT::AutoRemove::SuggestsImportant=false $buildDeps && \
    rm -rf /var/lib/apt/lists/*

ADD composer.json /srv/navitia-stat-exporter/composer.json
ADD composer.lock /srv/navitia-stat-exporter/composer.lock
ADD exporter.php /srv/navitia-stat-exporter/exporter.php
ADD config.php.docker /srv/navitia-stat-exporter/config.php
ADD Formatters /srv/navitia-stat-exporter/Formatters
ADD vendor /srv/navitia-stat-exporter/vendor
ADD docker-entrypoint.sh /

WORKDIR /srv/navitia-stat-exporter

ENTRYPOINT [ "/docker-entrypoint.sh" ]
