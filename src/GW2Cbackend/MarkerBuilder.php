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

        foreach($json as $markerGroupID => $markerGroup) {

            $tData = new TranslatedData($markerGroup['translated_data']);
            $mGroup = new MarkerGroup($markerGroupID, $tData);

            foreach($markerGroup['markerTypes'] as $markerType) {

                $icon = $this->getMarkerTypeIcon($markerType['slug']);
                $translatedData = array_key_exists('translated_data', $markerType) ? $markerType['translated_data'] : array();
                $tData = new TranslatedData($translatedData);

                $mType = new MarkerType($markerType['slug'], $icon, 0, $tData);
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
}