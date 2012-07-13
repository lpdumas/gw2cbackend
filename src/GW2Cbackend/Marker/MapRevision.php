<?php

namespace GW2CBackend\Marker;

class MapRevision {
    
    protected $id;
    
    protected $markerGroups;
    
    public function __construct($id) {

        $this->id = $id;
        $this->markerGroups = array();
    }
    
    public function getID() { return $this->id; }
    public function setID($id) { $this->id = $id; }
    
    public function addMarkerGroup(MarkerGroup $markerGroup) {

        $this->markerGroups[$markerGroup->getSlug()] = $markerGroup;
    }
    
    public function getAllMarkerGroups() { return $this->markerGroups; }
    
    public function getMarkerGroup($groupSlug) {

        if(array_key_exists($groupSlug, $this->markerGroups)) {
            return $this->markerGroups[$groupSlug];
        }
        
        return null;
    }
    
    public function d() {
        foreach($this->getAllMarkerGroups() as $markerGroup) {
            foreach($markerGroup->getAllMarkerTypes() as $markerType) {
                foreach($markerType->getAllMarkers() as $marker) {
                    var_dump($marker);
                }
            }
        }
    }
}