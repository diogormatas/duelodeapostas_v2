<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Aplicação
    |--------------------------------------------------------------------------
    */

    'name' => 'Duelo de Apostas',

    'env' => 'development',

    'debug' => true,

    'timezone' => 'Europe/Lisbon',

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    | Usado para gerar links nas views
    */

    'base_url' => '/duelo/v2/public',

    /*
    |--------------------------------------------------------------------------
    | Regras gerais de cupões
    |--------------------------------------------------------------------------
    */

    'coupon_close_minutes_before_match' => 60,

    'default_coupon_max_players' => 10,

    /*
    |--------------------------------------------------------------------------
    | Liga Tips
    |--------------------------------------------------------------------------
    */

    'liga_tips_matches' => 15,

    'liga_tips_entry_price' => 5,

    'liga_tips_max_players' => 100,

    /*
    |--------------------------------------------------------------------------
    | Jackpot
    |--------------------------------------------------------------------------
    */

    'jackpot_percentage' => 10,

    /*
    |--------------------------------------------------------------------------
    | Distribuição de prémios
    |--------------------------------------------------------------------------
    */

    'prize_distribution' => [

        [
            'max_players' => 4,
            'positions' => [
                1 => 100
            ]
        ],

        [
            'max_players' => 8,
            'positions' => [
                1 => 70,
                2 => 30
            ]
        ],

        [
            'max_players' => 29,
            'positions' => [
                1 => 50,
                2 => 30,
                3 => 20
            ]
        ],

        [
            'max_players' => 999999,
            'positions' => [
                1 => 46,
                2 => 26,
                3 => 16,
                4 => 12
            ]
        ]

    ],

    /*
    |--------------------------------------------------------------------------
    | Sistema de Duelos
    |--------------------------------------------------------------------------
    */

    'duels' => [

        /*
        |--------------------------------
        | Stakes disponíveis
        |--------------------------------
        */

        'stakes' => [2,5,10,20],

        // stake mínimo permitido
        'min_stake' => 2,

        // stake máximo permitido
        'max_stake' => 20,

        /*
        |--------------------------------
        | Limites anti-spam
        |--------------------------------
        */

        // desafios pendentes por utilizador
        'max_open_duels_per_user' => 3,

        // expiração automática
        'pending_expiration_hours' => 72,

        /*
        |--------------------------------
        | Jogos por duelo
        |--------------------------------
        */

        // número padrão sugerido (modo automático)
        'matches_per_duel' => 10,

        // mínimo jogos permitidos
        'min_matches' => 5,

        // máximo jogos permitidos
        'max_matches' => 15,

        /*
        |--------------------------------
        | Janela temporal jogos
        |--------------------------------
        */

        // mínimo horas antes do jogo começar
        'min_hours_before_match' => 3,

        // máximo dias para a frente
        'max_days_ahead' => 7

    ],

];