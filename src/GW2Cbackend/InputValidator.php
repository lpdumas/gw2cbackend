<?php

namespace GW2CBackend;

class InputValidator {

    protected $input;

    public function __construct($input) {

        $this->input = $input;
    }

    public function validate() {
        
        if(!is_array($this->input)) {
            return false;
        }

        if(!array_key_exists('version', $this->input) || !is_int($this->input['version'])) {
            return 'version field is missing';
        }

        if(!array_key_exists('creation_date', $this->input)) {
            return 'creation_date field is missing';
        }
        
        if(!array_key_exists('markers', $this->input)) {
            return 'main markers field is missing';
        }
        
        foreach($this->input["markers"] as $mgSlug => $markerGroup) {

            if(!is_array($markerGroup) || !array_key_exists('marker_types', $markerGroup) || !is_array($markerGroup['marker_types']) ||
                !is_string($mgSlug)) {
                return 'marker group not well formed';
            }

            foreach($markerGroup['marker_types'] as $mtSlug => $markerType) {

                if(!is_string($mtSlug) || !array_key_exists('markers', $markerType) || !is_array($markerType['markers'])) {
                        return 'marker type not well formed';
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
                           !is_int($marker["id"]) || !is_numeric($marker["lat"]) || !is_numeric($marker["lng"])
                        ) {
                            //var_dump($marker);
                            return 'marker is not correctly formed';
                        }
                }
            }
        }
        
        return true;
    }
}
