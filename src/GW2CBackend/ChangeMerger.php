<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend;

use \GW2CBackend\Marker\MapRevision;

/**
 * Merges the changes into a map representation.
 */
class ChangeMerger {
    
    /**
     * Contains the map reference.
     * @var \GW2CBackend\Marker\MapRevision
     */
    protected $reference;
    
    /**
     * Contains the changes.
     * @var array
     */
    protected $changes;
    
    /**
     * Contains the map with the merged changes.
     * @var \GW2CBackend\Marker\MapRevision
     */
    protected $newRevision;
    
    /**
     * Set the indicator that will determine if the output will be used by gw2c-backend's merging environment.
     * @var boolean
     */
    protected $forAdmin;
    
    /**
     * Constructor.
     *
     * @param \GW2CBackend\Marker\MapRevision $reference the map reference.
     * @param array $changes the changes.
     */
    public function __construct(MapRevision $reference, array $changes) {
        
        $this->reference = $reference;
        $this->newRevision = $reference;
        $this->changes = $changes;

        $this->maxID = 0;
        $this->forAdmin = false;
    }
    
    /**
     * Merges the changes to make a new revision.
     *
     * @param array $changesToMerge the list of changes' IDs that must be merged.
     * @return \GW2CBackend\Marker\MapRevision the map with the changes merged.
     */
    public function merge(array $changesToMerge = array()) {
        
        $this->setIDToNewMarkers();

        $changeID = 1;
        foreach($this->changes as $gSlug => $mGroup) {
            foreach($mGroup as $tSlug => $mType) {
                
                $markerType = &$this->newRevision->getMarkerGroup($gSlug)->getMarkerType($tSlug);
                
                foreach($mType as $change) {
                    
                    if(in_array($changeID, $changesToMerge) || $this->forAdmin) {
                    
                        switch($change['status']) {
                            case DiffProcessor::STATUS_POTENTIAL_DATA_LOSS:
                            case DiffProcessor::STATUS_MODIFIED_COORDINATES:
                            case DiffProcessor::STATUS_MODIFIED_DATA:
                            case DiffProcessor::STATUS_MODIFIED_ALL:
                                if($this->forAdmin) {
                                    $markerType->getMarker($change["marker"]->getID())->setAsReference();
                                }
                                $markerType->getMarker($change["marker"]->getID())->setStatus($change["status"]);
                                $markerType->getMarker($change["marker"]->getID())->setData($change["marker"]->getData());
                                $markerType->getMarker($change["marker"]->getID())->setLat($change["marker"]->getLat());
                                $markerType->getMarker($change["marker"]->getID())->setLng($change["marker"]->getLng());                                
                                break;
                            case DiffProcessor::STATUS_ADDED:
                                $change["marker"]->setStatus($change["status"]);
                                $markerType->addMarker($change['marker']);
                                break;
                            case DiffProcessor::STATUS_REMOVED:
                                $markerType->getMarker($change["marker"]->getID())->setStatus($change["status"]);
                                if(!$this->forAdmin) {
                                    $markerType->removeMarker($change["marker"]->getID());
                                }
                                break;
                        }
                    }
                    
                    $changeID++;
                }
            }
        }
        
        return $this->newRevision;
    }
    
    /**
     * This method sets new IDs to marker that have been created.
     *
     * The said markers are the ones whose ID are equal to -1.
     *
     * @return void.
     */
    public function setIDToNewMarkers() {
        
        $this->maxID = $this->computeMaximumID();

        foreach($this->changes as $gSlug => $mGroup) {
            
            foreach($mGroup as $tSlug => $mType) {
                
                foreach($mType as $changeID => $change) {

                    if($change["status"] == DiffProcessor::STATUS_ADDED && $change["marker"]->getID() == -1) {
                        $this->changes[$gSlug][$tSlug][$changeID]["marker"]->setID($this->getNewID());
                    }
                }
            }
        }
    }
    
    /**
     * Gets a new ID.
     *
     * @return integer a new ID.
     */
    protected function getNewID() {
        return ++$this->maxID;
    }
    
    /**
     * Gets the current maximum ID.
     *
     * @return integer the current maximum ID.
     */
    public function getMaximumID() {
        return $this->maxID;
    }
    
    /**
     * Gets the maximum ID from the reference.
     *
     * @return integer the maximum ID of the reference.
     */
    protected function computeMaximumID() {

        $maxID = 0;

        foreach($this->reference->getAllMarkerGroups() as $mGroup) {
            foreach($mGroup->getAllMarkerTypes() as $mType) {
            
                foreach($mType->getAllMarkers() as $marker) {
                    if($marker->getID() > $maxID) $maxID = $marker->getID();
                }
            }
        }

        return $maxID;
    }
    
    /**
     * Sets the indicator that will determine if the output will be used by gw2c-backend's merging environment.
     *
     * @param boolean $forAdmin
     */
    public function setForAdmin($forAdmin) { $this->forAdmin = $forAdmin; }
}