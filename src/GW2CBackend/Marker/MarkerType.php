<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend\Marker;

use GW2CBackend\TranslatedData;

/**
 * Represents a marker type.
 */
class MarkerType {

    /**
     * The marker type's slug.
     * @var string
     */
    protected $slug;

    /**
     * The marker type's icon filename.
     * @var string
     */
    protected $icon;

    /**
     * An indicator that tell the system if the marker type must be displayed in the area summary or not.
     * @var boolean
     */
    protected $displayInAreaSummary;

    /**
     * The marker type's data
     * @var \GW2CBackend\TranslatedData
     */
    protected $translatedData;

    /**
     * The markers collection
     * @var array
     */
    protected $markers;

    /**
     * Constructor.
     *
     * @param string $slug
     * @param string $icon
     * @param boolean $displayInAreaSummary
     * @param \GW2CBackend\TranslatedData $translatedData
     */
    public function __construct($slug, $icon, $displayInAreaSummary, TranslatedData $translatedData) {
        
        $this->slug = $slug;
        $this->icon = $icon;
        $this->displayInAreaSummary = $displayInAreaSummary;
        $this->translatedData = $translatedData;
        $this->markers = array();
    }

    /**
     * Gets the marker's slug.
     *
     * @return string
     */
    public function getSlug() { return $this->slug; }

    /**
     * Gets the marker's icon.
     *
     * @return string
     */
    public function getIcon() { return $this->icon; }

    /**
     * Gets the isDisplayInAreaSummary value
     *
     * @return boolean
     */
    public function isDisplayInAreaSummary() { return $this->displayInAreaSummary; }

    /**
     * Gets the marker's data
     *
     * @return \GW2CBackend\TranslatedData
     */
    public function getData() { return $this->translatedData; }

    /**
     * Adds a marker into the collection.
     *
     * @param \GW2CBackend\Marker\Marker $marker
     */
    public function addMarker(Marker $marker) {
        
        $this->markers[] = $marker;
    }

    /**
     * Gets all the markers collection
     *
     * @return array the collection of markers
     */
    public function getAllMarkers() {
        return $this->markers;
    }

    /**
     * Gets a marker in the collection
     *
     * @param string $markerID
     * @return \GW2CBackend\Marker\Marker|null a marker object if found, null otherwise 
     */
    public function getMarker($markerID) {
        
        foreach($this->markers as $k => $marker) {

            if($marker->getID() == $markerID) {
                return $this->markers[$k];
            }
        }
        
        return null;
    }

    /**
     * Removes a marker from the markers collection
     *
     * @param string $markerID
     */
    public function removeMarker($markerID) {
        
        foreach($this->markers as $k => $marker) {

            if($marker->getID() == $markerID) {
                unset($this->markers[$k]);
            }
        }
    }
}