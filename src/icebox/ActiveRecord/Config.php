<?php

/**
 * @package Icebox
 */

use ActiveRecord\Config as ArConfig;

class Config
{
    public static function initialize(Closure $initializer)
  	{
  		  ArConfig($initializer);
  	}
}
