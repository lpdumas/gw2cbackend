<?php

namespace GW2CBackend;

class ChangeMerger {
    
    protected $reference;
    
    protected $changes;
    
    protected $newRevision;
    
    protected $forAdmin;
    
    public function __construct(Marker\MapRevision $reference, array $changes) {
        
        $this->reference = $reference;
        $this->newRevision = $reference;
        $this->changes = $changes;

        $this->maxID = 0;
        $this->forAdmin = false;
    }
    
    public function merge(array $changesToMerge = array()) {
        
        $this->setIDToNewMarkers();

        $changeID = 1;
        foreach($this->changes as $gSlug => $mGroup) {
            foreach($mGroup as $tSlug => $mType) {
                
                $markerType = &$this->newRevision->getMarkerGroup($gSlug)->getMarkerType($tSlug);
                
                foreach($mType as $change) {
                    
                    if(in_array($changeID, $changesToMerge) || $this->forAdmin) {
                    
                        switch($change['status']) {
                            case DiffProcessor::STATUS_MODIFIED_COORDINATES:
                            case DiffProcessor::STATUS_MODIFIED_DATA:
                            case DiffProcessor::STATUS_MODIFIED_ALL:
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
    
    protected function getNewID() {
        return ++$this->maxID;
    }
    
    public function getMaximumID() {
        return $this->maxID;
    }
    
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
     * Set the indicator that will determine if the output will be used by gw2c-backend's merging environment
     * @param boolean $forAdmin
     */
    public function setForAdmin($forAdmin) { $this->forAdmin = $forAdmin; }
}