<?php

it('renders the login page in the browser', function () {
    visit('/login')
        ->assertSee('Log in')
        ->assertNoJavascriptErrors();
});
