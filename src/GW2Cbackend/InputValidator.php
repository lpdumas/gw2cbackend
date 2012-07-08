<?php

namespace GW2CBackend;

class InputValidator {

    protected $input;
    protected $areasList;
    protected $markerTypeList;

    public function __construct($input, $areasList) {

        $this->input = $input;
        $this->areasList = $areasList;
    }

    public function validate() {
        
        if(!is_array($this->input)) {
            return false;
        }
        
        foreach($this->input as $markerGroupID => $markerGroup) {

            if(!is_array($markerGroup) || !array_key_exists('markerGroup', $markerGroup) || !is_array($markerGroup['markerGroup']) ||
                !array_key_exists('name', $markerGroup)) {
                return false;
            }

            foreach($markerGroup['markerGroup'] as $markerTypeID => $markerType) {

                if(!array_key_exists('name', $markerType) || !array_key_exists('slug', $markerType) 
                    || !array_key_exists('markers', $markerType) || !is_array($markerType['markers'])) {
                        return false;
                }
            
                foreach($markerType['markers'] as $key => $marker) {

                    if(!is_int($key)) {
                        //echo "key is not a digit";
                        return false;
                    }
                    elseif (
                           !array_key_exists("id", $marker) || 
                           !array_key_exists("lat", $marker) ||
                           !array_key_exists("lng", $marker) ||
                           !array_key_exists("area", $marker) ||
                           !array_key_exists("title", $marker) ||
                           !array_key_exists("desc", $marker) ||
                           !array_key_exists("wikiLink", $marker) ||
                           !is_int($marker["id"]) || !is_numeric($marker["lat"]) || !is_numeric($marker["lng"]) ||
                           !is_int($marker["area"]) || (!array_key_exists($marker["area"], $this->areasList) &&
                           $marker['area'] != 0) ||
                           !is_string($marker["title"]) || !is_string($marker["desc"]) || !is_string($marker['wikiLink'])
                        ) {
                            //echo "a marker is not well formed";
                            //var_dump($marker);
                            return false;
                        }
                }
            }
        }
        
        return true;
    }
}
