<?php

/*
 * Volunteer training is free and given to a whole class at once: it starts when enough
 * volunteers have signed up. Individual (paid) training starts on the person's own date instead.
 */
return [
    // A class is scheduled once this many volunteers are waiting.
    'class_min' => 20,

    // At most this many volunteers in one class.
    'class_max' => 25,
];
