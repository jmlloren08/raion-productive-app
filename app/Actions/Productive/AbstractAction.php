<?php

namespace App\Actions\Productive;

abstract class AbstractAction
{
    /**
     * Execute the action.
     *
     * @param array $parameters
     * @return mixed
     */
    abstract public function handle(array $parameters = []);
}
