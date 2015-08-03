@echo off
@rem -clean-css
CALL node node_modules\less\bin\lessc --no-color --clean-css="" css\style.less > css\style.min.css
pause