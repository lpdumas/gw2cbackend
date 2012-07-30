<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend\Marker;

/**
 * Represents a map revision.
 */
class MapRevision {

    /**
     * The map's ID.
     * @var integer
     */
    protected $id;

    /**
     * The marker groups collection.
     * @var array
     */
    protected $markerGroups;

    /**
     * Constructor.
     *
     * @param integer $id
     */
    public function __construct($id) {

        $this->id = $id;
        $this->markerGroups = array();
    }

    /**
     * Gets the ID.
     *
     * @return integer the map's ID
     */
    public function getID() { return $this->id; }
    
    /**
     * Sets the map's ID.
     *
     * @param integer $id
     */
    public function setID($id) { $this->id = $id; }
    
    /**
     * Adds a marker group.
     *
     * @param \GW2CBackend\Marker\MarkerGroup $markerGroup the marker group object.
     */
    public function addMarkerGroup(MarkerGroup $markerGroup) {

        $this->markerGroups[$markerGroup->getSlug()] = $markerGroup;
    }

    /**
     * Gets the marker groups array.
     *
     * @return array
     */
    public function getAllMarkerGroups() { return $this->markerGroups; }

    /**
     * Gets a marker group.
     *
     * @param string $groupSlug the group identifier.
     * @return \GW2CBackend\Marker\MarkerGroup|null the marker group object if it exists, null otherwise
     */
    public function getMarkerGroup($groupSlug) {

        if(array_key_exists($groupSlug, $this->markerGroups)) {
            return $this->markerGroups[$groupSlug];
        }

        return null;
    }

    /**
     * Converts the objet to its representation in JSON.
     *
     * @return array a decoded json array.
     */
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

    /**
     * Dumps the objet. Debug purpose only.
     */
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