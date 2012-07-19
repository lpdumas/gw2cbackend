<?php

namespace GW2CBackend;

class TranslatedData {
    
    protected $langs;
    
    public function __construct(array $langs = array()) {
        $this->langs = $langs;
    }
    
    public function compare(TranslatedData $tData) {
        
        $isEqual = true;
        
        foreach($this->langs as $lang => $content) {
            foreach($content as $field => $value) {
                $isEqual = $isEqual && $tData->getData($lang, $field) == $value;
            }
        }
        
        return $isEqual;
    }
    
    public function addData($lang, $key, $value) {
        
        if(!array_key_exists($lang, $this->lang)) {
            $this->langs[$lang] = array();
        }
        
        $this->lang[$lang][$key] = $value;
    }
    
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
    
    public function getData($lang, $key) {

        if(array_key_exists($lang, $this->langs) && array_key_exists($key, $this->langs[$lang])) {
            return $this->langs[$lang][$key];
        }
        else {
            return null;
        }
    }
}