@echo off
@rem -clean-css
CALL lessc --no-color --clean-css="" ..\css\style.less > ..\css\style.min.css
CALL lessc --no-color --clean-css="" ..\css\calendar.less > ..\css\calendar.min.css
pause
