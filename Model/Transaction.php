<?php

class Transaction extends RitaZarinpalClientAppModel{




	public function createTransaction(){
		$this->create(false);
		$this->save(array('type' => 'temp'),false);
		return $this->getLastInsertID();
	}	
	
	
	public function startTransaction($id,$data){
		$this->id = $id;
		return  $this->save($data,false);
	}	
	
}