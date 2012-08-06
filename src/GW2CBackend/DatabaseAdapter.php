<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */
 
namespace GW2CBackend;

use Symfony\Component\Security\Core\User\User;

/**
 * Manages the database access.
 *
 * This class uses PDO to request the database.
 *
 * @todo This class needs a massive refactoring. Comments will come with the refactoring.
 */
class DatabaseAdapter {

    /**
     * @ignore
     */
    protected $pdo;
    
    /**
     * @ignore
     */
    protected $database;

    /**
     * @ignore
     */
    protected $data = array();

    /**
     * @ignore
     */
    public function connect($host, $port, $database, $user, $pword) {
        
        if($this->pdo instanceof \PDO) return;
        
        try {
            $this->pdo = new \PDO('mysql:host='.$host.';port='.$port.';dbname='.$database, $user, $pword);
            $this->database = $database;
        }
        catch(\Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * @ignore
     */
    public function addModification($json) {
        
        $date = date('Y-m-d H:i:s');
        $json = addslashes($json);

        $q = "INSERT INTO modification_list (date_added, value) 
                         VALUES ('".$date."', '".$json."')";

        $r = $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function saveNewRevisionAsReference($newRevision, $modificationSourceID, $maxID) {
        $date = date('Y-m-d H:i:s');
        
        $json = $newRevision->toJSON();
        $validator = new InputValidator($json);
        $isValid = $validator->validate();
        $json = addslashes(json_encode($json));
        
        $q = "INSERT INTO reference_list (value, date_added, id_merged_modification, max_marker_id) 
                         VALUES ('".$json."', '".$date."', '".$modificationSourceID."', '".$maxID."')";

        $r = $this->pdo->exec($q);
        
        return $this->pdo->lastInsertId();
    }

    /**
     * @ignore
     */
    public function markAsMerged($revID, $mergedChanges, $refID) {

        $date = date('Y-m-d H:i:s');

        foreach($mergedChanges as $change) {
            
            $q = "INSERT INTO merged_changes (id_change, id_modification, id_reference)
                    VALUES ('".$change."', '".$revID."', '".$refID."')";
            $this->pdo->exec($q);
        }

        $this->pdo->exec("UPDATE modification_list SET `is_merged` = 1, `date_merge` = '".$date."' WHERE id = ".$revID);
    }

    /**
     * @ignore
     */
    public function retrieveAll() {
        $this->retrieveOptions();
        //$this->retrieveResources();
        $this->retrieveAreasList();
        $this->retrieveCurrentReference();
        $this->retrieveFirstModification();
        //$this->retrieveReferenceAtSubmission($this->data['first-modification']['reference-at-submission']);
    }

    /**
     * @ignore
     */
    public function retrieveModificationList() {
        $result = $this->pdo->query("SELECT * FROM modification_list WHERE is_merged = 0");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        return $result->fetchAll();
    }

    /**
     * @ignore
     */
    public function retrieveMergedModificationList() {
        $result = $this->pdo->query("SELECT * FROM modification_list WHERE is_merged = 1 ORDER BY date_merge DESC");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        return $result->fetchAll();
    }

    /**
     * @ignore
     */
    public function retrieveReferenceAtSubmission($referenceID) {
        $result = $this->pdo->query("SELECT * FROM reference_list WHERE id = ".$referenceID."");
        
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        $this->data["reference-at-submission"] = $result->fetch();
    }

    /**
     * @ignore
     */
    public function retrieveCurrentReference() {
        
        $result = $this->pdo->query("SELECT * FROM reference_list ORDER BY date_added DESC LIMIT 0,1");
        
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        $this->data["current-reference"] = $result->fetch();
    }

    /**
     * @ignore
     */
    public function retrieveOptions() {
        $result = $this->pdo->query("SELECT * FROM options");
        $result->setFetchMode(\PDO::FETCH_ASSOC);

        foreach($result->fetchAll() as $row) {
            
            $this->data["options"][$row["id"]] = array('value' => $row["value"], 'desc' => $row['desc']);
        }
    }

    /**
     * @ignore
     */
    public function getMarkerGroups() {
        $result = $this->pdo->query("SELECT * FROM marker_group");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        $r = array();
        foreach($result->fetchAll() as $res) {

            $res['translated_data'] = $this->getTranslatedData($res['id_translated_data']);;
            $res['marker_types'] = array();
            $r[$res['slug']] = $res;
        }
        
        $this->data["marker.groups"] = $r;
        
        return $r;
    }

    /**
     * @ignore
     */
    public function getTranslatedData($id) {
        
        if($id == null) {
            $tData = new TranslatedData();
        }
        else {
            $q = "SELECT * FROM translated_data WHERE id = ".$id." ORDER BY keyv DESC";

            $data = $this->pdo->query($q);
            $data->setFetchMode(\PDO::FETCH_ASSOC);
            $tData = array();
            foreach($data->fetchAll() as $d) {
                $tData[$d['lang']][$d['keyv']] = $d['value'];
            }
        
            $tData = new TranslatedData($tData);
        }

        return $tData;
    }

    /**
     * @ignore
     */
    public function getMarkerTypes() {
        $result = $this->pdo->query("SELECT * FROM marker_type");
        $result->setFetchMode(\PDO::FETCH_ASSOC);

        $r = array();
        foreach($result->fetchAll() as $res) {

            $res['translated_data'] = $this->getTranslatedData($res['id_translated_data']);
            $r[$res['id']] = $res;
        }

        $this->data["marker.types"] = $r;
        
        return $r;
    }

    /**
     * @ignore
     */
    public function getMarkersStructure() {
        $markerGroups = $this->getMarkerGroups();
        $markerTypes = $this->getMarkerTypes();

        foreach($markerTypes as $markerType) {
            $markerGroups[$markerType['slug_marker_group']]['marker_types'][$markerType['id']] = $markerType;
        }

        return $markerGroups;
    }

    /**
     * @ignore
     */
    public function retrieveAreasList() {
        $result = $this->pdo->query("SELECT * FROM areas_list");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        foreach($result->fetchAll() as $row) {
            $this->data["areas-list"][$row["id"]] = $row;
        }
        
        return $this->data["areas-list"];
    }

    /**
     * @ignore
     */
    public function createArea($name, $rangeLvl, $swLat, $swLng, $neLat, $neLng) {
        
        $q = "INSERT INTO areas_list (`name`, `rangeLvl`, `swLat`, `swLng`, `neLat`, `neLng`)
                VALUES ('".$name."', '".$rangeLvl."', '".$swLat."', '".$swLng."','".$neLat."', '".$neLng."')";
        $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function editArea($areaID, $name, $rangeLvl, $swLat, $swLng, $neLat, $neLng) {
        
        $name = $this->pdo->quote($name);
        
        $q = "UPDATE areas_list SET `name` = ".$name.", `rangeLvl` = '".$rangeLvl."', 
                                    `swLat` = '".$swLat."', `swLng` = '".$swLng."',
                                    `neLat` = '".$neLat."', `neLng` = '".$neLng."'
                WHERE id = ".$areaID;
        $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function removeArea($areaID) {
        
        $this->pdo->exec("DELETE FROM areas_list WHERE id = ".$areaID);
    }

    /**
     * @ignore
     */
    public function retrieveModification($idModification) {
        $result = $this->pdo->query("SELECT * FROM modification_list WHERE id = ".$idModification);
        $result->setFetchMode(\PDO::FETCH_ASSOC);

        return $result->fetch();
    }

    /**
     * @ignore
     */
    public function retrieveFirstModification() {
        $result = $this->pdo->query("SELECT * FROM modification_list WHERE is_merged = 0 ORDER BY date_added LIMIT 0,1");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        $this->data["first-modification"] = $result->fetch();
    }

    /**
     * @ignore
     */
    public function getData($index = null) {
        
        if($index == null) {
            return $this->data;
        }
        else {
            return $this->data[$index];
        }
    }

    /**
     * @ignore
     */
    public function addFieldset($fieldsetName) {
        
        $q = "INSERT INTO fieldset (name) VALUES ('".$fieldsetName."')";
        $r = $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function removeFieldset($fieldsetID) {
        
        $query = $this->pdo->query("SELECT name FROM fieldset WHERE id =".$fieldsetID);
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        $q = "DELETE FROM fieldset WHERE id = ".$fieldsetID;
        $r = $this->pdo->exec($q);
        
        $result = $query->fetch();
        return $result['name'];
    }

    /**
     * @ignore
     */
    public function getAllFieldsets() {
        $result = $this->pdo->query("SELECT * FROM fieldset ORDER BY id");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        $r = array();
        foreach($result->fetchAll() as $fs) {

            $q = "SELECT * FROM fieldset_content WHERE id_fieldset =".$fs['id'];
            $content = $this->pdo->query($q);
            $content->setFetchMode(\PDO::FETCH_ASSOC);

            $fs['fields'] = $content->fetchAll();            
            $r[$fs['id']] = $fs;
        }

        return $r;
    }

    /**
     * @ignore
     */
    public function getFieldset($fieldsetID) {
        $query = $this->pdo->query("SELECT name FROM fieldset WHERE id =".$fieldsetID);
        
        if(!$query) return null;
        
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        
        $fieldset = $query->fetch();
        
        $q = "SELECT * FROM fieldset_content WHERE id_fieldset =".$fieldsetID;
        $content = $this->pdo->query($q);
        $content->setFetchMode(\PDO::FETCH_ASSOC);

        $fieldset['fields'] = $content->fetchAll();

        return $fieldset;
    }

    /**
     * @ignore
     */ 
    public function getTranslatableDataIDsByFieldset($fieldsetID) {
        
        $qg = "SELECT id_translated_data FROM marker_group WHERE id_translated_data IS NOT NULL AND id_fieldset = ".$fieldsetID;
        $qt = "SELECT id_translated_data FROM marker_type WHERE id_translated_data IS NOT NULL AND id_fieldset = ".$fieldsetID;

        $rg = $this->pdo->query($qg);
        $rg->setFetchMode(\PDO::FETCH_ASSOC);
        $rt = $this->pdo->query($qt);
        $rt->setFetchMode(\PDO::FETCH_ASSOC);
                
        $tDataIDs = array_merge($rg->fetchAll(), $rt->fetchAll());
        
        return $tDataIDs;
    }

    /**
     * @ignore
     */
    public function addField($fieldsetID, $fieldName) {

        $q = "INSERT INTO fieldset_content (key_value, id_fieldset) VALUES ('".$fieldName."', '".$fieldsetID."')";
        $r = $this->pdo->exec($q);
        
        $tdf = $this->getTranslatableDataIDsByFieldset($fieldsetID);
        foreach($tdf as $td) {
            $this->updateTranslatedDataFieldsRange($td['id_translated_data'], $fieldsetID);
        }
        
        $mgs = $this->getMarkerGroupIDsIfNoTranslatedData($fieldsetID);
        foreach($mgs as $mg) {
            $this->editMarkerGroup($mg['slug'], $mg['slug'], $fieldsetID);
        }
        
        $mts = $this->getMarkerTypeIDsIfNoTranslatedData($fieldsetID);
        foreach($mts as $mt) {
            $this->editMarkerType($mt['id'], $mt['id'], $mt['filename'], $fieldsetID);
        }

        $result = $this->getFieldset($fieldsetID);
        return $result['name'];
    }

    /**
     * @ignore
     */
    public function editField($fieldID, $fieldName) {
        
        $query = $this->pdo->query("SELECT * FROM fieldset_content WHERE id =".$fieldID);
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        $fieldset = $query->fetch();
        $fieldsetID = $fieldset['id_fieldset'];

        $fieldIDs = $this->getTranslatableDataIDsByFieldset($fieldsetID);
        $ids = "";
        foreach($fieldIDs as $field) {
            $ids.= $field["id_translated_data"].",";
        }
        $ids = substr($ids, 0, strlen($ids) - 1);

        $qtd = "UPDATE translated_data SET `keyv` = '".$fieldName."' WHERE keyv = '".$fieldset['key_value']."' AND id IN (".$ids.")";
        $r = $this->pdo->exec($qtd);


        $q = "UPDATE fieldset_content SET `key_value` = '".$fieldName."' WHERE id = ".$fieldID;
        $r = $this->pdo->exec($q);

        $result = $this->getFieldset($fieldsetID);
        return $result['name'];
    }

    /**
     * @ignore
     */
    public function removeField($fieldID, $fieldName) {
        
        $query = $this->pdo->query("SELECT id_fieldset FROM fieldset_content WHERE id =".$fieldID);
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        $fieldsetID = $query->fetch();
        $fieldsetID = $fieldsetID['id_fieldset'];

        $q = "DELETE FROM fieldset_content WHERE id = ".$fieldID;
        $r = $this->pdo->exec($q);
        
        $tdf = $this->getTranslatableDataIDsByFieldset($fieldsetID);
        foreach($tdf as $td) {
            $this->updateTranslatedDataFieldsRange($td['id_translated_data'], $fieldsetID);
        }

        $mgs = $this->getMarkerGroupByFieldset($fieldsetID);
        foreach($mgs as $mg) {
            $this->editMarkerGroup($mg['slug'], $mg['slug'], $fieldsetID);
        }
        
        $mts = $this->getMarkerTypeByFieldset($fieldsetID);
        foreach($mts as $mt) {
            $this->editMarkerType($mt['id'], $mt['id'], $mt['filename'], $fieldsetID);
        }

        $result = $this->getFieldset($fieldsetID);
        return $result['name'];
    }

    /**
     * @ignore
     */
    public function getMarkerGroupIDsIfNoTranslatedData($fieldsetID) {
        
        $q = "SELECT * FROM marker_group WHERE id_translated_data is NULL AND id_fieldset = ".$fieldsetID;
        $r = $this->pdo->query($q);
        $r->setFetchMode(\PDO::FETCH_ASSOC);
        
        return $r->fetchAll();
    }

    /**
     * @ignore
     */
    public function getMarkerGroupByFieldset($fieldsetID) {
        $q = "SELECT * FROM marker_group WHERE id_fieldset = ".$fieldsetID;
        $r = $this->pdo->query($q);
        $r->setFetchMode(\PDO::FETCH_ASSOC);
        
        return $r->fetchAll();
    }

    /**
     * @ignore
     */
    public function getMarkerTypeByFieldset($fieldsetID) {
        $q = "SELECT * FROM marker_type WHERE id_fieldset = ".$fieldsetID;
        $r = $this->pdo->query($q);
        $r->setFetchMode(\PDO::FETCH_ASSOC);
        
        return $r->fetchAll();
    }

    /**
     * @ignore
     */
    public function getMarkerTypeIDsIfNoTranslatedData($fieldsetID) {
        
        $q = "SELECT * FROM marker_type WHERE id_translated_data is NULL AND id_fieldset = ".$fieldsetID;
        $r = $this->pdo->query($q);
        $r->setFetchMode(\PDO::FETCH_ASSOC);
        
        return $r->fetchAll();
    }    

    /**
     * @ignore
     */
    public function addMarkerGroup($slug, $iconPrefix, $fieldsetID) {

        $tDataID = "NULL";
        if(!ctype_digit($fieldsetID)) {
            $fieldsetID = "NULL";
        }
        else {
            $fieldsetID = "'".$fieldsetID."'";
            $id = $this->addTranslatedDataFieldsFromFieldset($fieldsetID);
            if($id == null) {
                $tDataID = "NULL";
            }
            else {
                $tDataID = "'".$id."'";
            }
        }
        
        $slug = strtolower($slug);
        $q = "INSERT INTO marker_group (slug, icon_prefix, id_translated_data, id_fieldset) 
                VALUES ('".$slug."', '".$iconPrefix."', ".$tDataID.", ".$fieldsetID.")";
        $r = $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function editMarkerGroup($slugReference, $slug, $iconPrefix, $fieldsetID) {
        
        $mgQ = "SELECT id_translated_data, id_fieldset FROM marker_group WHERE slug = '".$slugReference."'";
        $mg = $this->pdo->query($mgQ);
        $mg = $mg->fetch();

        if(!is_null($mg['id_translated_data'])) {

            $nbFields = $this->updateTranslatedDataFieldsRange($mg['id_translated_data'], $fieldsetID);
            // we set the id_translated_data to null since there are no fields in the fieldset
            if($nbFields == 0) {
                $mg['id_translated_data'] = null;
            }
        }
        else {
            $id = $this->addTranslatedDataFieldsFromFieldset($fieldsetID);
        }

        if($fieldsetID == 'default') {
            $fieldsetID = "NULL";
            $id = "NULL";
        }
        else {
            $fieldsetID = "'".$fieldsetID."'";

            if(!isset($id)) {
                if(is_null($mg['id_translated_data'])) {
                    $id = "NULL";
                }
                else {
                    $id = "'".$mg['id_translated_data']."'";
                }
            }
            else {
                $id = "'".$id."'";
            }
        }

        $slug = strtolower($slug);
        $qMT = "UPDATE marker_type SET `slug_marker_group` = '".$slug."' WHERE slug_marker_group = '".$slugReference."'";
        $q = "UPDATE marker_group SET `slug` = '".$slug."', `icon_prefix` = '".$iconPrefix."',
                `id_translated_data` = ".$id.", `id_fieldset` = ".$fieldsetID." 
                WHERE slug = '".$slugReference."'";

        $this->pdo->exec($qMT);        
        $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function updateTranslatedDataFieldsRange($translatedDataID, $fieldsetID) {

        $fieldset = $this->getFieldset($fieldsetID);

        $fields = array();            
        if(!is_null($fieldset)) {
            foreach($fieldset['fields'] as $field) {
                array_push($fields, $field['key_value']);
            }
        }
        
        $tData = $this->getTranslatedData($translatedDataID);
        $current = array_pop($tData->getAllData());
        $toAdd = array();
    
        foreach($fields as $field) {
            
            if(is_array($current) && array_key_exists($field, $current)) {                
                unset($current[$field]);
            }
            else {                
                array_push($toAdd, $field);
            }
        }

        // $current contains now items that need to be deleted
        if(is_array($current)) {
            foreach($current as $field => $v) {
                $this->removeTranslatedDataField($translatedDataID, $field);
            }
        }
        
        // we add the fields that need to be added
        foreach($this->getAllLanguages() as $lang) {
            foreach($toAdd as $field) {
                $this->addTranslatedDataField($translatedDataID, $lang, $field);
            }
        }
        
        $fieldset = $this->getFieldset($fieldsetID);
        
        return count($fieldset['fields']);
    }

    /**
     * @ignore
     */
    public function removeMarkerGroup($slug) {
        
        $mgQ = "SELECT id_translated_data FROM marker_group WHERE slug = '".$slug."'";
        $mg = $this->pdo->query($mgQ);
        $mg = $mg->fetch();
        
        $translatedDataID = $mg['id_translated_data'];
        
        $rmg = "DELETE FROM marker_group WHERE slug = '".$slug."'";
        $rmt = "DELETE FROM marker_type WHERE slug_marker_group = '".$slug."'";
        $rtd = "DELETE FROM translated_data WHERE id = ".$translatedDataID;
        
        $this->pdo->exec($rtd);
        $this->pdo->exec($rmt);
        $this->pdo->exec($rmg);
    }

    /**
     * @ignore
     */
    public function addMarkerType($slug, $filename, $displayInAreaSum, $fieldsetID, $markerGroupID) {

        $tDataID = "NULL";
        if(!ctype_digit($fieldsetID)) {
            $fieldsetID = "NULL";
        }
        else {
            $fieldsetID = "'".$fieldsetID."'";
            $id = $this->addTranslatedDataFieldsFromFieldset($fieldsetID);
            if($id == null) {
                $tDataID = "NULL";
            }
            else {
                $tDataID = "'".$id."'";
            }
        }
        
        if($displayInAreaSum == 'on') $displayInAreaSum = 1;
        
        $q = "INSERT INTO marker_type (id, filename, display_in_area_summary, slug_marker_group, id_translated_data, id_fieldset) 
                VALUES ('".$slug."', '".$filename."', '".$displayInAreaSum."', '".$markerGroupID."', ".$tDataID.", ".$fieldsetID.")";
        $r = $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function removeMarkerType($slug) {
        
        $mtQ = "SELECT id_translated_data FROM marker_type WHERE id = '".$slug."'";
        $mt = $this->pdo->query($mtQ);
        $mt = $mt->fetch();
        
        $translatedDataID = $mt['id_translated_data'];
        
        $rmt = "DELETE FROM marker_type WHERE id = '".$slug."'";
        $rtd = "DELETE FROM translated_data WHERE id = ".$translatedDataID;
        
        $this->pdo->exec($rtd);
        $this->pdo->exec($rmt);
    }

    /**
     * @ignore
     */
    public function editMarkerType($slugReference, $slug, $filename, $displayInAreaSum, $fieldsetID) {
        
        $mtQ = "SELECT id_translated_data, id_fieldset FROM marker_type WHERE id = '".$slugReference."'";
        $mt = $this->pdo->query($mtQ);
        $mt = $mt->fetch();

        if(!is_null($mt['id_translated_data'])) {
            $nbFields = $this->updateTranslatedDataFieldsRange($mt['id_translated_data'], $fieldsetID);

            if($nbFields == 0) {
                $mt['id_translated_data'] = null;
            }
        }
        else {
            $id = $this->addTranslatedDataFieldsFromFieldset($fieldsetID);
        }

        if($fieldsetID == 'default') {
            $fieldsetID = "NULL";
            $id = "NULL";
        }
        else {
            $fieldsetID = "'".$fieldsetID."'";

            if(!isset($id)) {
                if(is_null($mt['id_translated_data'])) {
                    $id = "NULL";
                }
                else {
                    $id = "'".$mt['id_translated_data']."'";
                }
            }
            else {
                $id = "'".$id."'";
            }
        }

        if($displayInAreaSum == 'on') $displayInAreaSum = 1;

        $slug = strtolower($slug);
        $q = "UPDATE marker_type SET `id` = '".$slug."', `id_translated_data` = ".$id.", `id_fieldset` = ".$fieldsetID.",
                `filename` = '".$filename."', `display_in_area_summary` = '".$displayInAreaSum."' WHERE id = '".$slugReference."'";
        $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function addTranslatedDataFieldsFromFieldset($fieldsetID) {
        $langs = $this->getAllLanguages();
        $fieldset = $this->getFieldset($fieldsetID);
        $id = $this->getTranslatedDataNewID();
        foreach($langs as $lang) {
            
            if(is_array($fieldset)) {
                foreach($fieldset['fields'] as $field) {
                    $this->addTranslatedDataField($id, $lang, $field['key_value']);
                }
            }
        }
        
        if(empty($fieldset['fields'])) {
            return null;
        }

        return $id;
    }

    /**
     * @ignore
     */
    public function addTranslatedDataField($id, $lang, $key) {
        
        $q = "INSERT INTO translated_data (id, lang, keyv) 
                VALUES ('".$id."', '".$lang."', '".$key."')";
        $r = $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function removeTranslatedDataField($id, $key) {
        
        $q = "DELETE FROM translated_data WHERE id = ".$id." AND keyv = '".$key."'";
        $r = $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function updateTranslatedDataField($id, $lang, $key, $value) {
        
        $q = "UPDATE translated_data SET `value` = '".$value."' WHERE id = '".$id."' AND lang = '".$lang."' AND keyv = '".$key."'";
        $r = $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function getReference($referenceID) {
        $q = $this->pdo->query("SELECT * FROM reference_list WHERE id = ".$referenceID);
        if(!$q) return null;

        $q->setFetchMode(\PDO::FETCH_ASSOC);
        return $q->fetch();
    }

    /**
     * @ignore
     */
    public function removeModification($idModification) {
        $this->pdo->exec("DELETE FROM modification_list WHERE id = ".$idModification);
    }

    /**
     * @ignore
     */
    public function editTranslatedData($tDataID, $values, $fieldsetID) {
        
        $fieldset = $this->getFieldset($fieldsetID);
        
        $langs = $this->getAllLanguages();
        foreach($langs as $lang) {
            
            foreach($fieldset['fields'] as $field) {
                $newValue = $values[$lang.'-'.$field['key_value']];
                $this->updateTranslatedDataField($tDataID, $lang, $field['key_value'], $newValue);
            }
        }
    }

    /**
     * @ignore
     */
    public function getTranslatedDataNewID() {
        $l = $this->pdo->query("SELECT MAX(id) as max_id FROM translated_data");
        $l->setFetchMode(\PDO::FETCH_ASSOC);
        $l = $l->fetch();

        return $l['max_id'] + 1;
    }

    /**
     * @ignore
     */
    public function getAllLanguages() {
        
        $l = $this->pdo->query("SELECT * FROM supported_language");
        $l->setFetchMode(\PDO::FETCH_ASSOC);
        
        $langs = array();        
        foreach($l->fetchAll() as $lang) {
            array_push($langs, $lang['id']);
        }
        
        return $langs;
    }

    /**
     * @ignore
     */
    public function getUserByUsername($username) {
        
        $q = $this->pdo->query("SELECT * FROM user WHERE username = '".$username."'");
        if(!$q) return null;

        $q->setFetchMode(\PDO::FETCH_ASSOC);
        return $q->fetch();
        
    }

    /**
     * @ignore
     */
    public function getAllUsers() {
        $q = $this->pdo->query("SELECT * FROM user");
        if(!$q) return null;

        $q->setFetchMode(\PDO::FETCH_ASSOC);
        return $q->fetchAll();
    }

    /**
     * @ignore
     */
    public function createUser($username, $password, $role, $service) {
        
        $username = strtolower($username);
        $user = new User($username, '', explode(',', $role), true, true, true, true);

        $encoder = $service->getEncoder($user);
        $password = $encoder->encodePassword($password, $user->getSalt());

        $q = "INSERT INTO user(username, password, roles) 
                VALUES ('".$username."', '".$password."', '".$role."')";

        $r = $this->pdo->exec($q);
    }

    /**
     * @ignore
     */
    public function removeUser($username) {
        
        $this->pdo->exec("DELETE FROM user WHERE username = '".$username."'");
    }

    /**
     * @ignore
     */
    public function editOptions(array $options) {
        
        foreach($options as $key => $value) {
            $this->pdo->exec("UPDATE options SET `value` = '".$value."' WHERE id = '".$key."'");
        }
    }

    /**
     * @ignore
     */
    public function dumpDatabase() {

        $sQuery = "SHOW tables FROM " . $this->database;
        $sResult = $this->pdo->query($sQuery);
        $sData = "
        -- PDO SQL Dump --

        SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";

        --
        -- Database: `$this->database`
        --

        -- --------------------------------------------------------
        ";

        while ($aTable = $sResult->fetch(\PDO::FETCH_ASSOC)) {

          $sTable = $aTable['Tables_in_' . $this->database];

          $sQuery = "SHOW CREATE TABLE $sTable";

          $sResult2 = $this->pdo->query($sQuery);

          $aTableInfo = $sResult2->fetch(\PDO::FETCH_ASSOC);

          $sData .= "\n\n--
        -- Table structure for table `$sTable`
        --\n\n";
          $sData .= $aTableInfo['Create Table'] . ";\n";

          $sData .= "\n\n--
        -- Entries for table `$sTable`
        --\n\n";


          $sQuery = "SELECT * FROM $sTable\n";

          $sResult3 = $this->pdo->query($sQuery);

          while ($aRecord = $sResult3->fetch(\PDO::FETCH_ASSOC)) {

            // Insert query per record
            $sData .= "INSERT INTO $sTable VALUES (";
            $sRecord = "";
            foreach( $aRecord as $sField => $sValue ) {
              $sRecord .= "'".utf8_encode(addslashes($sValue))."',";
            }
            $sData .= substr( $sRecord, 0, -1 );
            $sData .= ");\n";
          }

        }
        
        return $sData;
    }

    /**
     * @ignore
     */
    public function handleError(\Exception $e) {
        var_dump($e);
    }

}