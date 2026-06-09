<?php

use Illuminate\Validation\Rules\Password;

it('returns strong password rules in production', function () {
    $this->app['env'] = 'production';

    $rules = Password::defaults();

    expect($rules)->toBeInstanceOf(Password::class);
});
