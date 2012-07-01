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

        $q = "INSERT INTO modification_list (date_added, value, id_reference_at_submission) 
                         VALUES ('".$date."', '".$json."', '".$idReference."')";

        $this->pdo->exec($q);
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
        $this->retrieveResources();
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
    
    public function retrieveResources() {
        $result = $this->pdo->query("SELECT * FROM resources");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        $this->data["resources"] = $result->fetchAll();
    }
    
    public function retrieveAreasList() {
        $result = $this->pdo->query("SELECT * FROM areas_list");
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        
        foreach($result->fetchAll() as $row) {
            $this->data["areas-list"][$row["id"]] = $row;
        }
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

    public function handleError(\Exception $e) {
        var_dump($e);
    }

}