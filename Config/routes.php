<?php

Router::connect(
    '/transactions',
    ['plugin' => 'RitaZarinpalClient', 'controller' => 'transactions', 'action' => 'verification']
);
