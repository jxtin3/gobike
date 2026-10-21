<?php

/*
 * Go Biker's Training fee for organizations that replicate the Go Bike Project
 * (shown on /replicate-go-bike). One class covers up to `class_size` trainees;
 * each trainee beyond that adds `additional_trainee_fee`. Amounts are in pesos.
 */

return [

    'class_fee' => 30000,

    'class_size' => 15,

    'additional_trainee_fee' => 2000,

    // Larger groups are asked to contact the team so more than one class can be arranged.
    'max_trainees' => 50,

    // PayMongo methods for paying early online (the same ones donations use).
    'payment_methods' => ['gcash', 'dob'],

    // Full refund when the organization cancels at least this many days before the training (see the Terms).
    'refund_cutoff_days' => 7,

];
