<?php
namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Contracts\Http\Routing\Url_Generator as Generator_Contract;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Url_Generator extends UrlGenerator implements Generator_Contract { }
