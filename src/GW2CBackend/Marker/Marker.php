<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend\Marker;

use GW2CBackend\TranslatedData;

/**
 * Represents a marker.
 */
class Marker {

    /**
     * The marker's ID.
     * @var integer
     */
    protected $id;

    /**
     * The marker's latitude.
     * @var float
     */
    protected $lat;

    /**
     * The marker's longitude.
     * @var float
     */
    protected $lng;

    /**
     * The marker's area ID.
     * @var integer
     */
    protected $area;

    /**
     * The marker's translated data.
     * @var \GW2CBackend\TranslatedData
     */
    protected $translatedData;

    /**
     * The marker's status.
     * @var integer
     */
    protected $status;

    /**
     * The reference marker. When a marker is changed, we keep the old marker as the "reference".
     * @var \GW2Backend\Marker\Marker
     */
    protected $reference;

    /**
     * Constructor.
     *
     * @param integer $id
     * @param float $lat
     * @param float $lng
     * @param integer $area
     * @param \GW2CBackend\TranslatedData $translatedData
     */
    public function __construct($id, $lat, $lng, $area, TranslatedData $translatedData) {
        $this->id = $id;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->area = $area;
        $this->translatedData = $translatedData;
        $this->reference = null;
    }

    /**
     * Compares two marker's data. Coordinates are not taken into account !
     *
     * @param \GW2CBackend\Marker\Marker $marker
     * @return boolean true if the translated data and the area are the same.
     */
    public function compare(Marker $marker) {
        
        return $this->getData()->compare($marker->getData()) && $this->getArea() == $marker->getArea();
    }

    /**
     * Sets the marker as its reference.
     *
     * This is used when the marker as changed to set the current marker as the reference to be able to keep track of the changes.
     */
    public function setAsReference() {
        
        $this->reference = new Marker($this->id, $this->lat, $this->lng, $this->area, 
                                        new TranslatedData($this->translatedData->getAllData())
                                    );
    }

    /**
     * Gets the marker's ID.
     *
     * @return integer
     */
    public function getID() { return $this->id; }

    /**
     * Sets the marker's ID.
     *
     * @param integer $value
     */
    public function setID($value) { $this->id = $value; }

    /**
     * Gets the marker's status.
     *
     * @return string
     */
    public function getStatus() { return $this->status; }

    /**
     * Sets the marker's status.
     *
     * @param string $status
     */
    public function setStatus($status) { $this->status = $status; }

    /**
     * Gets the marker's reference.
     *
     * @return \GW2CBackend\Marker\Marker
     */
    public function getReference() { return $this->reference; }

    /**
     * Sets the marker's reference.
     *
     * @param \GW2CBackend\Marker\Marker $reference
     */
    public function setReference(Marker $reference) { $this->reference = $reference; }

    /**
     * Gets the marker's latitude.
     *
     * @return float
     */
    public function getLat() { return $this->lat; }

    /**
     * Sets the marker's latitude.
     *
     * @param float $lat
     */
    public function setLat($lat) { return $this->lat = $lat; }

    /**
     * Gets the marker's longitude.
     *
     * @return float
     */
    public function getLng() { return $this->lng; }

    /**
     * Sets the marker's longitude.
     *
     * @param float $lng
     */
    public function setLng($lng) { return $this->lng = $lng; }

    /**
     * Gets the marker's area ID.
     *
     * @return integer
     */
    public function getArea() { return $this->area; }

    /**
     * Gets the marker's translated data.
     *
     * @return \GW2CBackend\TranslatedData
     */
    public function getData() { return $this->translatedData; }

    /**
     * Sets the marker's translated data.
     *
     * @param \GW2CBackend\TranslatedData $tData
     */
    public function setData(TranslatedData $tData) { $this->translatedData = $tData; }
}