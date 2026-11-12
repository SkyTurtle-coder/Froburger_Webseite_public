#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
release="${1:?Release fehlt}"
[[ "$release" =~ ^avf-card-titles-[0-9]{8}-[0-9]{6}$ ]]
[[ "${2:?Hash fehlt}" =~ ^[a-f0-9]{64}$ && "${3:?Baseline-Hash fehlt}" =~ ^[a-f0-9]{64}$ ]]
app=/home/avfrobur/www/gamma.avfroburger.ch
stage=/home/avfrobur/deploy-tmp/$release
backup=/home/avfrobur/backups/$release
files=(wp-content/plugins/avf-post-templates/assets/css/post-template-frontend.css wp-content/plugins/avf-post-templates/assets/js/post-template-frontend.js)
cd "$app"
test "$(wp option get home)" = 'https://www.avfroburger.ch'
wp plugin is-active avf-post-templates
printf '%s  %s\n' "$2" "$stage/new.tar.gz" "$3" "$stage/base.tar.gz" | sha256sum -c
mkdir -p "$stage/new" "$stage/base"
tar -xzf "$stage/new.tar.gz" -C "$stage/new"
tar -xzf "$stage/base.tar.gz" -C "$stage/base"
for file in "${files[@]}"; do
    # Preserve unrelated production edits: fail before writing if source differs.
    php -r '$read = fn($p) => str_replace("\r\n", "\n", file_get_contents($p)); $current=$read($argv[1]); if ($current !== $read($argv[2]) && $current !== $read($argv[3])) { fwrite(STDERR, "Serverdatei weicht ab: ".$argv[1].". Nichts installiert.\n"); exit(1); }' "$file" "$stage/base/$file" "$stage/new/$file"
done
if wp maintenance-mode is-active >/dev/null 2>&1; then
    printf 'WordPress ist bereits im Wartungsmodus. Abbruch.\n' >&2
    exit 1
fi
test ! -e "$backup"
mkdir -p "$backup"
tar -czf "$backup/code-before.tar.gz" "${files[@]}"
test -s "$backup/code-before.tar.gz"
rollback() {
    result=$?
    trap - ERR
    set +e
    tar -xzf "$backup/code-before.tar.gz" -C "$app"
    # A new filemtime invalidates browser caches, including those of a partial release.
    touch "${files[@]}"
    wp cache flush
    wp maintenance-mode deactivate
    printf 'Update fehlgeschlagen. Vorherige Dateien wiederhergestellt: %s\n' "$backup" >&2
    exit "$result"
}
trap rollback ERR
wp maintenance-mode activate
for file in "${files[@]}"; do
    install -m 644 "$stage/new/$file" "$app/$file"
done
# The existing enqueue code versions both assets by filemtime.
wp cache flush
wp maintenance-mode deactivate
curl --fail --silent --show-error --max-time 30 "https://www.avfroburger.ch/aktuelles/?avf_release=$release" > "$stage/verified.html"
grep -q 'avf-post-recommendations__card-title' "$stage/verified.html"
for file in "${files[@]}"; do
    curl --fail --silent --show-error --max-time 30 "https://www.avfroburger.ch/$file?avf_release=$release" > "$stage/asset-check"
    cmp "$stage/asset-check" "$app/$file"
done
trap - ERR
printf 'Kartentitel erfolgreich aktualisiert. Backup: %s\n' "$backup"
