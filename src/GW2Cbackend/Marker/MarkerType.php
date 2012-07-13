<?php

namespace GW2CBackend\Marker;

use GW2CBackend\TranslatedData;

class MarkerType {
    
    protected $slug;
    protected $icon;
    protected $displayInAreaSummary;
    protected $translatedData;
    protected $markers;
    
    public function __construct($slug, $icon, $displayInAreaSummary, TranslatedData $translatedData) {
        
        $this->slug = $slug;
        $this->icon = $icon;
        $this->displayInAreaSummary = $displayInAreaSummary;
        $this->translatedData = $translatedData;
        $this->markers = array();
    }
    
    public function getSlug() { return $this->slug; }
    public function getIcon() { return $this->icon; }
    public function isDisplayInAreaSummary() { return $this->displayInAreaSummary; }
    public function getData() { return $this->translatedData; }

    public function addMarker(Marker $marker) {
        
        $this->markers[] = $marker;
    }

    public function getAllMarkers() {
        return $this->markers;
    }
}