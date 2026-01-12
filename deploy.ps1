# PowerShell script for deploying Account Arena to server
# Usage: .\deploy.ps1

$ErrorActionPreference = "Stop"

# Settings
$VPS = if ($env:SSH_HOST) { $env:SSH_HOST } else { "account-arena-server" }
$ProjectDir = "/var/www/account-arena"

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
Write-Host "        DEPLOY ACCOUNT ARENA" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

# Test SSH connection first
Write-Host "Testing SSH connection..." -ForegroundColor Yellow
$testResult = ssh -o ConnectTimeout=5 -o BatchMode=yes -o StrictHostKeyChecking=no $VPS "echo 'OK'" 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Cannot connect to server: $testResult" -ForegroundColor Red
    Write-Host ""
    Write-Host "Possible solutions:" -ForegroundColor Yellow
    Write-Host "1. Run .\add-ssh-key-to-server.ps1 to add SSH key to server" -ForegroundColor Yellow
    Write-Host "2. Or use deploy-with-password.ps1 for password authentication" -ForegroundColor Yellow
    Write-Host "3. Or use direct IP: `$env:SSH_HOST = 'root@31.131.26.78'" -ForegroundColor Yellow
    Write-Host "4. Check SSH config: cat `$env:USERPROFILE\.ssh\config" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "To add SSH key, you need to:" -ForegroundColor Cyan
    Write-Host "1. Connect manually: ssh root@31.131.26.78" -ForegroundColor Gray
    Write-Host "2. Run: mkdir -p ~/.ssh && chmod 700 ~/.ssh" -ForegroundColor Gray
    Write-Host "3. Add your public key to ~/.ssh/authorized_keys" -ForegroundColor Gray
    Write-Host "4. Run: chmod 600 ~/.ssh/authorized_keys" -ForegroundColor Gray
    Write-Host ""
    exit 1
}
Write-Host "[OK] SSH connection successful" -ForegroundColor Green
Write-Host ""

try {
    # Step 1: Get changes
    Write-Step "Getting changes from GitHub..." 1 7
    $null = ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir; git pull origin main"
    if ($LASTEXITCODE -ne 0) { throw "Error getting changes" }
    Write-Success "Changes received"
    Write-Host ""

    # Step 2: Backend - Composer
    Write-Step "Installing Backend dependencies..." 2 7
    $null = ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/backend; composer install --no-dev --optimize-autoloader --no-interaction"
    if ($LASTEXITCODE -ne 0) { throw "Error installing dependencies" }
    Write-Success "Dependencies installed"
    Write-Host ""

    # Step 3: Backend - Migrations
    Write-Step "Running migrations..." 3 7
    $null = ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/backend; php artisan migrate --force"
    if ($LASTEXITCODE -ne 0) { throw "Error running migrations" }
    Write-Success "Migrations completed"
    Write-Host ""

    # Step 4: Backend - Cache
    Write-Step "Clearing and updating cache..." 4 7
    $null = ssh -o ConnectTimeout=20 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/backend; php artisan config:cache; php artisan route:cache; php artisan view:cache; php artisan optimize"
    if ($LASTEXITCODE -ne 0) { throw "Error updating cache" }
    Write-Success "Cache updated"
    Write-Host ""

    # Step 5: Frontend - Install dependencies
    Write-Step "Installing Frontend dependencies..." 5 7
    $null = ssh -o ConnectTimeout=60 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/frontend; npm install --silent"
    if ($LASTEXITCODE -ne 0) { throw "Error installing Frontend dependencies" }
    Write-Success "Frontend dependencies installed"
    Write-Host ""

    # Step 6: Frontend - Build
    Write-Step "Building Frontend..." 6 7
    $null = ssh -o ConnectTimeout=120 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir/frontend; npm run build"
    if ($LASTEXITCODE -ne 0) { throw "Error building Frontend" }
    Write-Success "Frontend built"
    Write-Host ""

    # Step 7: Permissions and restart
    Write-Step "Setting permissions and restarting services..." 7 7
    $null = ssh -o ConnectTimeout=20 -o StrictHostKeyChecking=no $VPS "cd $ProjectDir; chown -R www-data:www-data .; chmod -R 775 backend/storage backend/bootstrap/cache; systemctl restart php8.2-fpm; systemctl reload nginx; systemctl restart account-arena-worker 2>&1 || true"
    if ($LASTEXITCODE -ne 0) { throw "Error setting permissions or restarting" }
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
