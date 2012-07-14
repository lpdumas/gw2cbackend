<?php

namespace GW2CBackend;

use \GW2CBackend\Marker\MapRevision;

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
    
    public function __construct(MapRevision $modification, MapRevision $reference, $maxReferenceID) {

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
        
        $processIDs = array();
        
        foreach($this->modification->getAllMarkerGroups() as $mGroup) {
            
            $this->changes[$mGroup->getSlug()] = array();
            
            foreach($mGroup->getAllMarkerTypes() as $mType) {

                $this->changes[$mGroup->getSlug()][$mType->getSlug()] = array();
                
                foreach($mType->getAllMarkers() as $marker) {
                    
                    $markersReference = $this->reference->getMarkerGroup($mGroup->getSlug())
                                                        ->getMarkerType($mType->getSlug())
                                                        ->getAllMarkers();
                    $item = $this->lookForMarker($marker, $markersReference);
                    
                    if($item["status"] != self::STATUS_OK) {
                        $this->changes[$mGroup->getSlug()][$mType->getSlug()][] = $item;
                    }
                    
                    if($marker->getID() != -1) {
                        array_push($processIDs, $marker->getID());
                    }
                }
            }
        }

        // we must look for deleted items
        foreach($this->reference->getAllMarkerGroups() as $mGroup) {
            
            foreach($mGroup->getAllMarkerTypes() as $mType) {
                
                foreach($mType->getAllMarkers() as $marker) {
                    
                    if($marker->getID() <= $this->maxReferenceID && !in_array($marker->getID(), $processIDs)) {
                        
                        $item = array("status" => self::STATUS_REMOVED, "marker" => $marker, "marker-reference" => null);
                        $this->changes[$mGroup->getSlug()][$mType->getSlug()][] = $item;
                    }
                }
            }
        }

        return $this->changes;
    }
    
    protected function lookForMarker($marker, $markersReference) {
        
        $item = array("status" => "", "marker" => null, "marker-reference" => null);
        
        $markerReference = self::getMarkerByID($marker->getID(), $markersReference);
        
        if(!is_null($markerReference)) {

            // if the coordinates are the same
            // because of PHP stores float numbers, we can't compare them efficiently so we transtype the float to strings
            if($markerReference->getLat()."" == $marker->getLat()."" && $markerReference->getLng()."" == $marker->getLng()."") {

                // if the data are the same
                if($markerReference->compare($marker)) {
                    $item["status"] = self::STATUS_OK;
                }
                else { // if the data have changed
                    $item["status"] = self::STATUS_MODIFIED_DATA;
                    $item["marker"] = $marker;
                    $item["marker-reference"] = $markerReference;
                }
            }
            else { // if the coordinantes changed
                // if the data are the same
                if($markerReference->compare($marker)) {
                    $item["status"] = self::STATUS_MODIFIED_COORDINATES;
                    $item["marker"] = $marker;
                    $item["marker-reference"] = $markerReference;
                }
                else { // if the data have changed
                    $item["status"] = self::STATUS_MODIFIED_ALL;
                    $item["marker"] = $marker;
                    $item["marker-reference"] = $markerReference;
                }
            }
        }
        else {
            $item["status"] = self::STATUS_ADDED;
            $item["marker"] = $marker;
        }
        
        return $item;
    }
    
    /**
     * Search for a marker in the modification JSON array
     * We delete the found item from the modification JSON array each time we find it. In the end, the remaining items are the added ones
     * @param $markerReference the marker from the reference
     * @param $markerTypeReference the marker type from the reference
     * @return an array that represents the result with the status and eventually the marker or/and the marker from the reference
     */
    protected function searchForMarker($markerReference, $markers) {
        


        /*foreach($this->modification[$markerGroupID]['markerGroup'] as $k => $markerType) {
            
            if($markerType['slug'] == $markerTypeID) {
                $markerCollection = $this->modification[$markerGroupID]['markerGroup'][$k]['markers'];
                break;
            }
        }*/

        $marker = null;
        //$marker = self::getMarkerById($markerReference["id"], $markerCollection);

        // if the marker has been found
        if($marker != null) {

            $id = array_search($marker, $markerCollection);
        
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
            unset($markerCollection[$id]);
            $markerCollection = array_values($markerCollection);
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
    static public function getMarkerByID($id, $collection) {

        foreach($collection as $item) {
            if($item->getID() == $id) {
                return $item;
            }
        }

        return null;        
    }
    
    public function hasChanges() {

        foreach($this->changes as $mGroup) {
            foreach($mGroup as $mType) {

                if(!empty($mType)) {
                    return true;
                }                
            }

        }
        
        return false;
    }
}
