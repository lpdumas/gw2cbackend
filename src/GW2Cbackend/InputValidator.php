<?php

namespace GW2CBackend;

class InputValidator {

    protected $input;
    protected $markerTypeList;

    public function __construct($input, $markerTypeList) {
        $this->input = $input;
        $this->markerTypeList = $markerTypeList;
    }

    public function validate() {
        
        if(!is_array($this->input)) {
            return false;
        }
        
        foreach($this->input as $markerType => $markerTypeCollection) {
            
            if(!is_string($markerType)) {
                echo "marker type is not a string";
                return false;
            }
            else {
                
                if(!is_array($markerTypeCollection) || !in_array($markerType, $this->markerTypeList)) {
                    return false;
                }
                
                foreach($markerTypeCollection as $key => $marker) {

                    if(!is_int($key)) {
                        echo "key is not a digit";
                        return false;
                    }
                    else {

                        if (
                           !array_key_exists("id", $marker) || 
                           !array_key_exists("lat", $marker) ||
                           !array_key_exists("lng", $marker) ||
                           !array_key_exists("title", $marker) ||
                           !array_key_exists("desc", $marker) ||
                           !is_int($marker["id"]) ||!is_float($marker["lat"]) || !is_float($marker["lng"]) ||
                           !is_string($marker["title"]) || !is_string($marker["desc"])
                        ) {
                            echo "a marker is not well formed";
                            var_dump($marker);
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }
}
