<?php

namespace GW2CBackend;

class ChangerMerger {
    public function __construct(Marker\MapRevision $revision, $resourcesPath, $areas) {
        
        //$this->reference = $reference;
        //$this->changes = $changes;
        //$this->changesOriginal = $changes; 
        //$this->markerGroups = $markerGroups;
        $this->areas = $areas;
        $this->resourcesPath = $resourcesPath;
        //$this->maxID = 0;
        
        $this->revision = $revision;
    }
    
    public function generate($mergeForAdmin, $changesToMerge = array()) {
        
        $outputString = "";

        //$this->setIDToNewMarkers();
        
        /*$changeID = 1;
        foreach($this->changes as $mgk => $markerGroup) {
            foreach($markerGroup as $mtk => $markerType) {
                foreach($markerType as $ck => $change) {
                    $this->changes[$mgk][$mtk][$ck]['id'] = $changeID;
                    $changeID++;
                }
            }
        }*/
        
        
        //$this->mergeChanges($mergeForAdmin, $changesToMerge);

        $outputString.= $this->generateMetadataOutput();
        //$outputString.= $this->generateAreasOutput();
        $outputString.= $this->generateMarkersOutput();

        $this->output = $outputString;
        
        return $this->output;
    }
    
    public function setIDToNewMarkers() {
        
        $this->maxID = self::getMaximumID($this->reference);

        foreach($this->changes as $markerGroupID => $markerGroup) {
            
            foreach($markerGroup as $markerTypeID => $markerType) {
                
                foreach($markerType as $changeID => $change) {

                    if($change["status"] == DiffProcessor::STATUS_ADDED && $change["marker"]["id"] == -1) {
                        $this->changes[$markerGroupID][$markerTypeID][$changeID]["marker"]["id"] = $this->getNewID();
                    }
                }
            }
        }
        
        $this->changesOriginal = $this->changes;
    }
    
    protected function getNewID() {
        return ++$this->maxID;
    }
    
    public function getMaxMarkerID() { return $this->maxID; }
    
    static protected function getMaximumID($collection) {

        $maxID = 0;

        foreach($collection as $markerGroup) {
            foreach($markerGroup['markerGroup'] as $markerType) {
            
                foreach($markerType['markers'] as $marker) {
                    if($marker["id"] > $maxID) $maxID = $marker["id"];
                }
            }
        }

        return $maxID;
    }

    protected function mergeChanges($mergeForAdmin, $changesToMerge) {
        
        foreach($this->reference as $markerGroupID => $markerGroup) {
            foreach($markerGroup['markerGroup'] as $markerTypeID => $markerType) {

                $markerCollection = &$this->reference[$markerGroupID]['markerGroup'][$markerTypeID]['markers'];

                foreach($markerType['markers'] as $markerID => $marker) {
                
                    $change = $this->getMarkerInChangesByID($marker["id"], $markerGroupID, $markerType);

                    if($change != null && (in_array($change['id'], $changesToMerge) || $mergeForAdmin) ) {
                        
                        switch($change["status"]) {
                            case DiffProcessor::STATUS_MODIFIED_COORDINATES:
                            case DiffProcessor::STATUS_MODIFIED_DATA:
                            case DiffProcessor::STATUS_MODIFIED_ALL:
                                $markerCollection[$markerID] = $change["marker"];
                                if($mergeForAdmin) {
                                    $markerCollection[$markerID]['marker-reference'] = $change["marker-reference"];
                                    $markerCollection[$markerID]['status'] = $change['status'];
                                }
                                break;
                            case DiffProcessor::STATUS_REMOVED:
                                if(!$mergeForAdmin) {
                                    unset($markerCollection[$markerID]);
                                    $markerCollection = array_values($markerCollection);
                                }
                                else {
                                    $markerCollection[$markerID]['status'] = DiffProcessor::STATUS_REMOVED;
                                }
                                break;
                        }

                        $changeCollection = &$this->changes[$markerGroupID][$markerType['slug']];

                        // we remove the items so there is only the remainings items with the "STATUS_ADDED" status
                        $id = array_search($change, $changeCollection);
                        unset($changeCollection[$id]);
                        $changeCollection = array_values($changeCollection);
                    }
                }
                
                foreach($this->changes[$markerGroupID][$markerType['slug']] as $change) {

                    if(in_array($change['id'], $changesToMerge) || $mergeForAdmin) {
                        $change['marker']['status'] = DiffProcessor::STATUS_ADDED;
                        $markerCollection[] = $change['marker'];
                    }
                }
            }
        }
    }
    
    protected function getMarkersByType($type) {
        
        foreach($this->reference as $markerGroup) {
            
            foreach($markerGroup['markerGroup'] as $markerType) {
                if($markerType['slug'] == $type) {
                    return $markerType['markers'];
                }
            }
        }
        
        return array();
    }
    
    protected function getMarkerInChangesByID($markerID, $markerGroupID, $markerType) {

        if(!array_key_exists($markerType['slug'], $this->changes[$markerGroupID])) return null;

        foreach($this->changes[$markerGroupID][$markerType['slug']] as $change) {

            if((array_key_exists("marker", $change) && $change["marker"]["id"] == $markerID) || 
                (array_key_exists("marker-reference", $change) && $change["marker-reference"]["id"] == $markerID)) {

                return $change;
            }
        }

        return null;
    }    
}