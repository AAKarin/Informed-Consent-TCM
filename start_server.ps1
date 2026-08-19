Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "       MENJALANKAN SERVER TCM INFORMED CONSENT & NGROK" -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Matikan proses lama
Write-Host "[*] Membersihkan proses lama..." -ForegroundColor Yellow
Get-Process -Name php, ngrok -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue

# 2. Jalankan PHP Server di Background
Write-Host "[*] Memulai PHP Server di port 8000..." -ForegroundColor Yellow
Start-Process -FilePath "php" -ArgumentList "-S localhost:8000 -t public" -WindowStyle Hidden

Start-Sleep -Seconds 2

# 3. Jalankan Ngrok
Write-Host "[*] Memulai Ngrok Tunnel..." -ForegroundColor Yellow
$ngrokPath = "C:\Program Files\ngrok\ngrok.exe"
if (Test-Path $ngrokPath) {
    Start-Process -FilePath $ngrokPath -ArgumentList "http 8000"
} else {
    Start-Process -FilePath "ngrok" -ArgumentList "http 8000"
}

Write-Host ""
Write-Host "[OK] Server PHP dan Ngrok Berhasil Dijalankan!" -ForegroundColor Green
Write-Host "  - Local URL : http://localhost:8000"
Write-Host "  - Public URL: Lihat di jendela Ngrok yang terbuka"
Write-Host ""
Write-Host "Untuk menghentikan server, jalankan: .\stop_server.ps1" -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan
