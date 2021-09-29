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
                if !task! neq none echo        "!module!": {  >> .\..\config.json
                set modulePath=!module!\
                if !task! == none set modulePath=
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
                    echo            "path": "!task!/!module!"  >> .\..\config.json
                    echo        },  >> .\..\config.json
                ) else (
                    echo            "path": "!task!",  >> .\..\config.json
                )
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
@echo on