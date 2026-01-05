# Скрипт для добавления SSH ключа на сервер используя пароль
# Использование: .\add-ssh-key-to-server.ps1

$ErrorActionPreference = "Stop"

$SERVER_IP = "31.131.26.78"
$SERVER_USER = "root"
$SERVER_PASSWORD = "7gEbjQZayw2R7N86F3"
$PUB_KEY_PATH = "$env:USERPROFILE\.ssh\id_rsa_account_arena.pub"

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "  ADDING SSH KEY TO SERVER" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path $PUB_KEY_PATH)) {
    Write-Host "[ERROR] SSH public key not found at $PUB_KEY_PATH" -ForegroundColor Red
    Write-Host "Please run .\setup-ssh-keys.ps1 first" -ForegroundColor Yellow
    exit 1
}

$publicKey = Get-Content $PUB_KEY_PATH -Raw
$publicKey = $publicKey.Trim()

Write-Host "Public key to add:" -ForegroundColor Yellow
Write-Host $publicKey -ForegroundColor Gray
Write-Host ""

# Создаем временный скрипт для выполнения на сервере
$tempScript = [System.IO.Path]::GetTempFileName()
$scriptContent = @"
#!/bin/bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
# Проверяем, нет ли уже этого ключа
if ! grep -Fxq "$publicKey" ~/.ssh/authorized_keys 2>/dev/null; then
    echo "$publicKey" >> ~/.ssh/authorized_keys
    chmod 600 ~/.ssh/authorized_keys
    echo "Key added successfully"
else
    echo "Key already exists"
fi
"@

$scriptContent | Out-File -FilePath $tempScript -Encoding UTF8 -NoNewline

Write-Host "Attempting to add key to server..." -ForegroundColor Yellow
Write-Host "Server: $SERVER_USER@$SERVER_IP" -ForegroundColor Gray
Write-Host ""

# Используем sshpass если доступен, иначе используем обычный ssh с интерактивным вводом
$sshpassAvailable = Get-Command sshpass -ErrorAction SilentlyContinue

if ($sshpassAvailable) {
    Write-Host "Using sshpass for password authentication..." -ForegroundColor Yellow
    $command = "cat `"$tempScript`" | sshpass -p '$SERVER_PASSWORD' ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP 'bash'"
    Invoke-Expression $command
} else {
    Write-Host "sshpass not found. Using manual method." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Please run these commands manually:" -ForegroundColor Yellow
    Write-Host "1. Connect to server: ssh $SERVER_USER@$SERVER_IP" -ForegroundColor Cyan
    Write-Host "   Password: $SERVER_PASSWORD" -ForegroundColor Gray
    Write-Host ""
    Write-Host "2. Run these commands on the server:" -ForegroundColor Cyan
    Write-Host "   mkdir -p ~/.ssh" -ForegroundColor Gray
    Write-Host "   chmod 700 ~/.ssh" -ForegroundColor Gray
    Write-Host "   echo '$publicKey' >> ~/.ssh/authorized_keys" -ForegroundColor Gray
    Write-Host "   chmod 600 ~/.ssh/authorized_keys" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Or copy this command and run it on the server:" -ForegroundColor Yellow
    Write-Host "echo '$publicKey' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys" -ForegroundColor Cyan
    Write-Host ""
    
    # Попытка автоматического выполнения через ssh (может запросить пароль)
    Write-Host "Attempting automatic addition (you may be prompted for password)..." -ForegroundColor Yellow
    $command = "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '$publicKey' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys && echo 'Key added'"
    
    try {
        $result = ssh -o StrictHostKeyChecking=no "$SERVER_USER@$SERVER_IP" $command 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "[SUCCESS] Key added to server!" -ForegroundColor Green
        } else {
            Write-Host "[INFO] Automatic addition failed. Please use manual method above." -ForegroundColor Yellow
        }
    } catch {
        Write-Host "[INFO] Please use manual method above." -ForegroundColor Yellow
    }
}

# Удаляем временный файл
Remove-Item $tempScript -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "Testing connection..." -ForegroundColor Yellow
Start-Sleep -Seconds 2
$test = ssh -o ConnectTimeout=5 -o BatchMode=yes account-arena-server "echo 'Connection successful'" 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "[SUCCESS] Connection test passed! SSH key is working." -ForegroundColor Green
} else {
    Write-Host "[WARNING] Connection test failed. Key may need to be added manually." -ForegroundColor Yellow
    Write-Host "After adding the key manually, test with: ssh account-arena-server 'echo test'" -ForegroundColor Gray
}
