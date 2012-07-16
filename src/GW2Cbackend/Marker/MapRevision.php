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
    
    public function toJSON() {
        
        $json = array('version' => (int)$this->getID(), 'creation_date' => date('Y-m-d'), 'markers' => array());
        
        foreach($this->markerGroups as $mg) {
            
            $json['markers'][$mg->getSlug()] = array('marker_types' => array());
            
            foreach($mg->getAllMarkerTypes() as $mt) {
                
                $json['markers'][$mg->getSlug()]['marker_types'][$mt->getSlug()] = array('markers' => array());
                
                foreach($mt->getAllMarkers() as $m) {
                    
                    $marker = array();
                    $dataMarker = $m->getData()->getAllData();
                    if(!empty($dataMarker)) {
                        $marker['data_translation'] = $dataMarker;
                    }
                    
                    $marker['id'] = $m->getID();
                    $marker['lat'] = $m->getLat();
                    $marker['lng'] = $m->getLng();
                    
                    $json['markers'][$mg->getSlug()]['marker_types'][$mt->getSlug()]['markers'][] = $marker;
                }
            }
        }
        
        return $json;
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