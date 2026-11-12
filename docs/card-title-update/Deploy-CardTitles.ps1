param([switch]$Upload, [switch]$Deploy)
$ErrorActionPreference = 'Stop'
function Invoke-Checked {
    param([string]$Exe, [string[]]$Arguments)
    & $Exe @Arguments
    if ($LASTEXITCODE -ne 0) { throw "$Exe fehlgeschlagen (Exit $LASTEXITCODE)." }
}
$repo = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$commit = 'c5e21794e89cf1b0143d25c9054c47adf13b4b65'
$release = 'avf-card-titles-' + (Get-Date -Format 'yyyyMMdd-HHmmss')
$package = Join-Path $env:TEMP $release
$remote = "/home/avfrobur/deploy-tmp/$release"
New-Item -ItemType Directory -Path $package | Out-Null
$files = @('wp-content/plugins/avf-post-templates/assets/css/post-template-frontend.css', 'wp-content/plugins/avf-post-templates/assets/js/post-template-frontend.js')
Invoke-Checked git (@('-C', $repo, '-c', 'core.autocrlf=false', 'archive', '--format=tar.gz', "--output=$package/new.tar.gz", $commit, '--') + $files)
Invoke-Checked git (@('-C', $repo, '-c', 'core.autocrlf=false', 'archive', '--format=tar.gz', "--output=$package/base.tar.gz", "$commit^", '--') + $files)
$installer = [IO.File]::ReadAllText((Join-Path $PSScriptRoot 'install.sh')).Replace("`r`n", "`n")
[IO.File]::WriteAllText((Join-Path $package 'install.sh'), $installer, (New-Object System.Text.UTF8Encoding($false)))
$newHash = (Get-FileHash "$package/new.tar.gz" -Algorithm SHA256).Hash.ToLowerInvariant()
$baseHash = (Get-FileHash "$package/base.tar.gz" -Algorithm SHA256).Hash.ToLowerInvariant()
$installerHash = (Get-FileHash "$package/install.sh" -Algorithm SHA256).Hash.ToLowerInvariant()
$remoteCommand = 'php -r ''exit(hash_file($argv[3], $argv[1]) === $argv[2] ? 0 : 1);'' ''{0}/install.sh'' ''{1}'' sha256 && bash ''{0}/install.sh'' ''{2}'' ''{3}'' ''{4}' -f $remote, $installerHash, $release, $newHash, $baseHash
[IO.File]::WriteAllText((Join-Path $package 'launch-command.txt'), $remoteCommand, (New-Object System.Text.UTF8Encoding($false)))
Write-Host "Paket aus Commit ${commit}: $package"
if (-not ($Upload -or $Deploy)) {
    Write-Host 'Mit -Upload nur bereitstellen; mit -Deploy bereitstellen und produktiv installieren.'
    return
}
Invoke-Checked ssh @('avfroburger-hostpoint', "umask 077; mkdir -p '$remote'")
Invoke-Checked scp @("$package/new.tar.gz", "$package/base.tar.gz", "$package/install.sh", "avfroburger-hostpoint:$remote/")
$displayCommand = $remoteCommand.Replace('$', '`$')
Write-Host "SSH-Startbefehl: ssh avfroburger-hostpoint `"$displayCommand`""
if ($Deploy) {
    Invoke-Checked ssh @('avfroburger-hostpoint', $remoteCommand)
}
