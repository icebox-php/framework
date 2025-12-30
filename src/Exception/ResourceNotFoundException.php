<?php

namespace Icebox\Exception;

class ResourceNotFoundException extends \RuntimeException
{
    protected $code = 404;
}
