Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "       MENGHENTIKAN SERVER TCM INFORMED CONSENT & NGROK" -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "[*] Menghentikan proses PHP dan Ngrok..." -ForegroundColor Yellow
Get-Process -Name php, ngrok -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue

Write-Host "[*] Membersihkan proses di port 8000..." -ForegroundColor Yellow
try {
    $connections = Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue
    foreach ($conn in $connections) {
        Stop-Process -Id $conn.OwningProcess -Force -ErrorAction SilentlyContinue
    }
} catch {
    # Ignore
}

Write-Host ""
Write-Host "[OK] Semua server (PHP & Ngrok) berhasil dihentikan!" -ForegroundColor Green
Write-Host "=================================================================" -ForegroundColor Cyan
