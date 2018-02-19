<?php

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr\V4\Runtime\Misc;



use Z38\MurmurHash\Murmur3F;

class Pair /*implements \Serializable*/
{
	public $a;
	public $b;

	public function __construct($a, $b)
	{
		$this->a = $a;
		$this->b = $b;
	}


	public function equals(object $obj): bool
	{
		if ($obj == $this) {
			return true;
		}
		else if (!($obj instanceof Pair)) {
			return false;
		}

		return $this->a === $obj->a && $this->b === $obj->b;
	}

	public function hashCode(): string
    {
        // TODO: check if it is correct
		$hash = new Murmur3F();
		$hash->write($this->a);
		$hash->write($this->b);
		return bin2hex($hash->sum());
	}

	public function __toString() {
		return sprintf("(%s, %s)", $this->a, $this->b);
	}
}
