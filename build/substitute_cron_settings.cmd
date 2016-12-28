cd .\Release\cron
Powershell.exe -executionpolicy remotesigned -File ../../build/cron_settings_replace.ps1
cd ..\..\
pause
