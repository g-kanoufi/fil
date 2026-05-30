<?php

test('root redirects to staff app login', function () {
    $this->get('/')->assertRedirect('/app');
});
