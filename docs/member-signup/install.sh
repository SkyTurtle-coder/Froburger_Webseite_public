#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
release="${1:?Release fehlt}"
[[ "$release" =~ ^avf-member-signup-wp-[0-9]{8}-[0-9]{6}$ ]]
[[ "${2:?Hash fehlt}" =~ ^[a-f0-9]{64}$ && "${3:?Baseline-Hash fehlt}" =~ ^[a-f0-9]{64}$ ]]
app=/home/avfrobur/www/gamma.avfroburger.ch
stage=/home/avfrobur/deploy-tmp/$release
backup=/home/avfrobur/backups/$release
files=(wp-content/plugins/avf-events-integration/assets/css/events-lists.css wp-content/plugins/avf-events-integration/includes/class-avf-events-api-client.php wp-content/plugins/avf-events-integration/includes/class-avf-event-detail-router.php wp-content/plugins/avf-events-integration/includes/class-avf-event-detail-shortcode.php wp-content/plugins/avf-events-integration/includes/class-avf-event-signup-handler.php)
cd "$app"
test "$(wp option get home)" = 'https://www.avfroburger.ch'
wp plugin is-active avf-events-integration
# Require the backend policy before exposing the new public form.
wp eval '$r=avf_internal_api_request("GET", "/api/v1/public/events/schluss-acbc/", array("timeout"=>10)); if (is_wp_error($r) || wp_remote_retrieve_response_code($r)!==200) {fwrite(STDERR,"Backend nicht erreichbar.\n"); exit(1);} $d=json_decode(wp_remote_retrieve_body($r),true); if (($d["signup_policy"]??"")!=="members_v1" || empty($d["is_internal"]) || !isset($d["signups"]) || !is_array($d["signups"])) {fwrite(STDERR,"Backend-Mitgliederpruefung fehlt. Zuerst Django deployen.\n"); exit(1);}'
php -r 'exit(hash_file($argv[3], $argv[1]) === $argv[2] ? 0 : 1);' "$stage/new.tar.gz" "$2" sha256
php -r 'exit(hash_file($argv[3], $argv[1]) === $argv[2] ? 0 : 1);' "$stage/base.tar.gz" "$3" sha256
mkdir -p "$stage/new" "$stage/base"
tar -xzf "$stage/new.tar.gz" -C "$stage/new"
tar -xzf "$stage/base.tar.gz" -C "$stage/base"
for file in "${files[@]}"; do
    if [[ "$file" = *.php ]]; then php -l "$stage/new/$file"; fi
    # Exact reviewed production variant: older integer coercion in the signup handler.
    # All other file changes still stop deployment before writing.
    php -r '$read = fn($p) => str_replace("\r\n", "\n", file_get_contents($p)); $current=$read($argv[1]); $reviewed = $argv[1] === "wp-content/plugins/avf-events-integration/includes/class-avf-event-signup-handler.php" && hash("sha256", $current) === "9c92713eb94fc65cf4834887e3bcfafa64c01dcb993bc6f91bfc40b38427bf66"; if (!$reviewed && $current !== $read($argv[2]) && $current !== $read($argv[3])) { fwrite(STDERR, "Serverdatei weicht ab: ".$argv[1].". Nichts installiert.\n"); exit(1); }' "$file" "$stage/base/$file" "$stage/new/$file"
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
# Purge plugin transients and Elementor output; details now bypass stale data.
wp eval 'AVF_Events_API_Client::clear_cache(); if (class_exists("\\Elementor\\Plugin")) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }'
wp cache flush
wp maintenance-mode deactivate
curl --fail --silent --show-error --max-time 30 --dump-header "$stage/headers.txt" "https://www.avfroburger.ch/anlaesse/schluss-acbc/?avf_release=$release" > "$stage/verified.html"
grep -q 'avf-event-detail__signup-grid' "$stage/verified.html"
grep -q 'id="avf-public-signups-heading"' "$stage/verified.html"
grep -qi 'cache-control:.*no-store' "$stage/headers.txt"
trap - ERR
printf 'Interne Anmeldung auf WordPress installiert. Backup: %s\n' "$backup"
