<?php

namespace Boilerplate\Tools\Form;

use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Loader;
use CAllFormResult;
use CEvent;
use CEventMessage;
use CForm;
use CFormAnswer;
use CFormField;
use CFormResult;
use CSite;
use CTimeZone;
use CUser;
use Boilerplate\Tools\Content\ContentTable;

abstract class Form
{
    protected string $code;
    protected array $message;

    protected array $data;
    protected array $questions;
    protected array $answers;

    protected array $content;

    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function __construct(protected string $lang)
    {
        Loader::includeModule('form');

        $this->content = ContentTable::getPageContent('common', $this->lang)['common'];

        $this->code = $this->setFormCode();
        $this->message = $this->setFormMessage();

        $this->data = $this->getData();
    }

    abstract public function setFormCode(): string;

    abstract public function setFormMessage(): array;

    protected function getData(): array
    {
        return (CForm::GetBySID($this->code))->fetch();
    }

    protected function getQuestions(): array
    {
        $output = [];

        $fields = CFormField::GetList(
            $this->data['ID'],
            'N',
            's_sort',
            'asc',
            [
                'ACTIVE' => 'Y',
            ]
        );

        while ($field = $fields->fetch()) {
            $output[] = $field;
        }

        return $output;
    }

    protected function getAnswers(): array
    {
        $output = [];

        foreach ($this->questions as $field) {
            $answers = CFormAnswer::GetList(
                $field['ID'],
                's_sort',
                'asc',
                [
                    'ACTIVE' => 'Y',
                ]
            );

            while ($answer = $answers->fetch()) {
                $output[$field['ID']][] = $answer;
            }
        }

        return $output;
    }

    protected function combineFields(): array
    {
        $output = [];

        foreach ($this->questions as $question) {
            $f = [
                'ID'       => $question['ID'],
                'CODE'     => $question['SID'],
                'LABEL'    => $question['TITLE'],
                'SORT'     => $question['C_SORT'],
                'REQUIRED' => $question['REQUIRED'] === 'Y',
                'COMMENTS' => $question['COMMENTS'],
            ];

            $answers = $this->answers[$question['ID']];

            if (!$answers) {
                continue;
            }

            if ((is_countable($answers) ? count($answers) : 0) === 1) {
                $f['TYPE'] = $answers[0]['FIELD_TYPE'];

                switch ($answers[0]['FIELD_TYPE']) {
                    case 'text':
                        $f['NAME'] = "form_text_{$answers[0]['ID']}";
                        break;
                    case 'textarea':
                        $f['NAME'] = "form_textarea_{$answers[0]['ID']}";
                        break;
                    case 'date':
                        $f['NAME'] = "form_date_{$answers[0]['ID']}";
                        break;
                    case 'image':
                        $f['NAME'] = "form_image_{$answers[0]['ID']}";
                        break;
                    case 'file':
                        $f['NAME'] = "form_file_{$answers[0]['ID']}";
                        break;
                    case 'email':
                        $f['NAME'] = "form_email_{$answers[0]['ID']}";
                        break;
                    case 'url':
                        $f['NAME'] = "form_url_{$answers[0]['ID']}";
                        break;
                    case 'password':
                        $f['NAME'] = "form_password_{$answers[0]['ID']}";
                        break;
                    case 'hidden':
                        $f['NAME'] = "form_hidden_{$answers[0]['ID']}";
                        break;
                }
            } else {
                $f['TYPE'] = $answers[0]['FIELD_TYPE'];

                switch ($answers[0]['FIELD_TYPE']) {
                    case 'radio':
                        $f['NAME'] = "form_radio_{$question['SID']}";
                        break;
                    case 'checkbox':
                        $f['NAME'] = "form_checkbox_{$question['SID']}[]";
                        break;
                    case 'dropdown':
                        $f['NAME'] = "form_dropdown_{$question['SID']}";

                        foreach ($answers as $answer) {
                            $f['OPTIONS'][] = [
                                'ID'      => $answer['ID'],
                                'VALUE'   => $answer['VALUE'],
                                'MESSAGE' => $answer['MESSAGE'],
                            ];
                        }

                        break;
                    case 'multiselect':
                        $f['NAME'] = "form_multiselect_{$question['SID']}[]";
                        break;
                }
            }

            $output[] = $f;
        }

        return $output;
    }

    public function getFields(): array
    {
        // TODO: Закешировать

        $this->questions = $this->getQuestions();
        $this->answers = $this->getAnswers();

        return $this->combineFields();
    }

    public function getFormData(): array
    {
        return $this->data;
    }

    public function addFormResult(array $fields): Json
    {
        $res = new Json();
        $res->setStatus(200);

        if (!$fields) {
            $res->setData([
                'data'   => null,
                'status' => 'error',
                'errors' => [
                    'Missing form data'
                ],
            ]);

            return $res;
        }

        if ($fields['name']
            || $fields['email']
            || $fields['phone']
            || $fields['message']) {
            $res->setData([
                'data'   => null,
                'status' => 'error',
                'errors' => [
                    'Invalid form data'
                ],
            ]);

            return $res;
        }

        $cleanFields = [];
        foreach ($fields as $key => $value) {
            $k = filter_var(trim($key), FILTER_SANITIZE_STRING);
            $v = filter_var(trim((string) $value), FILTER_SANITIZE_STRING);

            if ($k && $v) {
                $cleanFields[$k] = $v;
            }
        }

        if (!$cleanFields) {
            $res->setData([
                'data'   => null,
                'status' => 'error',
                'errors' => [
                    'Missing form data'
                ],
            ]);

            return $res;
        }

        $resultId = CFormResult::Add($this->data['ID'], $cleanFields);

        if ($resultId && $this->mail($resultId)) {
            $res->setData([
                'data'   => [
                    'message' => $this->message,
                ],
                'status' => 'success',
                'errors' => null,
            ]);
        } else {

            global $strError;

            $res->setData([
                'data'   => null,
                'status' => 'error',
                'errors' => [
                    $strError,
                ],
            ]);
        }

        return $res;
    }

    /*
     * Копия метода ядра
     */
    public function mail($RESULT_ID, $TEMPLATE_ID = false): bool
    {
        $arrRES = [];
        $arrANSWER = null;
        global $APPLICATION, $DB, $MESS, $strError;

        $err_mess = (CAllFormResult::err_mess()) . "<br>Function: Mail<br>Line: ";
        $RESULT_ID = intval($RESULT_ID);

        CTimeZone::Disable();
        $arrResult = CFormResult::GetDataByID($RESULT_ID, [], $arrRES, $arrANSWER);
        CTimeZone::Enable();
        if ($arrResult) {
            $z = CForm::GetByID($arrRES["FORM_ID"]);
            if ($arrFORM = $z->Fetch()) {
                $TEMPLATE_ID = intval($TEMPLATE_ID);

                $arrFormSites = CForm::GetSiteArray($arrRES["FORM_ID"]);
                $arrFormSites = (is_array($arrFormSites)) ? $arrFormSites : [];

                if (!in_array($this->lang, $arrFormSites)) {
                    return true;
                }

                $rs = CSite::GetList("sort", "asc", ['ID' => implode('|', $arrFormSites)]);
                $arrSites = [];
                while ($ar = $rs->Fetch()) {
                    if ($ar["DEF"] == "Y") {
                        $def_site_id = $ar["ID"];
                    }
                    $arrSites[$ar["ID"]] = $ar;
                }

                $arrFormTemplates = CForm::GetMailTemplateArray($arrRES["FORM_ID"]);
                $arrFormTemplates = (is_array($arrFormTemplates)) ? $arrFormTemplates : [];

                $arrTemplates = [];
                $rs = CEventMessage::GetList("id", "asc", [
                    "ACTIVE"     => "Y",
                    "SITE_ID"    => $this->lang,
                    "EVENT_NAME" => $arrFORM["MAIL_EVENT_TYPE"],
                ]);

                while ($ar = $rs->Fetch()) {
                    if ($TEMPLATE_ID > 0) {
                        if ($TEMPLATE_ID == $ar["ID"]) {
                            $arrTemplates[$ar["ID"]] = $ar;
                            break;
                        }
                    } elseif (in_array($ar["ID"], $arrFormTemplates)) {
                        $arrTemplates[$ar["ID"]] = $ar;
                    }
                }

                foreach ($arrTemplates as $arrTemplate) {
                    $OLD_MESS = $MESS;
                    $MESS = [];
                    IncludeModuleLangFile(
                        $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/form/admin/form_mail.php",
                        $arrSites[$arrTemplate["SITE_ID"]]["LANGUAGE_ID"]
                    );

                    $USER_AUTH = " ";
                    if (intval($arrRES["USER_ID"]) > 0) {
                        $w = CUser::GetByID($arrRES["USER_ID"]);
                        $arrUSER = $w->Fetch();
                        $USER_ID = $arrUSER["ID"];
                        $USER_EMAIL = $arrUSER["EMAIL"];
                        $USER_NAME = $arrUSER["NAME"] . " " . $arrUSER["LAST_NAME"];
                        if ($arrRES["USER_AUTH"] != "Y") {
                            $USER_AUTH = "(" . GetMessage("FORM_NOT_AUTHORIZED") . ")";
                        }
                    } else {
                        $USER_ID = GetMessage("FORM_NOT_REGISTERED");
                        $USER_NAME = "";
                        $USER_AUTH = "";
                        $USER_EMAIL = "";
                    }

                    $arEventFields = [
                        "RS_FORM_ID"         => $arrFORM["ID"],
                        "RS_FORM_NAME"       => $arrFORM["NAME"],
                        "RS_FORM_VARNAME"    => $arrFORM["SID"],
                        "RS_FORM_SID"        => $arrFORM["SID"],
                        "RS_RESULT_ID"       => $arrRES["ID"],
                        "RS_DATE_CREATE"     => $arrRES["DATE_CREATE"],
                        "RS_USER_ID"         => $USER_ID,
                        "RS_USER_EMAIL"      => $USER_EMAIL,
                        "RS_USER_NAME"       => $USER_NAME,
                        "RS_USER_AUTH"       => $USER_AUTH,
                        "RS_STAT_GUEST_ID"   => $arrRES["STAT_GUEST_ID"],
                        "RS_STAT_SESSION_ID" => $arrRES["STAT_SESSION_ID"],
                    ];
                    $w = CFormField::GetList($arrFORM["ID"], "ALL");
                    while ($wr = $w->Fetch()) {
                        $answer = "";
                        $answer_raw = '';
                        if (is_array($arrResult[$wr["SID"]])) {
                            $bHasDiffTypes = false;
                            $lastType = '';
                            foreach ($arrResult[$wr['SID']] as $arrA) {
                                if ($lastType == '') {
                                    $lastType = $arrA['FIELD_TYPE'];
                                } elseif ($arrA['FIELD_TYPE'] != $lastType) {
                                    $bHasDiffTypes = true;
                                    break;
                                }
                            }

                            foreach ($arrResult[$wr["SID"]] as $arrA) {
                                if ($wr['ADDITIONAL'] == 'Y') {
                                    $arrA['FIELD_TYPE'] = $wr['FIELD_TYPE'];
                                }

                                $USER_TEXT_EXIST = (trim((string) $arrA["USER_TEXT"]) <> '');
                                $ANSWER_TEXT_EXIST = (trim((string) $arrA["ANSWER_TEXT"]) <> '');
                                $ANSWER_VALUE_EXIST = (trim((string) $arrA["ANSWER_VALUE"]) <> '');
                                $USER_FILE_EXIST = (intval($arrA["USER_FILE_ID"]) > 0);

                                if ($arrTemplate["BODY_TYPE"] == "html") {
                                    if (
                                        $bHasDiffTypes
                                        && !$USER_TEXT_EXIST
                                        && (
                                            $arrA['FIELD_TYPE'] == 'text'
                                            || $arrA['FIELD_TYPE'] == 'textarea'
                                        )
                                    ) {
                                        continue;
                                    }

                                    if (trim($answer) <> '') {
                                        $answer .= "<br />";
                                    }
                                    if (trim($answer_raw) <> '') {
                                        $answer_raw .= ",";
                                    }

                                    if ($ANSWER_TEXT_EXIST) {
                                        $answer .= $arrA["ANSWER_TEXT"] . ': ';
                                    }

                                    switch ($arrA['FIELD_TYPE']) {
                                        case 'text':
                                        case 'textarea':
                                        case 'hidden':
                                        case 'date':
                                        case 'password':
                                        case 'integer':

                                            if ($USER_TEXT_EXIST) {
                                                $answer .= trim((string) $arrA["USER_TEXT"]);
                                                $answer_raw .= trim((string) $arrA["USER_TEXT"]);
                                            }

                                            break;

                                        case 'email':
                                        case 'url':

                                            if ($USER_TEXT_EXIST) {
                                                $answer .= '<a href="' . ($arrA['FIELD_TYPE'] == 'email' ? 'mailto:'
                                                        : '') . trim((string) $arrA["USER_TEXT"]) . '">' . trim(
                                                        (string) $arrA["USER_TEXT"]
                                                    ) . '</a>';
                                                $answer_raw .= trim((string) $arrA["USER_TEXT"]);
                                            }

                                            break;

                                        case 'checkbox':
                                        case 'multiselect':
                                        case 'radio':
                                        case 'dropdown':

                                            if ($ANSWER_TEXT_EXIST) {
                                                $answer = mb_substr($answer, 0, -2) . ' ';
                                                $answer_raw .= $arrA['ANSWER_TEXT'];
                                            }

                                            if ($ANSWER_VALUE_EXIST) {
                                                $answer .= '(' . $arrA['ANSWER_VALUE'] . ') ';
                                                if (!$ANSWER_TEXT_EXIST) {
                                                    $answer_raw .= $arrA['ANSWER_VALUE'];
                                                }
                                            }

                                            if (!$ANSWER_VALUE_EXIST && !$ANSWER_TEXT_EXIST) {
                                                $answer_raw .= $arrA['ANSWER_ID'];
                                            }

                                            $answer .= '[' . $arrA['ANSWER_ID'] . ']';

                                            break;

                                        case 'file':
                                        case 'image':

                                            if ($USER_FILE_EXIST) {
                                                $f = \CFile::GetByID($arrA["USER_FILE_ID"]);
                                                if ($fr = $f->Fetch()) {
                                                    $file_size = \CFile::FormatSize($fr["FILE_SIZE"]);
                                                    $url = ($APPLICATION->IsHTTPS() ? "https://" : "http://")
                                                        . $_SERVER["HTTP_HOST"]
                                                        . "/bitrix/tools/form_show_file.php?rid=" . $RESULT_ID
                                                        . "&hash=" . $arrA["USER_FILE_HASH"] . "&lang=" . LANGUAGE_ID;

                                                    if ($arrA["USER_FILE_IS_IMAGE"] == "Y") {
                                                        $answer .= "<a href=\"$url\">" . $arrA["USER_FILE_NAME"]
                                                            . "</a> [" . $fr["WIDTH"] . " x " . $fr["HEIGHT"] . "] ("
                                                            . $file_size . ")";
                                                    } else {
                                                        $answer .= "<a href=\"$url&action=download\">"
                                                            . $arrA["USER_FILE_NAME"] . "</a> (" . $file_size . ")";
                                                    }

                                                    $answer_raw .= $arrA['USER_FILE_NAME'];
                                                }
                                            }

                                            break;
                                    }
                                } else {
                                    if (
                                        $bHasDiffTypes
                                        && !$USER_TEXT_EXIST
                                        && (
                                            $arrA['FIELD_TYPE'] == 'text'
                                            || $arrA['FIELD_TYPE'] == 'textarea'
                                        )
                                    ) {
                                        continue;
                                    }

                                    if (trim($answer) <> '') {
                                        $answer .= "\n";
                                    }
                                    if (trim($answer_raw) <> '') {
                                        $answer_raw .= ",";
                                    }

                                    if ($ANSWER_TEXT_EXIST) {
                                        $answer .= $arrA["ANSWER_TEXT"] . ': ';
                                    }

                                    switch ($arrA['FIELD_TYPE']) {
                                        case 'text':
                                        case 'textarea':
                                        case 'email':
                                        case 'url':
                                        case 'hidden':
                                        case 'date':
                                        case 'password':
                                        case 'integer':

                                            if ($USER_TEXT_EXIST) {
                                                $answer .= trim((string) $arrA["USER_TEXT"]);
                                                $answer_raw .= trim((string) $arrA["USER_TEXT"]);
                                            }

                                            break;

                                        case 'checkbox':
                                        case 'multiselect':
                                        case 'radio':
                                        case 'dropdown':

                                            if ($ANSWER_TEXT_EXIST) {
                                                $answer = mb_substr($answer, 0, -2) . ' ';
                                                $answer_raw .= $arrA['ANSWER_TEXT'];
                                            }

                                            if ($ANSWER_VALUE_EXIST) {
                                                $answer .= '(' . $arrA['ANSWER_VALUE'] . ') ';
                                                if (!$ANSWER_TEXT_EXIST) {
                                                    $answer_raw .= $arrA['ANSWER_VALUE'];
                                                }
                                            }

                                            if (!$ANSWER_VALUE_EXIST && !$ANSWER_TEXT_EXIST) {
                                                $answer_raw .= $arrA['ANSWER_ID'];
                                            }

                                            $answer .= '[' . $arrA['ANSWER_ID'] . ']';

                                            break;

                                        case 'file':
                                        case 'image':

                                            if ($USER_FILE_EXIST) {
                                                $f = \CFile::GetByID($arrA["USER_FILE_ID"]);
                                                if ($fr = $f->Fetch()) {
                                                    $file_size = \CFile::FormatSize($fr["FILE_SIZE"]);
                                                    $url = ($APPLICATION->IsHTTPS() ? "https://" : "http://")
                                                        . $_SERVER["HTTP_HOST"]
                                                        . "/bitrix/tools/form_show_file.php?rid=" . $RESULT_ID
                                                        . "&hash=" . $arrA["USER_FILE_HASH"] . "&action=download&lang="
                                                        . LANGUAGE_ID;

                                                    if ($arrA["USER_FILE_IS_IMAGE"] == "Y") {
                                                        $answer .= $arrA["USER_FILE_NAME"] . " [" . $fr["WIDTH"] . " x "
                                                            . $fr["HEIGHT"] . "] (" . $file_size . ")\n" . $url;
                                                    } else {
                                                        $answer .= $arrA["USER_FILE_NAME"] . " (" . $file_size . ")\n"
                                                            . $url . "&action=download";
                                                    }
                                                }

                                                $answer_raw .= $arrA['USER_FILE_NAME'];
                                            }

                                            break;
                                    }
                                }
                            }
                        }

                        $arEventFields[$wr["SID"]] = ($answer == '') ? " " : $answer;
                        $arEventFields[$wr["SID"] . '_RAW'] = ($answer_raw == '') ? " " : $answer_raw;
                    }

                    CEvent::Send(
                        $arrTemplate["EVENT_NAME"], $arrTemplate["SITE_ID"], $arEventFields, "Y", $arrTemplate["ID"]
                    );
                    $MESS = $OLD_MESS;
                }

                return true;
            } else {
                $strError .= GetMessage("FORM_ERROR_FORM_NOT_FOUND") . "<br>";
            }
        } else {
            $strError .= GetMessage("FORM_ERROR_RESULT_NOT_FOUND") . "<br>";
        }

        return false;
    }
}
