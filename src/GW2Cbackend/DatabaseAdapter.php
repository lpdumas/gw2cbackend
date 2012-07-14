<?php

namespace GW2CBackend;

class DatabaseAdapter {

    protected $pdo;
    protected $data = array();

    public function connect($host, $port, $database, $user, $pword) {
        
        if($this->pdo instanceof \PDO) return;
        
        try {
            $this->pdo = new \PDO('mysql:host='.$host.';port='.$port.';dbname='.$database, $user, $pword);
        }
        catch(\Exception $e) {
            $this->handleError($e);
        }
    }
    
    public function addModification($json) {
        
        $date = date('Y-m-d H:i:s');
        
        if(!array_key_exists("current-reference", $this->data)) {
            $this->retrieveCurrentReference();
        }

        $idReference = $this->data["current-reference"]["id"];
        
        $json = addslashes($json);

        $q = "INSERT INTO modification_list (date_added, value, id_reference_at_submission) 
                         VALUES ('".$date."', '".$json."', '".$idReference."')";

        $r = $this->pdo->exec($q);
    }
    
    public function addReference($reference, $maxMarkerID, $idModification) {
        
        $date = date('Y-m-d H:i:s');
        $jsonReference = json_encode($reference);
        
        if(!array_key_exists("current-reference", $this->data)) {
            $this->retrieveCurrentReference();
        }

        $q = "INSERT INTO reference_list (value, date_added, id_merged_modification, max_marker_id) 
                         VALUES ('".$jsonReference."', '".$date."', '".$idModification."', '".$maxMarkerID."')";

        $this->pdo->exec($q);
    }

    public function retrieveAll() {
        $this->retrieveOptions();
        //$this->retrieveResources();
        $this->retrieveAreasList();
        $this->retrieveCurrentReference();
        $this->retrieveFirstModification();
        //$this->retrieveReferenceAtSubmission($this->data['first-modification']['reference-at-submission']);
    }
    
    public function retrieveModificationList() {
        $result = $this->pdo->query("SELECT * FROM modification_list WHERE is_merged = 0");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        return $result->fetchAll();
    }
    
    public function retrieveReferenceAtSubmission($referenceID) {
        $result = $this->pdo->query("SELECT * FROM reference_list WHERE id = ".$referenceID."");
        
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        $this->data["reference-at-submission"] = $result->fetch();
    }
    
    public function retrieveCurrentReference() {
        
        $result = $this->pdo->query("SELECT * FROM reference_list ORDER BY date_added DESC LIMIT 0,1");
        
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        $this->data["current-reference"] = $result->fetch();
    }

    public function retrieveOptions() {
        $result = $this->pdo->query("SELECT * FROM options");
        $result->setFetchMode(\PDO::FETCH_ASSOC);

        foreach($result->fetchAll() as $row) {
            
            $this->data["options"][$row["id"]] = $row["value"];
        }
    }
    
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
    
    public function getMarkersStructure() {
        $markerGroups = $this->getMarkerGroups();
        $markerTypes = $this->getMarkerTypes();

        foreach($markerTypes as $markerType) {
            $markerGroups[$markerType['slug_marker_group']]['marker_types'][$markerType['id']] = $markerType;
        }

        return $markerGroups;
    }

    public function retrieveAreasList() {
        $result = $this->pdo->query("SELECT * FROM areas_list");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        foreach($result->fetchAll() as $row) {
            $this->data["areas-list"][$row["id"]] = $row;
        }
        
        return $this->data["areas-list"];
    }
    
    public function retrieveModification($idModification) {
        $result = $this->pdo->query("SELECT * FROM modification_list WHERE id = ".$idModification);
        $result->setFetchMode(\PDO::FETCH_ASSOC);

        return $result->fetch();
    }
    
    public function retrieveFirstModification() {
        $result = $this->pdo->query("SELECT * FROM modification_list WHERE is_merged = 0 ORDER BY date_added LIMIT 0,1");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        $this->data["first-modification"] = $result->fetch();
    }
    
    public function getData($index = null) {
        
        if($index == null) {
            return $this->data;
        }
        else {
            return $this->data[$index];
        }
    }
    
    public function addFieldset($fieldsetName) {
        
        $q = "INSERT INTO fieldset (name) VALUES ('".$fieldsetName."')";
        $r = $this->pdo->exec($q);
    }
    
    public function removeFieldset($fieldsetID) {
        
        $query = $this->pdo->query("SELECT name FROM fieldset WHERE id =".$fieldsetID);
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        $q = "DELETE FROM fieldset WHERE id = ".$fieldsetID;
        $r = $this->pdo->exec($q);
        
        $result = $query->fetch();
        return $result['name'];
    }
    
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
    
    public function getTranslatableDataIDsByFieldset($fieldsetID) {
        
        $qg = "SELECT id_translated_data FROM marker_group WHERE id_fieldset = ".$fieldsetID;
        $qt = "SELECT id_translated_data FROM marker_type WHERE id_fieldset = ".$fieldsetID;

        $rg = $this->pdo->query($qg);
        $rg->setFetchMode(\PDO::FETCH_ASSOC);
        $rt = $this->pdo->query($qt);
        $rt->setFetchMode(\PDO::FETCH_ASSOC);
                
        $tDataIDs = array_merge($rg->fetchAll(), $rt->fetchAll());
        
        return $tDataIDs;
    }
    
    public function addField($fieldsetID, $fieldName) {

        $q = "INSERT INTO fieldset_content (key_value, id_fieldset) VALUES ('".$fieldName."', '".$fieldsetID."')";
        $r = $this->pdo->exec($q);
        
        $tdf = $this->getTranslatableDataIDsByFieldset($fieldsetID);
        foreach($tdf as $td) {
            $this->updateTranslatedDataFieldsRange($td['id_translated_data'], $fieldsetID);
        }

        $result = $this->getFieldset($fieldsetID);
        return $result['name'];
    }

    public function editField($fieldID, $fieldName) {
        
        $query = $this->pdo->query("SELECT id_fieldset FROM fieldset_content WHERE id =".$fieldID);
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        $fieldsetID = $query->fetch();
        $fieldsetID = $fieldsetID['id_fieldset'];

        $q = "UPDATE fieldset_content SET `key_value` = '".$fieldName."' WHERE id = ".$fieldID;
        $r = $this->pdo->exec($q);

        $result = $this->getFieldset($fieldsetID);
        return $result['name'];
    }
    
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

        $result = $this->getFieldset($fieldsetID);
        return $result['name'];
    }
    
    public function addMarkerGroup($slug, $fieldsetID) {

        $tDataID = "NULL";
        if(!ctype_digit($fieldsetID)) {
            $fieldsetID = "NULL";
        }
        else {
            $fieldsetID = "'".$fieldsetID."'";
            $id = $this->addTranslatedDataFieldsFromFieldset($fieldsetID);
            $tDataID = "'".$id."'";
        }
        
        $slug = strtolower($slug);
        $q = "INSERT INTO marker_group (slug, name, id_translated_data, id_fieldset) 
                VALUES ('".$slug."', '', ".$tDataID.", ".$fieldsetID.")";
        $r = $this->pdo->exec($q);
    }
    
    public function editMarkerGroup($slugReference, $slug, $fieldsetID) {
        
        $mgQ = "SELECT id_translated_data, id_fieldset FROM marker_group WHERE slug = '".$slugReference."'";
        $mg = $this->pdo->query($mgQ);
        $mg = $mg->fetch();

        if($fieldsetID != $mg['id_fieldset']) {
            if(!is_null($mg['id_translated_data'])) {
                $this->updateTranslatedDataFieldsRange($mg['id_translated_data'], $fieldsetID);
            }
            else {
                $id = $this->addTranslatedDataFieldsFromFieldset($fieldsetID);
            }
        }

        if($fieldsetID == 'default') {
            $fieldsetID = "NULL";
            $id = "NULL";
        }
        else {
            $fieldsetID = "'".$fieldsetID."'";
            if(!isset($id)) {
                $id = "'".$mg['id_translated_data']."'";
            }
            else {
                $id = "'".$id."'";
            }
        }

        $slug = strtolower($slug);
        $qMT = "UPDATE marker_type SET `slug_marker_group` = '".$slug."' WHERE slug_marker_group = '".$slugReference."'";
        $q = "UPDATE marker_group SET `slug` = '".$slug."', `id_translated_data` = ".$id.", `id_fieldset` = ".$fieldsetID." 
                WHERE slug = '".$slugReference."'";

        $this->pdo->exec($qMT);        
        $this->pdo->exec($q);
    }
    
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
        
    }
    
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

    public function addMarkerType($slug, $filename, $fieldsetID, $markerGroupID) {

        $tDataID = "NULL";
        if(!ctype_digit($fieldsetID)) {
            $fieldsetID = "NULL";
        }
        else {
            $fieldsetID = "'".$fieldsetID."'";
            $id = $this->addTranslatedDataFieldsFromFieldset($fieldsetID);
            $tDataID = "'".$id."'";
        }
        
        $q = "INSERT INTO marker_type (id, name, filename, slug_marker_group, id_translated_data, id_fieldset) 
                VALUES ('".$slug."', '', '".$filename."', '".$markerGroupID."', ".$tDataID.", ".$fieldsetID.")";

        $r = $this->pdo->exec($q);
    }
    
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
    
    public function editMarkerType($slugReference, $slug, $filename, $fieldsetID) {
        
        $mtQ = "SELECT id_translated_data, id_fieldset FROM marker_type WHERE id = '".$slugReference."'";
        $mt = $this->pdo->query($mtQ);
        $mt = $mt->fetch();

        if($fieldsetID != $mt['id_fieldset']) {
            if(!is_null($mt['id_translated_data'])) {
                $this->updateTranslatedDataFieldsRange($mt['id_translated_data'], $fieldsetID);
            }
            else {
                $id = $this->addTranslatedDataFieldsFromFieldset($fieldsetID);
            }
        }

        if($fieldsetID == 'default') {
            $fieldsetID = "NULL";
            $id = "NULL";
        }
        else {
            $fieldsetID = "'".$fieldsetID."'";
            if(!isset($id)) {
                $id = "'".$mt['id_translated_data']."'";
            }
            else {
                $id = "'".$id."'";
            }
        }

        $slug = strtolower($slug);
        $q = "UPDATE marker_type SET `id` = '".$slug."', `id_translated_data` = ".$id.", `id_fieldset` = ".$fieldsetID.",
                `filename` = '".$filename."' WHERE id = '".$slugReference."'";
       
        $this->pdo->exec($q);
    }
    
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

        return $id;
    }
    
    public function addTranslatedDataField($id, $lang, $key) {
        
        $q = "INSERT INTO translated_data (id, lang, keyv) 
                VALUES ('".$id."', '".$lang."', '".$key."')";
        $r = $this->pdo->exec($q);
    }

    public function removeTranslatedDataField($id, $key) {
        
        $q = "DELETE FROM translated_data WHERE id = ".$id." AND keyv = '".$key."'";
        $r = $this->pdo->exec($q);
    }
    
    public function updateTranslatedDataField($id, $lang, $key, $value) {
        
        $q = "UPDATE translated_data SET `value` = '".$value."' WHERE id = '".$id."' AND lang = '".$lang."' AND keyv = '".$key."'";
        $r = $this->pdo->exec($q);
    }
    
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
    
    public function getTranslatedDataNewID() {
        $l = $this->pdo->query("SELECT MAX(id) as max_id FROM translated_data");
        $l->setFetchMode(\PDO::FETCH_ASSOC);
        $l = $l->fetch();
        
        var_dump($l['max_id']);
        return $l['max_id'] + 1;
    }

    public function getAllLanguages() {
        
        $l = $this->pdo->query("SELECT * FROM supported_language");
        $l->setFetchMode(\PDO::FETCH_ASSOC);
        
        $langs = array();        
        foreach($l->fetchAll() as $lang) {
            array_push($langs, $lang['id']);
        }
        
        return $langs;
    }

    public function handleError(\Exception $e) {
        var_dump($e);
    }

}