<?php

namespace GW2CBackend\Marker;

use GW2CBackend\TranslatedData;

class MarkerGroup {
    
    protected $slug;
    protected $translatedData;
    protected $markerTypes;
    
    public function __construct($slug, TranslatedData $translatedData) {
        
        $this->slug = $slug;
        $this->translatedData = $translatedData;
        $this->markerTypes = array();
    }

    public function getSlug() { return $this->slug; }
    public function getData() { return $this->translatedData; }

    public function addMarkerType(MarkerType $markerType) {
        
        $this->markerTypes[$markerType->getSlug()] = $markerType;
    }

    public function getMarkerType($markerTypeSlug) {
        
        if(array_key_exists($markerTypeSlug, $this->markerTypes)) {
            return $this->markerTypes[$markerTypeSlug];
        }
        
        return null;
    }

    public function getAllMarkerTypes() {
        return $this->markerTypes;
    }
}