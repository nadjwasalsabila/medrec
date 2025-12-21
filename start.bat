@echo off
echo ========================================
echo    STARTING MEDREC TRANSFER SYSTEM
echo ========================================
echo.

echo [1] Starting PocketBase Server...
cd pocketbase
start "PocketBase" pocketbase.exe serve
cd ..
echo    PocketBase running at: http://localhost:8090
echo.

echo [2] Opening Browser...
timeout /t 3 /nobreak > nul
start http://localhost/medrec/
echo    Application running at: http://localhost/medrec/
echo.

echo [3] Setup Instructions:
echo    - Setup database: http://localhost/medrec/setup_manual.php
echo    - Admin panel: http://localhost:8090/_/
echo    - Default login: RS001 / rs001pass
echo.

echo [4] Press any key to stop servers...
pause > nul

echo.
echo Stopping servers...
taskkill /F /IM pocketbase.exe > nul 2>&1
echo All servers stopped.