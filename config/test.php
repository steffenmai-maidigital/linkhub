<?php

// Testsystem-Kennzeichnung: bei TEST_MODE=true erscheint "TESTSYSTEM"
// im Header und auf der Support-Seite; unter Einstellungen → Testsystem
// lässt sich der letzte Produktionsstand einspielen (docker-compose.test.yml).
// Test system marking: with TEST_MODE=true, "TESTSYSTEM" appears
// in the header and on the support page; under Settings → Test system
// the latest production state can be restored (docker-compose.test.yml).
return [
    'enabled' => (bool) env('TEST_MODE', false),
];
