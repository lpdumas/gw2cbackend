<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend;

/**
 * Represents the marker information with the translation.
 */
class TranslatedData {

    /**
     * The data collection. Index is the lang's slug and value an array of key => value
     * @var array
     */
    protected $langs;

    /**
     * Constructor.
     *
     * @param array $langs
     */
    public function __construct(array $langs = array()) {
        $this->langs = $langs;
    }

    /**
     * Compares two TranslatedData objects.
     *
     * @param \GW2CBackend\TranslatedData $tData
     * @return boolean
     */
    public function compare(TranslatedData $tData) {
        
        $isEqual = true;
        
        foreach($this->langs as $lang => $content) {
            foreach($content as $field => $value) {
                $isEqual = $isEqual && $tData->getData($lang, $field) == $value;
            }
        }
        
        return $isEqual;
    }

    /**
     * Gets all the data or all the data of a determined language.
     *
     * @param string|false the lang's slug or false.
     * @return array|null null if the lang's slug does not exist, array of all langs if $lang is false, 
     * array of data if $lang has been specified.
     */
    public function getAllData($lang = false) {
        
        if($lang && array_key_exists($lang, $this->langs)) {
            return $this->langs[$lang];
        }
        elseif($lang === false) {
            return $this->langs;
        }
        else {
            return null;
        }
    }

    /**
     * Gets a data of a determined language
     *
     * @param string $lang the lang's slug.
     * @param string $key the data's key.
     * @return string|null null if the key does not exist, the value otherwise.
     */
    public function getData($lang, $key) {

        if(array_key_exists($lang, $this->langs) && array_key_exists($key, $this->langs[$lang])) {
            return $this->langs[$lang][$key];
        }
        else {
            return null;
        }
    }
}