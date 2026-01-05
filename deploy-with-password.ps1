# PowerShell script for deploying with password authentication (temporary solution)
# Usage: .\deploy-with-password.ps1

$ErrorActionPreference = "Stop"

# Settings
$VPS = "root@31.131.26.78"
$VPS_PASSWORD = "7gEbjQZayw2R7N86F3"
$ProjectDir = "/var/www/subcloudy"

# Output functions
function Write-Step {
    param([string]$Message, [int]$Step, [int]$Total)
    Write-Host "[$Step/$Total] $Message" -ForegroundColor Yellow
}

function Write-Success {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Write-ErrorMsg {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "        DEPLOY ACCOUNT ARENA (Password Auth)" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "WARNING: Using password authentication. This is less secure." -ForegroundColor Yellow
Write-Host "Please set up SSH keys for better security." -ForegroundColor Yellow
Write-Host ""

# Check if sshpass is available
$sshpassAvailable = Get-Command sshpass -ErrorAction SilentlyContinue

if (-not $sshpassAvailable) {
    Write-Host "[ERROR] sshpass is required for password authentication" -ForegroundColor Red
    Write-Host "Install options:" -ForegroundColor Yellow
    Write-Host "1. Install via WSL: wsl sudo apt-get install sshpass" -ForegroundColor Gray
    Write-Host "2. Or use manual SSH connection and run commands" -ForegroundColor Gray
    Write-Host "3. Or set up SSH keys: .\setup-ssh-keys.ps1" -ForegroundColor Gray
    exit 1
}

try {
    # Step 1: Get changes
    Write-Step "Getting changes from GitHub..." 1 7
    $result = sshpass -p $VPS_PASSWORD ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir; git pull origin main" 2>&1
    if ($LASTEXITCODE -ne 0) { throw "Error getting changes: $result" }
    Write-Success "Changes received"
    Write-Host ""

    # Step 2: Backend - Composer
    Write-Step "Installing Backend dependencies..." 2 7
    $result = sshpass -p $VPS_PASSWORD ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/backend; composer install --no-dev --optimize-autoloader --no-interaction" 2>&1
    if ($LASTEXITCODE -ne 0) { throw "Error installing dependencies: $result" }
    Write-Success "Dependencies installed"
    Write-Host ""

    # Step 3: Backend - Migrations
    Write-Step "Running migrations..." 3 7
    $result = sshpass -p $VPS_PASSWORD ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/backend; php artisan migrate --force" 2>&1
    if ($LASTEXITCODE -ne 0) { throw "Error running migrations: $result" }
    Write-Success "Migrations completed"
    Write-Host ""

    # Step 4: Backend - Cache
    Write-Step "Clearing and updating cache..." 4 7
    $result = sshpass -p $VPS_PASSWORD ssh -o ConnectTimeout=20 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/backend; php artisan config:cache; php artisan route:cache; php artisan view:cache; php artisan optimize" 2>&1
    if ($LASTEXITCODE -ne 0) { throw "Error updating cache: $result" }
    Write-Success "Cache updated"
    Write-Host ""

    # Step 5: Frontend - Install dependencies
    Write-Step "Installing Frontend dependencies..." 5 7
    $result = sshpass -p $VPS_PASSWORD ssh -o ConnectTimeout=60 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/frontend; npm install --silent" 2>&1
    if ($LASTEXITCODE -ne 0) { throw "Error installing Frontend dependencies: $result" }
    Write-Success "Frontend dependencies installed"
    Write-Host ""

    # Step 6: Frontend - Build
    Write-Step "Building Frontend..." 6 7
    $result = sshpass -p $VPS_PASSWORD ssh -o ConnectTimeout=120 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/frontend; npm run build" 2>&1
    if ($LASTEXITCODE -ne 0) { throw "Error building Frontend: $result" }
    Write-Success "Frontend built"
    Write-Host ""

    # Step 7: Permissions and restart
    Write-Step "Setting permissions and restarting services..." 7 7
    $result = sshpass -p $VPS_PASSWORD ssh -o ConnectTimeout=20 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir; chown -R www-data:www-data .; chmod -R 775 backend/storage backend/bootstrap/cache; systemctl restart php8.2-fpm; systemctl reload nginx; systemctl restart account-arena-worker 2>&1 || true" 2>&1
    if ($LASTEXITCODE -ne 0) { throw "Error setting permissions or restarting: $result" }
    Write-Success "Permissions set, services restarted"
    Write-Host ""

    Write-Host "================================================================" -ForegroundColor Green
    Write-Host "        DEPLOY COMPLETED SUCCESSFULLY!" -ForegroundColor Green
    Write-Host "================================================================" -ForegroundColor Green
    Write-Host ""
}
catch {
    Write-Host ""
    Write-ErrorMsg "$_"
    Write-Host ""
    exit 1
}
