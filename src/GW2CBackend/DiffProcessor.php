<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend;

use \GW2CBackend\Marker\MapRevision;
use \GW2CBackend\Marker\Marker;

/**
 * This class detects the differences between two version of the map.
 */
class DiffProcessor {

    /**
     * When an entry has no change.
     * @var string
     */
    const STATUS_OK = "status_ok";

    /**
     * When an entry is a new one.
     * @var string
     */
    const STATUS_ADDED = "status_added";

    /**
     * When an entry has been removed.
     * @var string
     */
    const STATUS_REMOVED = "status_removed";

    /**
     * When an entry has its coordinates and its data modified.
     * @var string
     */
    const STATUS_MODIFIED_ALL = "status_modified_all";

    /**
     * When an entry has its coordinates modified.
     * @var string
     */
    const STATUS_MODIFIED_COORDINATES = "status_modified_coordinates";

    /**
     * When an entry has its data modified.
     * @var string
     */
    const STATUS_MODIFIED_DATA = "status_modified_data";

    /**
     * When an entry has potentially a data loss.
     * @var string
     */
    const STATUS_POTENTIAL_DATA_LOSS = "status_potential_data_loss";

    /**
     * The submitted modification.
     * @var \GW2CBackend\Marker\MapRevision
     */
    protected $modification;

    /**
     * The current reference.
     * @var \GW2CBackend\Marker\MapRevision
     */
    protected $reference;

    /**
     * The changes detected in the diff process.
     * @var array
     */
    protected $changes;

    /**
     * The maximum ID of the reference.
     *
     * This is used to know if the deleted marker has really been deleted or the deletion detection comes from the fact that 
     * the modification has been made from an older reference than the current one.
     *
     * @var integer
     */
    protected $maxReferenceID;

    /**
     * Constructor.
     *
     * @param \GW2CBackend\Marker\MapRevision $modification the modification
     * @param \GW2CBackend\Marker\MapRevision $reference the current reference
     * @param integer $maxReferenceID the current reference's max ID
     */
    public function __construct(MapRevision $modification, MapRevision $reference, $maxReferenceID) {

        $this->modification = $modification;
        $this->reference = $reference;
        
        $this->changes = array();
        
        $this->maxReferenceID = $maxReferenceID;
    }
    
    /**
     * Process the DIFF between the modification and the reference config.
     *
     * This populates the $changes attribute with markers and information about the change.
     *
     * @todo adding more comments for the algorithm
     * @return array $changes with all the changes from the diff
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
    
    /**
     * Looks for a marker in the reference and build the entry result.
     *
     * @todo adding comments, refactoring function name
     * @param \GW2CBackend\Marker\Marker $marker the marker being searched
     * @param array $markersReference the markers from the reference
     */
    protected function lookForMarker(Marker $marker, array $markersReference) {
        
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
                elseif(self::isPotentialDataLoss($markerReference, $marker)) {
                    $item["status"] = self::STATUS_POTENTIAL_DATA_LOSS;
                    $item["marker"] = $marker;
                    $item["marker-reference"] = $markerReference;
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
                elseif(self::isPotentialDataLoss($markerReference, $marker)) {
                    $item["status"] = self::STATUS_POTENTIAL_DATA_LOSS;
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
     * Search for a marker in the modification JSON array.
     *
     * We delete the found item from the modification JSON array each time we find it. In the end, the remaining items are the added ones.
     *
     * @todo removing unused code
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
     * Get a marker in a collection by marker's ID.
     *
     * @param integer $id a marker's ID.
     * @param array $collection a collection containing markers.
     * @return \GW2CBackend\Marker\Marker|null the marker if found, null otherwise.
     */
    static public function getMarkerByID($id, array $collection) {

        foreach($collection as $item) {
            if($item->getID() == $id) {
                return $item;
            }
        }

        return null;        
    }

    /**
     * Detects if a marker presents a potential data loss in comparison with the reference.
     *
     * @param \GW2CBackend\Marker\Marker $reference
     * @param \GW2CBackend\Marker\Marker $changedMarker
     * @return boolean
     */
    static protected function isPotentialDataLoss(Marker $reference, Marker $changedMarker) {
        $dRef = $reference->getData();
        $dCh = $changedMarker->getData();
        
        foreach($dRef->getAllData() as $lang => $langs) {
            
            foreach($langs as $field => $value) {
                
                $changedValue = $dCh->getData($lang, $field);
                if(!empty($value) && !is_null($changedValue) && empty($changedValue)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Indicates if the DiffProcessor detected changes or not.
     *
     * @return boolean
     */
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
