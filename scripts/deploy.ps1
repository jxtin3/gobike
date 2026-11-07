<#
.SYNOPSIS
  Deploys the Go Bike website to Hostinger (https://gobikeproject.org).

.DESCRIPTION
  1. Checks that the work is committed, then pushes main to GitHub.
  2. Builds the CSS/JS (Vite) on this PC, because the Premium plan has no Node.js.
  3. Uploads the build to the server.
  4. On the server: pulls the code from GitHub, installs PHP packages, runs new
     migrations and rebuilds Laravel's caches.

  Needs the SSH alias "gobike-hostinger" in ~/.ssh/config (key ~/.ssh/hostinger_gobike).

.EXAMPLE
  .\scripts\deploy.ps1
#>
param(
    # Deploy even if there are uncommitted changes (they will not reach the server).
    [switch]$AllowDirty
)

$ErrorActionPreference = 'Continue'

# ---- settings -------------------------------------------------------------
$SshHost = 'gobike-hostinger'
$AppDir  = '~/domains/gobikeproject.org/gobike'
$SiteUrl = 'https://gobikeproject.org'
# ---------------------------------------------------------------------------

function Fail([string]$message) {
    Write-Host "deploy: $message" -ForegroundColor Red
    exit 1
}

function Step([string]$message) {
    Write-Host "deploy: $message" -ForegroundColor Cyan
}

$root = Split-Path $PSScriptRoot -Parent
Set-Location $root

# ---- 1. commit check and push ---------------------------------------------
if ((git rev-parse --abbrev-ref HEAD) -ne 'main') { Fail 'switch to the main branch first.' }
if (-not $AllowDirty) {
    if (git status --porcelain) { Fail 'there are uncommitted changes. Commit them first, or pass -AllowDirty.' }
}
Step 'pushing main to GitHub'
git push -q origin main
if ($LASTEXITCODE -ne 0) { Fail 'git push failed.' }

# ---- 2. build the front end -----------------------------------------------
Step 'building CSS/JS (npm run build)'
npm run build | Out-Null
if ($LASTEXITCODE -ne 0) { Fail 'npm run build failed. Run it by hand to see the error.' }

# ---- 3. upload the build ----------------------------------------------------
Step 'uploading the build'
$archive = Join-Path $env:TEMP 'gobike-build.tgz'
tar -czf $archive -C public build
if ($LASTEXITCODE -ne 0) { Fail 'could not pack public/build.' }
scp -q $archive "${SshHost}:gobike-build.tgz"
if ($LASTEXITCODE -ne 0) { Fail 'upload failed. Check that "ssh gobike-hostinger" works.' }
Remove-Item $archive

# ---- 4. update the server ---------------------------------------------------
# Hostinger disables proc_open, so Composer runs without its scripts and
# package:discover is run on its own.
Step 'updating the server'
$remote = @"
set -e
cd $AppDir
git pull -q --ff-only
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts -q
php artisan package:discover -q
rm -rf public/build && tar -xzf ~/gobike-build.tgz -C public && rm ~/gobike-build.tgz
php artisan migrate --force
php artisan config:cache -q
php artisan route:cache -q
php artisan view:cache -q
php artisan event:cache -q
git log --oneline -1
"@
# Windows line endings would break the remote shell.
ssh $SshHost ($remote -replace "`r", '')
if ($LASTEXITCODE -ne 0) { Fail 'a server step failed (see the output above). The site may be half-updated: fix it and deploy again.' }

# ---- 5. check the site --------------------------------------------------------
try {
    $status = (Invoke-WebRequest -Uri "$SiteUrl/up" -UseBasicParsing -TimeoutSec 30).StatusCode
} catch {
    $status = $_.Exception.Response.StatusCode.value__
}
if ($status -ne 200) { Fail "the site answered $status on /up. Check storage/logs on the server." }

Write-Host "deploy: done. $SiteUrl is up to date." -ForegroundColor Green
