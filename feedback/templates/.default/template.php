<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
use Bitrix\Main\Web\Json;

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

$uniqueId = rand();
$jsObj = [
    'signedParams' => $this->getComponent()->getSignedParameters(),
    'formId' => "pf_feedback_form_$uniqueId",
    'buttonId' => "pf_feedback_button_$uniqueId",
    'componentName'=>$this->getComponent()->getName(),
];
?>
<form action="" id="<?= $jsObj['formId'] ?>">
    <input type="text" name="NAME">
    <textarea name="PREVIEW_TEXT" cols="30" rows="10"></textarea>
    <input type="file" multiple name="FILES[]">
    <input type="file" name="FILE">
    <?if($arParams["USE_CAPTCHA"] == "Y"):?>
        <div class="mf-captcha">
            <div class="mf-text"><?=GetMessage("MFT_CAPTCHA")?></div>
            <input type="hidden" name="captcha_sid" value="<?=$arResult["capCode"]?>">
            <img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["capCode"]?>" width="180" height="40" alt="CAPTCHA">
            <div class="mf-text"><?=GetMessage("MFT_CAPTCHA_CODE")?><span class="mf-req">*</span></div>
            <input type="text" name="captcha_word" size="30" maxlength="50" value="">
        </div>
    <?endif;?>
    <button type="button" id="<?= $jsObj['buttonId'] ?>">Submit</button>
</form>
<script type="text/javascript">
    const pf_feedback_signed_<?=$uniqueId?> = new FpFeedback(<?=Json::encode($jsObj)?>);
</script>
