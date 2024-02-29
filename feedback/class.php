<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\UserTable;

Loader::includeModule('iblock');


class FpFeedback extends \CBitrixComponent implements Controllerable
{
    /**
     * Errors
     * @var array
     */
    private array $errors = [];

    /**
     * Clear prefilter
     * @return array[]
     */
    public function configureActions(): array
    {
        return [
            'send' => [
                'prefilters' => [],
            ],
        ];
    }

    /**
     * Add one error to $this->errors
     * @param string $error
     * @param string $code
     * @return void
     */
    public function setError(string $error, string $code): void
    {
        $this->errors[$code] = $error;
    }

    /**
     * List of keys of parameters which the component have to sign
     * @return string[]
     */
    protected function listKeysSignedParameters(): array
    {
        return [
            'EVENT_MESSAGE_ID',
            'IBLOCK_ADD',
            'IBLOCK_ID',
            'USE_CAPTCHA',
        ];
    }

    /**
     * Start sending data
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    public function sendAction(): array
    {
        $this->defaultValidation();
        $this->customValidation();

        if (!empty($this->errors)) {
            $result = ['status' => 'e', 'errors' => $this->errors];
        } else {
            $this->processSaveIblockElement();
            $this->processPost();
            $result = ['status' => 's', 'msg' => Loc::getMessage('FPF_OK_MESSAGE')];
        }
        return $result;
    }

    /**
     * Default validation
     * @return void
     */
    private function defaultValidation(): void
    {
        global $USER;
        if ($this->arParams['USE_CAPTCHA'] == 'Y' && !$USER->IsAuthorized()) {
            include_once(Application::getDocumentRoot() . "/bitrix/modules/main/classes/general/captcha.php");
            $captcha_code = $this->request["captcha_sid"];
            $captcha_word = $this->request["captcha_word"];
            $cpt = new CCaptcha();
            $captchaPass = Option::get('main', 'captcha_password', "");
            if (strlen($captcha_word) > 0 && strlen($captcha_code) > 0) {
                if (!$cpt->CheckCodeCrypt($captcha_word, $captcha_code, $captchaPass)) {
                    $this->setError(Loc::getMessage("FPF_CAPTCHA_WRONG"), 'captcha');
                }
            } else {
                $this->setError(Loc::getMessage("FPF_CAPTCHA_EMPTY"), 'captcha');
            }
        }
    }

    /**
     * Custom validation
     * This function is designed for custom validation fill errors
     * Use $this->request
     * @return void
     */
    private function customValidation(): void
    {
    }

    /**
     * Save data to iblock
     * @return void
     * @throws \Bitrix\Main\LoaderException
     */
    private function processSaveIblockElement(): void
    {
        $elementData = $this->request->getPostList()->toArray() + $this->getFormatedFilesArray();
        if ($this->arParams['IBLOCK_ADD'] == 'Y' && $this->arParams['IBLOCK_ID'] > 0 && Loader::includeModule('iblock')) {
            $el = new \CIBlockElement;
            $arLoadProductArray = [
                "IBLOCK_ID" => intval($this->arParams['IBLOCK_ID']),
                "PROPERTY_VALUES" => $elementData,
                "NAME" => Loc::getMessage('FPF_NAME', ['#DATE#' => date('d.m.Y')]),
                "ACTIVE" => "N",
                'PREVIEW_TEXT' => $this->request['PREVIEW_TEXT']
            ];
            $el->Add($arLoadProductArray);
        }
    }

    /**
     * Send Email
     * @return void
     */
    private function processPost(): void
    {
        if ($this->arParams['EVENT_MESSAGE_ID'] > 0) {
            $arFileList = $this->getFormatedFilesArray();
            if ($arFileList) {
                foreach ($arFileList as $fileGroup) {
                    if (is_array(reset($fileGroup))) {
                        foreach ($fileGroup as $item) {
                            $filesID[] = CFile::SaveFile($item + ['MODULE_ID' => 'main'], "feedback");
                        }
                    } else {
                        $filesID[] = CFile::SaveFile($fileGroup + ['MODULE_ID' => 'main'], "feedback");
                    }
                }
            }
            Event::send([
                "EVENT_NAME" => "MAIN_FEEDBACK",
                'MESSAGE_ID' => $this->arParams['EVENT_MESSAGE_ID'],
                "LID" => SITE_ID,
                "C_FIELDS" => $this->request->getPostList()->toArray(),
                'FILE' => $filesID ?? []
            ]);
        }
    }

    /**
     * Create correct array to save files in DB
     * @return array
     */
    private function getFormatedFilesArray(): array
    {
        $allFiles = $this->request->getFileList()?->toArray();
        $fileList = [];
        if ($allFiles) {
            foreach ($allFiles as $code => $filesData) {
                if (is_array($filesData['name'])) {
                    foreach ($filesData['name'] as $keyFileField => $fileField) {
                        if ($filesData['error'][$keyFileField] != 0) {
                            continue;
                        }
                        $fileList[$code]['n' . $keyFileField] = [
                            'name' => $fileField,
                            'type' => $filesData['type'][$keyFileField],
                            'tmp_name' => $filesData['tmp_name'][$keyFileField],
                            'error' => $filesData['error'][$keyFileField],
                            'size' => $filesData['size'][$keyFileField],
                        ];
                    }
                } else {
                    if ($filesData['error'] != 0) {
                        continue;
                    }
                    $fileList[$code] = $filesData;
                }
            }
        }
        return $fileList;
    }

    /**
     * Init Result
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function setResult(): void
    {
        global $APPLICATION;
        global $USER;

        if ($this->arParams["USE_CAPTCHA"] == "Y") {
            $this->arResult['capCode'] = htmlspecialcharsbx($APPLICATION->CaptchaGetCode());
        }
        if ($USER->IsAuthorized()) {
            $this->arResult['USER'] = UserTable::getList([
                'select' => ['*', 'UF_*'],
                'filter' => ['ID' => $USER->GetID()]
            ])?->fetch();
        }
    }

    /**
     * Init Params
     * @return void
     */
    public function setParams(): void
    {
        global $USER;
        $this->arParams["USE_CAPTCHA"] = (($this->arParams["USE_CAPTCHA"] != "N" && !$USER->IsAuthorized()) ? "Y" : "N");
    }

    /**
     * Execute component
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function executeComponent(): void
    {
        CJSCore::Init(["fx", "ajax"]);
        $this->setParams();
        $this->setResult();
        $this->includeComponentTemplate();
    }
}
