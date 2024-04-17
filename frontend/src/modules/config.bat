@echo off


SetLocal EnableDelayedExpansion

cd .\src\modules\
echo { > .\config.json
for /d %%d in (*.*) do set lastTask=%%d
for /d %%d in (*.*) do (
    if %%d neq locales (
        set task=%%d
        echo    "!task!": {  >> .\config.json
        cd !task!
        for /d %%d in (*.*) do (
            set module=%%d
            if !module! neq settings if !module! neq output (
                set modulePath=!module!\
                set dictPath=!task!
                if !task! neq none (
                    echo        "!module!": {  >> .\..\config.json
                    set dictPath=!task!/!module!
                ) else (
                    set modulePath=
                )
                call :addModuleConfig modulePath dictPath task
                if !task! neq none (
                    echo        },  >> .\..\config.json
                )
            )
        )

        if exist ..\common\visualisation_master\ (
            if !task!==brainstorming (
                call :addVisualisationMaster task
            )
            if !task!==information (
                call :addVisualisationMaster task
            )
            if !task!==selection (
                call :addVisualisationMaster task
            )
            if !task!==voting (
                call :addVisualisationMaster task
            )
        )

        if exist settings (
            echo        "settings": {  >> .\..\config.json
            if exist settings\TaskParameter.vue (
                echo            "parameter": "TaskParameter",  >> .\..\config.json
            )
            echo            "path": "!task!/settings"  >> .\..\config.json
            echo        }  >> .\..\config.json
        )
        cd ..
        if !task! neq !lastTask! (
            echo    },  >> .\config.json
        ) else echo    }  >> .\config.json
    )
)
echo } >> .\config.json
cd .\..\..\
EndLocal
EXIT /B 0
@echo on

:addVisualisationMaster
set task=!%~1!
set modulePath=visualisation_master\
set dictPath=common/visualisation_master
set currentPath=%cd%
cd .\..\common

echo        "visualisation_master": {  >> .\..\config.json
call :addModuleConfig modulePath dictPath task
echo        },  >> .\..\config.json
cd !currentPath!
EXIT /B 0

:addModuleConfig
                set modulePath=!%~1!
                set dictPath=!%~2!
                set task=!%~3!
                if exist !modulePath!output\PublicScreen.vue (
                    echo            "publicScreen": "output/PublicScreen",  >> .\..\config.json
                )
                if exist !modulePath!output\Participant.vue (
                    echo            "participant": "output/Participant",  >> .\..\config.json
                )
                if exist !modulePath!output\ModeratorContent.vue (
                    echo            "moderatorContent": "output/ModeratorContent",  >> .\..\config.json
                )
                if exist !modulePath!output\ModeratorConfig.vue (
                    echo            "moderatorConfig": "output/ModeratorConfig",  >> .\..\config.json
                )
                if exist !modulePath!output\ModuleStatistic.vue (
                    echo            "moduleStatistic": "output/ModuleStatistic",  >> .\..\config.json
                )

                if exist !modulePath!locales (
                    cd !modulePath!locales
                    set locales=
                    for /f "delims=" %%G in ('dir *.json /b') do (
                        set language=%%~G
                        set locales=!locales!!language:~0,2!,
                    )
                    if !task! neq none ( cd ..\..
                    ) else cd ..
                    echo            "locales": "!locales!",  >> .\..\config.json
                )

                if exist !modulePath!config.json (
                    set moduleConfig=
                    for /f "delims=" %%x in (!modulePath!config.json) do (
                        if %%x neq { if %%x neq } (
                            set moduleConfig=!moduleConfig!%%x
                        )
                    )
                    echo            !moduleConfig!,  >> .\..\config.json
                )
                if !task! neq none (
                    echo            "path": "!dictPath!"  >> .\..\config.json
                ) else (
                    echo            "path": "!dictPath!",  >> .\..\config.json
                )
EXIT /B 0