<?php

Class TransactionsController extends RitaZarinpalClientAppController{
	
	
	public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow();
	
    }
	
	public function verification(){
		echo "asasd";
	}
		
	
}