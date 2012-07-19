<?php

namespace GW2CBackend\Marker;

use GW2CBackend\TranslatedData;

class MarkerGroup {
    
    protected $slug;
    protected $iconPrefix;
    protected $translatedData;
    protected $markerTypes;
    
    public function __construct($slug, $iconPrefix, TranslatedData $translatedData) {
        
        $this->slug = $slug;
        $this->iconPrefix = $iconPrefix;
        $this->translatedData = $translatedData;
        $this->markerTypes = array();
    }

    public function getSlug() { return $this->slug; }
    public function getIconPrefix() { return $this->iconPrefix; }
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