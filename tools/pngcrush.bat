IF "%1" == "" GOTO full

rem �h���b�O���h���b�v���̓���
move %1 %1.bak
pngcrush -rem alla -reduce -brute %1.bak %1
rem pngcrush -rem alla -l 9 "%1.bak" "%1"
GOTO end

:full
rem ����f�B���N�g���̈��k����png��out�f�B���N�g���ɏo��
pngcrush -rem alla -reduce -brute -d "./out" *

:end