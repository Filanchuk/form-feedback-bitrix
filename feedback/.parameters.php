<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arCurrentValues */

use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
    "PARAMETERS" => [
        "AJAX_MODE" => [],
        "IBLOCK_ADD" => [
            "NAME" => Loc::getMessage('FPF_IBLOCK_ADD'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
            "PARENT" => "BASE",
            'REFRESH' => 'Y',
        ],
        "USE_CAPTCHA" => [
            "NAME" => Loc::getMessage('FPF_CAPTCHA'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
            "PARENT" => "VISUAL",
        ],
        "EVENT_MESSAGE_ID" => [
            "NAME" => Loc::getMessage('FPF_EMAIL_TEMPLATES'),
            "TYPE" => "STRING",
            "PARENT" => "DATA_SOURCE",
        ],
    ]
];
if ($arCurrentValues['IBLOCK_ADD'] === 'Y') {
    $arComponentParameters['PARAMETERS']['IBLOCK_ID'] = [
        "NAME" => Loc::getMessage('FPF_IBLOCK_ID'),
        "TYPE" => "STRING",
        "PARENT" => "BASE",
    ];
}
