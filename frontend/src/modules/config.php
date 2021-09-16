<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Credentials: true");

$config = array(
    'brainstorming' => array(),
    'categorisation' => array(),
    'information' => array(),
    'selection' => array(),
    'voting' => array(),
    'none' => array(),
);

function createDetailConfig($path) {
    $detailConfig = array('path' => $path);

    $pathConfig = "$path/config.json";
    if (file_exists($pathConfig)) {
        $customConfig = json_decode(file_get_contents($pathConfig));
        $detailConfig = array_merge($detailConfig, (array)$customConfig);
    }

    $pathLocales = "$path/locales";
    if (file_exists($pathLocales)) {
        $locales = scandir($pathLocales);
        if (count($locales) > 0) {
            $locale_list = "";
            foreach($locales as $localeFile) {
                if($localeFile == '.' || $localeFile == '..') continue;
                $locale = explode('.', $localeFile)[0];
                $locale_list = "$locale_list$locale, ";
            }
            $detailConfig['locales'] = $locale_list;
        }
    }

    $pathOrganisms = "$path/organisms";
    if (file_exists($pathOrganisms)) {
        $organisms = scandir($pathOrganisms);
        if (in_array("ModeratorContentComponent.vue", $organisms))
            $detailConfig['moderatorContent'] = "organisms/ModeratorContentComponent";
        if (in_array("PublicScreenComponent.vue", $organisms))
            $detailConfig['publicScreen'] = "organisms/PublicScreenComponent";
        if (in_array("ModeratorConfig.vue", $organisms))
            $detailConfig['moderatorConfig'] = "organisms/ModeratorConfig";
        if (in_array("ParticipantView.vue", $organisms))
            $detailConfig['participant'] = "organisms/ParticipantView";
    }

    $pathViews = "$path/views";
    if (file_exists($pathViews)) {
        $views = scandir($pathViews);
        if (in_array("ParticipantView.vue", $views))
            $detailConfig['participant'] = "views/ParticipantView";
    }

    $settingFiles = scandir($path);
    if (in_array("TaskParameter.vue", $settingFiles))
        $detailConfig['parameter'] = "TaskParameter";

    return $detailConfig;
}

function createConfig($dir) {
    global $config;
    $files = scandir($dir);
    foreach($files as $file) {
        if($file == '.' || $file == '..') continue;
        $path = "$dir/$file";
        $config[$dir][$file] = createDetailConfig($path);
    }
};

createConfig('brainstorming');
createConfig('categorisation');
createConfig('information');
createConfig('selection');
createConfig('voting');
$config['none'] = createDetailConfig('none');
$config['none']['settings'] = createDetailConfig('none/settings');

echo json_encode($config);

?>
