<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend\Marker;

use GW2CBackend\TranslatedData;

/**
 * Represents a marker group.
 */
class MarkerGroup {

    /**
     * The marker group's slug.
     * @var string
     */
    protected $slug;

    /**
     * The marker group's prefix for icons.
     *
     * This is the folder path to the marker type's icon filename.
     * @var string
     */
    protected $iconPrefix;

    /**
     * The marker group's translated data.
     * @var \GW2CBackend\TranslatedData
     */
    protected $translatedData;

    /**
     * The marker types collection.
     * @var array
     */
    protected $markerTypes;

    /**
     * Constructor.
     *
     * @param string $slug
     * @param string $iconPrefix
     * @param \GW2CBackend\TranslatedData $translatedData
     */
    public function __construct($slug, $iconPrefix, TranslatedData $translatedData) {
        
        $this->slug = $slug;
        $this->iconPrefix = $iconPrefix;
        $this->translatedData = $translatedData;
        $this->markerTypes = array();
    }

    /**
     * Gets the marker group's slug.
     *
     * @return string
     */
    public function getSlug() { return $this->slug; }

    /**
     * Gets the marker group's prefix icon.
     *
     * @return string
     */
    public function getIconPrefix() { return $this->iconPrefix; }

    /**
     * Gets the marker group's translated data.
     *
     * @return \GW2CBackend\TranslatedData
     */
    public function getData() { return $this->translatedData; }

    /**
     * Adds a marker type to the marker type's collection.
     *
     * @param \GW2CBackend\Marker\MarkerType $markerType
     */
    public function addMarkerType(MarkerType $markerType) {
        
        $this->markerTypes[$markerType->getSlug()] = $markerType;
    }

    /**
     * Gets a marker type.
     *
     * @param string $markerTypeSlug
     * @return \GW2CBackend\Marker\MarkerType|null the marker type object, null if it doesn't exist.
     */
    public function getMarkerType($markerTypeSlug) {
        
        if(array_key_exists($markerTypeSlug, $this->markerTypes)) {
            return $this->markerTypes[$markerTypeSlug];
        }
        
        return null;
    }

    /**
     * Gets all the marker types.
     *
     * @return array
     */
    public function getAllMarkerTypes() {
        return $this->markerTypes;
    }
}