<?php

/**
 * @author Stephan Winiker "stephan.winiker@hslu.ch"
 */
trait ilEventoImportGetUserIdsByMatriculation
{
    private function getUserIdsByMatriculation($matriculation) {
        $res = $this->ilDB->queryF("SELECT usr_id FROM usr_data WHERE matriculation = %s",
            array("text"), array($matriculation));
        $ids=array();
        while($user_rec = $this->ilDB->fetchAssoc($res)){
            $ids[]=$user_rec["usr_id"];
        }
        return $ids;
    }
}