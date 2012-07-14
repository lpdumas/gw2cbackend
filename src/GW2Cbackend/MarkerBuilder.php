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
        
        $structure = $this->getMarkersStructure();

        foreach($json as $markerGroupID => $markerGroup) {

            $tData = $structure[$markerGroupID]['translated_data']; // this is an instance of TranslatedData
            $mGroup = new MarkerGroup($markerGroupID, $tData);

            foreach($markerGroup['markerTypes'] as $markerType) {

                $mTypeData = $structure[$markerGroupID]['marker_types'][$markerType['slug']];
                $icon = $mTypeData['filename'];
                $translatedData = array_key_exists('translated_data', $markerType) ? $mTypeData['translated_data']->getAllData() : array();
                $tData = new TranslatedData($translatedData);

                $mType = new MarkerType($markerType['slug'], $icon, $mTypeData['display_in_area_summary'], $tData);
                $mGroup->addMarkerType($mType);
                
                foreach($markerType['markers'] as $marker) {

                    $area =  $this->getMarkerArea($marker['lat'], $marker['lng']);
                    $translatedData = array_key_exists('translated_data', $marker) ? $marker['translated_data'] : array();
                    $tData = new TranslatedData($translatedData);

                    $m = new Marker($marker['id'], $marker['lat'], $marker['lng'], $area, $tData);                    
                    $mType->addMarker($m);
                }
            }

            $mapRevision->addMarkerGroup($mGroup);
        }

        return $mapRevision;
    }
    
    protected function getMarkerTypeIcon($markerTypeSlug) {
        
        $markerTypes = $this->db->getMarkerTypes();
        
        foreach($markerTypes as $slug => $markerType) {
            if($slug == $markerTypeSlug) {
                return $markerType['filename'];
            }
        }
        
        return "";
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