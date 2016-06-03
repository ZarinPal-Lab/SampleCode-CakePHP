<?php

class TransactionsController extends RitaZarinpalClientAppController
{
    public $components = [
        'RequestHandler',
        'RitaZarinpalClient.Zarinpal' => ['merchantID' => '524cdafd-8dec-49d3-88d7-74385ee8a9d4'], ];

    /**
     * TransactionsController::beforeFilter().
     *
     * @return void
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * TransactionsController::verification().
     *
     * @return void
     */
    public function verification()
    {
        extract($this->request->query);
        l($Authority);
        $this->Zarinpal->verification($Authority);
        l($Status);
    }
}
