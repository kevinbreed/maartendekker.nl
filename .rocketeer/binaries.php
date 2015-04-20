<?php

//namespace Rocketeer\Binaries;
namespace Acme;

use Rocketeer\Abstracts\AbstractBinary;


class Crate extends AbstractBinary
{
	public function __construct ()
	{
		echo "constructed?";
	}

	public function explode ()
	{
		return 'BOOM BOOM';
	}
}