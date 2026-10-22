#!/usr/bin/env bash
#
# Update PHP 8.3 on the WSL Ubuntu host and in the DDEV web container.
#
# Target: PHP >= 8.3.36
# Node.js and composer.json are deliberately left untouched.
#
# Run from anywhere inside the project:
#
#   bash scripts/update-php83.sh
#
# The host step uses sudo and the ondrej/php PPA. The DDEV step rebuilds the
# web image from .ddev/web-build/Dockerfile without Docker layer cache.

set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

MIN_PHP="8.3.36"
PPA="ppa:ondrej/php"

if [[ "${EUID}" -eq 0 ]]; then
    SUDO=()
else
    SUDO=(sudo)
fi

log() {
    printf '\n==> %s\n' "$*"
}

fail() {
    printf '\nERROR: %s\n' "$*" >&2
    exit 1
}

php_version_ok() {
    dpkg --compare-versions "$1" ge "$MIN_PHP"
}

host_php_version() {
    php -r 'echo PHP_VERSION;'
}

ddev_php_version() {
    ddev exec php -r 'echo PHP_VERSION;' | tr -d '\r'
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "Required command not found: $1"
}

update_host_php() {
    log "Updating host PHP 8.3 packages from ${PPA}"

    "${SUDO[@]}" apt-get update
    "${SUDO[@]}" apt-get install -y \
        software-properties-common \
        ca-certificates \
        lsb-release \
        apt-transport-https

    if ! grep -Rqs 'ondrej/php' /etc/apt/sources.list /etc/apt/sources.list.d; then
        "${SUDO[@]}" add-apt-repository -y "$PPA"
    fi

    "${SUDO[@]}" apt-get update

    mapfile -t php_packages < <(
        dpkg-query -W -f='${db:Status-Abbrev} ${Package}\n' 'php8.3-*' 2>/dev/null \
            | awk '$1 == "ii" { print $2 }' \
            | sort -u
    )

    [[ "${#php_packages[@]}" -gt 0 ]] || fail "No installed php8.3-* packages were found."

    log "Upgrading: ${php_packages[*]}"
    "${SUDO[@]}" env DEBIAN_FRONTEND=noninteractive \
        apt-get -y -o Dpkg::Options::=--force-confold \
        install --only-upgrade "${php_packages[@]}"

    if [[ -x /usr/bin/php8.3 ]] && command -v update-alternatives >/dev/null 2>&1; then
        "${SUDO[@]}" update-alternatives --set php /usr/bin/php8.3 || true
    fi

    hash -r

    if dpkg -s php8.3-fpm >/dev/null 2>&1; then
        if command -v systemctl >/dev/null 2>&1 && systemctl is-system-running >/dev/null 2>&1; then
            "${SUDO[@]}" systemctl restart php8.3-fpm || true
        elif command -v service >/dev/null 2>&1; then
            "${SUDO[@]}" service php8.3-fpm restart || true
        fi
    fi
}

update_ddev_php() {
    log "Refreshing DDEV web image and upgrading PHP 8.3"

    [[ -f .ddev/web-build/Dockerfile ]] || fail "Missing .ddev/web-build/Dockerfile"
    require_command ddev
    require_command docker

    local web_image
    web_image="$(ddev version 2>/dev/null | awk '$1 == "web" { print $2; exit }')"

    if [[ -n "$web_image" ]]; then
        log "Pulling ${web_image}"
        docker pull "$web_image" || true
    fi

    log "Rebuilding the DDEV web layer without cache"
    ddev restart --no-cache -y
}

verify() {
    log "Verifying PHP versions"

    local host_version
    local container_version

    host_version="$(host_php_version)"
    container_version="$(ddev_php_version)"

    printf 'Host PHP:  %s\n' "$host_version"
    printf 'DDEV PHP:  %s\n' "$container_version"
    printf 'Required:  >= %s\n' "$MIN_PHP"

    php_version_ok "$host_version" \
        || fail "Host PHP ${host_version} is older than ${MIN_PHP}."
    php_version_ok "$container_version" \
        || fail "DDEV PHP ${container_version} is older than ${MIN_PHP}."

    printf '\nPHP update complete. Node.js and composer.json were not changed.\n'
}

main() {
    require_command php
    require_command dpkg

    update_host_php
    update_ddev_php
    verify
}

main "$@"
