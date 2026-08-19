@echo off
title TCM Informed Consent - Server Stopper
color 0C

echo =================================================================
echo        MENGHENTIKAN SERVER TCM INFORMED CONSENT & NGROK
echo =================================================================
echo.

echo [*] Menghentikan proses PHP...
taskkill /F /T /IM php.exe >nul 2>&1

echo [*] Menghentikan proses Ngrok...
taskkill /F /T /IM ngrok.exe >nul 2>&1

echo [*] Memastikan port 8000 bebas...
powershell -NoProfile -Command "Get-Process -Name php, ngrok -ErrorAction SilentlyContinue | Stop-Process -Force; Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue }" >nul 2>&1

echo.
echo =================================================================
echo   [OK] Semua proses server dan port 8000 berhasil dihentikan!
echo =================================================================
echo.
timeout /t 3
