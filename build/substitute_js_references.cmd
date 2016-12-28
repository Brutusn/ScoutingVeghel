cd .\Release
Powershell.exe -executionpolicy remotesigned -File ../build/js_refs_replace.ps1
cd ..\
pause
