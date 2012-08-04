<?php

namespace GW2CBackend;

class Util {

    static public $app;

    static public function decodeJSON($jsonString) {

        $json = json_decode($jsonString, true);

        if(is_null($json)) {

            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $error = ' - Pas d\'erreur';
                    break;
                case JSON_ERROR_DEPTH:
                    $error = ' - Profondeur maximale atteinte';
                break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error = ' - Inadéquation des modes ou underflow';
                break;
                case JSON_ERROR_CTRL_CHAR:
                    $error = ' - Erreur lors du contrôle des caractères';
                break;
                case JSON_ERROR_SYNTAX:
                    $error = ' - Erreur de syntaxe ; JSON malformé';
                break;
                case JSON_ERROR_UTF8:
                    $error = ' - Caractères UTF-8 malformés, probablement une erreur d\'encodage';
                break;
                default:
                    $error = ' - Erreur inconnue';
                break;
            }

            self::$app['monolog']->addError($error);
        }

        return $json;
    }
}