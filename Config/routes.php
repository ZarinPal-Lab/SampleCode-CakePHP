<?php

Router::connect(
	'/transactions',
	array('plugin' => 'RitaZarinpalClient', 'controller' => 'transactions','action' => 'verification')
);

