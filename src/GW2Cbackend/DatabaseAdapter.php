<?php

namespace GW2CBackend;

class DatabaseAdapter {

    protected $pdo;
    
    protected $data;

    public function connect($host, $port, $database, $user, $pword) {
        try {
            $this->pdo = new \PDO('mysql:host='.$host.';port='.$port.';dbname='.$database, $user, $pword);
        }
        catch(\Exception $e) {
            $this->handleError($e);
        }
    }
    
    public function retrieveAll() {
        $this->retrieveOptions();
        $this->retrieveResources();
        $this->retrieveAreasList();
        $this->retrieveFirstModification();
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
        
        $this->data["areas-list"] = $result->fetchAll();
    }
    
    public function retrieveFirstModification() {
        $result = $this->pdo->query("SELECT * FROM modification_list ORDER BY date_added LIMIT 0,1");
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