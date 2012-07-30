<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend;

use GW2CBackend\Marker\MarkerGroup;
use GW2CBackend\Marker\MarkerType;
use GW2CBackend\Marker\Marker;
use GW2CBackend\Marker\MapRevision;

/**
 * Builds a MapRevision instance.
 */
class MarkerBuilder {

    /**
     * Contains the database service.
     * @var \GW2CBackend\DatabaseAdapter 
     */
    protected $db;

    /**
     * Constructor.
     *
     * @param \GW2CBackend\DatabaseAdapter $db the database service
     */
    public function __construct(DatabaseAdapter $db) {
        $this->db = $db;
    }

    /**
     * Builds a map object.
     *
     * @param integer $revisionID the revision's ID.
     * @param string $json the json string that represents the map.
     * @return \GW2CBackend\Marker\MapRevision a new map revision object.
     */
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

    /**
     * Gets the marker's area by coordinates.
     *
     * The areas information are retrieved from the database.
     *
     * @param float $lat the marker's latitude.
     * @param float $lng the marker's longitude.
     * @return integer the area's ID if found, 0 otherwise.
     */
    protected function getMarkerArea($lat, $lng) {
        
        $areas = $this->db->retrieveAreasList();
        
        foreach($areas as $area) {

            if($lat <= $area['neLat'] && $lat >= $area['swLat'] && $lng <= $area['neLng'] && $lng >= $area['swLng']) {
                return $area['id'];
            }
            
        }
        
        return 0;
    }

    /**
     * Retrieves the marker groups and the marker types from the database.
     *
     * @return array 
     */
    protected function getMarkersStructure() {

        return $this->db->getMarkersStructure();
    }
}