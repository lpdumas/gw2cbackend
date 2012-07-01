<?php

namespace GW2CBackend;

class DiffProcessor {
    
    const STATUS_OK = "status_ok";
    const STATUS_ADDED = "status_added";
    const STATUS_REMOVED = "status_removed";
    const STATUS_MODIFIED_ALL = "status_modified_all";
    const STATUS_MODIFIED_COORDINATES = "status_modified_coordinates";
    const STATUS_MODIFIED_DATA = "status_modified_data";
    
    protected $modificationDefault;
    protected $modification;
    protected $reference;
    
    protected $changes;
    
    protected $maxReferenceID;
    
    public function __construct($modification, $reference, $maxReferenceID) {

        $this->modification = $modification;
        $this->modificationDefault = $modification;
        $this->reference = $reference;
        
        $this->changes = array();
        
        $this->maxReferenceID = $maxReferenceID;
    }
    
    /**
     * Process the DIFF between the modification and the reference config.
     * This populates the $changes attribute with markers and information about the change
     *
     * @return $changes an array with all the changes from the DIFF
     */
    public function process() {

        foreach($this->reference as $markerType => $markerTypeCollection) {

            $this->changes[$markerType] = array();
            foreach($markerTypeCollection as $id => $marker) {

                $result = $this->searchForMarker($marker, $markerType);

                if($result["status"] != self::STATUS_OK) {
                    $this->changes[$markerType][] = $result;
                }
            }

            // the remaining elements are the added markers
            foreach($this->modification[$markerType] as $id => $marker) {

                // protect against eventual errors
                if($marker["id"] == -1) {
                    $result = array("status" => self::STATUS_ADDED, "marker" => $marker, "marker-reference" => null);
                    $this->changes[$markerType][] = $result;
                }
            }
        }
        
        return $this->changes;
    }
    
    /**
     * Search for a marker in the modification JSON array
     * We delete the found item from the modification JSON array each time we find it. In the end, the remaining items are the added ones
     * @param $markerReference the marker from the reference
     * @param $markerTypeReference the marker type from the reference
     * @return an array that represents the result with the status and eventually the marker or/and the marker from the reference
     */
    protected function searchForMarker($markerReference, $markerType) {
        
        $result = array("status" => null, "marker" => null, "marker-reference" => $markerReference);

        $marker = self::getMarkerById($markerReference["id"], $this->modification[$markerType]);

        // if the marker has been found
        if($marker != null) {

            $id = array_search($marker, $this->modification[$markerType]);
        
            // if the coordinates are the same
            // because of PHP stores float numbers, we can't compare them efficiently so we transtype the float to strings
            if($markerReference["lat"]."" == $marker["lat"]."" && $markerReference["lng"]."" == $marker["lng"]."") {

                // if the data are the same
                if($markerReference["title"] == $marker["title"] && $markerReference["desc"] == $marker["desc"] &&
                    $markerReference["area"] == $marker["area"]
                ) {
                    $result["status"] = self::STATUS_OK;
                }
                else { // if the data have changed
                    $result["status"] = self::STATUS_MODIFIED_DATA;
                    $result["marker"] = $marker;
                }
            }
            else { // if the coordinantes changed
                // if the data are the same
                if($markerReference["title"] == $marker["title"] && $markerReference["desc"] == $marker["desc"] &&
                    $markerReference["area"] == $marker["area"] 
                ) {
                    $result["status"] = self::STATUS_MODIFIED_COORDINATES;
                    $result["marker"] = $marker;
                }
                else { // if the data have changed
                    $result["status"] = self::STATUS_MODIFIED_ALL;
                    $result["marker"] = $marker;
                }
            }

            // we remove the marker from the array so we can know which markers have been added
            unset($this->modification[$markerType][$id]);
            $this->modification[$markerType] = array_values($this->modification[$markerType]);
        }
        else { // if the marker has been removed

            // we must check that the marker is in the range of IDs of the reference at the time of the submission
            if($markerReference["id"] <= $this->maxReferenceID) {
                $result["status"] = self::STATUS_REMOVED;
            }
            else {
                $result["status"] = self::STATUS_OK;
            }
        }

        
        return $result;
    }
    
    /**
     * Get a marker in a collection by marker's ID
     *
     * @param $id a marker's ID
     * @param $collection a collection containing markers
     */
    static public function getMarkerById($id, $collection) {

        foreach($collection as $item) {
            if($item["id"] == $id) {
                return $item;
            }
        }

        return null;        
    }
    
    public function hasNoChange() {
        
        foreach($this->changes as $markerTypeChanges) {
            if(!empty($markerTypeChanges)) {
                return false;
            }
        }
        
        return true;
    }
}
