<?php

namespace GW2CBackend;

use GW2CBackend\Marker\MarkerGroup;
use GW2CBackend\Marker\MarkerType;
use GW2CBackend\Marker\Marker;
use GW2CBackend\Marker\MapRevision;

class MarkerBuilder {
    
    protected $db;
    
    public function __construct(DatabaseAdapter $db) {
        $this->db = $db;
    }

    public function build($revisionID, $json) {
        
        $mGroup = null;
        $mType = null;
        $m = null;
        $tData = null;

        $mapRevision = new MapRevision($revisionID);
        
        foreach($this->getMarkersStructure() as $mgSlug => $markerGroup) {
            
            $mGroup = new MarkerGroup($mgSlug, $markerGroup['icon_prefix'], $markerGroup['translated_data']);
            
            foreach($markerGroup['marker_types'] as $mtSlug => $markerType) {
                
                $icon = $markerType['filename'];
                $displayInSummary = $markerType['display_in_area_summary'];
                $tData = $markerType['translated_data'];
                
                $mType = new MarkerType($mtSlug, $icon, $displayInSummary, $tData);
                $mGroup->addMarkerType($mType);
                
                if(array_key_exists($mgSlug, $json['markers'])) {
                    $markers = $json['markers'][$mgSlug]['marker_types'];

                    if(array_key_exists($mtSlug, $markers)) {
                        foreach($markers[$mtSlug]['markers'] as $marker) {
                    
                            $area = $this->getMarkerArea($marker['lat'], $marker['lng']);
                            $translatedData = array_key_exists('data_translation', $marker) ? $marker['data_translation'] : array();
                            $tData = new TranslatedData($translatedData);

                            $m = new Marker($marker['id'], $marker['lat'], $marker['lng'], $area, $tData);                    
                            $mType->addMarker($m);
                        }
                    }
                }
            }
            
            $mapRevision->addMarkerGroup($mGroup);
        }

        return $mapRevision;
    }
    
    protected function getMarkerArea($lat, $lng) {
        
        $areas = $this->db->retrieveAreasList();
        
        foreach($areas as $area) {

            if($lat <= $area['neLat'] && $lat >= $area['swLat'] && $lng <= $area['neLng'] && $lng >= $area['swLng']) {
                return $area['id'];
            }
            
        }
        
        return 0;
    }
    
    protected function getMarkersStructure() {

        return $this->db->getMarkersStructure();
    }
}