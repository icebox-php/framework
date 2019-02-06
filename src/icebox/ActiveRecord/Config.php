<?php

/**
 * @package Icebox
 */

namespace Icebox\ActiveRecord;

class Config
{
    public static function initialize(Closure $initializer)
  	{
  		  \ActiveRecord\Config::initialize($initializer);
  	}
}
