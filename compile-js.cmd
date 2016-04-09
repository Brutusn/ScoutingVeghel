@echo off
echo Minifing javascript.
java -jar ./js-compile/compiler.jar --compilation_level SIMPLE_OPTIMIZATIONS --language_in=ECMASCRIPT6 --language_out=ECMASCRIPT5_STRICT --js ./js/scripts.origineel.js --js_output_file ./js/scripts.js
pause