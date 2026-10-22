#ddev-silent-no-warn
# Extend DDEV's web image with the current PHP 8.3 patch release.
#
# DDEV v1.25.4 ships PHP 8.3.33. The Sury repository already contains 8.3.36,
# so this layer upgrades every installed php8.3-* package and fails the build
# if the resulting CLI is older than 8.3.36.
#
# Do not add FROM/BASE_IMAGE here. DDEV appends this file to its generated
# Dockerfile; a second FROM would discard DDEV's user and setup layers.

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --only-upgrade \
        -o Dpkg::Options::="--force-confold" \
        $(dpkg-query -W -f='${db:Status-Abbrev} ${Package}\n' 'php8.3-*' \
            | awk '$1 == "ii" { print $2 }') \
    && update-alternatives --set php /usr/bin/php8.3 \
    && update-alternatives --set php-fpm /usr/sbin/php-fpm8.3 \
    && /usr/bin/php8.3 -r 'if (version_compare(PHP_VERSION, "8.3.36", "<")) { fwrite(STDERR, "PHP ".PHP_VERSION." is older than 8.3.36\n"); exit(1); }' \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
