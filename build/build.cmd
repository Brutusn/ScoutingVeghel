@echo off
cd ..\

CALL build/clear-release.cmd

echo Calling less compile.
CALL build/compile-less.cmd

echo Calling JavaScript compile
CALL build/compile-js.cmd

echo Building release.
CALL build/dir_structure_release.cmd

::not needed since out css is already generated from less
::echo substitutin CSS references
::CALL build/substitute_css_references.cmd

echo substitutin JS references
CALL build/substitute_js_references.cmd

echo Substituting PHP settings
CALL build/substitute_php_settings.cmd

echo Substituting cron settings
CALL build/substitute_cron_settings.cmd

cd .\build
echo Done with building release, thanks for your time
pause
