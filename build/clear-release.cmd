set /p input= Delete "Release" folder? (y/n)
IF %input%==y (
echo Clearing Release folder
  IF EXIST "Release" (
      rmdir "Release" /s /q
  )
) ELSE (
  echo No deleting of "Release". Remember old files could be still be present. 
)
pause
