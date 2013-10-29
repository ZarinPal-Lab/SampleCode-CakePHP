<?php

class Transaction extends RitaZarinpalClientAppModel{




	public function createTransaction(){
		$this->create();
		$this->save(array(
			'type' => 'temp', 
			'created_at'=> time()
		));
		return $this->getLastInsertID();
	}	
	
	
/**
 * Transaction::startTransaction()
 * 
 * @param mixed $id
 * @param mixed $data
 * @return
 */
	public function startTransaction($data){
		$data['type'] = 'start';
		$data['started_at'] = time();
		 if( $this->save($data)){
		 	return true;
		 }else{
		 	return false;
		 }
	}	
	
}