<?php

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
namespace Antlr\V4\Runtime\Misc;

/** An immutable inclusive interval a..b */
class Interval {
    const INTERVAL_POOL_MAX_VALUE = 1000;

    //public static final Interval INVALID = new Interval(-1,-2);

    protected static $cache = [];

    public $a;
    public $b;

    public static $creates = 0;
    public static $misses = 0;
    public static $hits = 0;
    public static $outOfRange = 0;

    public function __construct(int $a, int $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

	/** Interval objects are used readonly so share all with the
     *  same single value a==b up to some max size.  Use an array as a perfect hash.
     *  Return shared object for 0..INTERVAL_POOL_MAX_VALUE or a new
     *  Interval object with a..a in it.  On Java.g4, 218623 IntervalSets
     *  have a..a (set with 1 element).
     */
	public static function of(int $a, int $b): Interval
    {
        // cache just a..a
        if ( $a != $b || $a < 0 || $a > self::INTERVAL_POOL_MAX_VALUE ) {
            return new Interval($a,$b);
        }

        if ( self::$cache[$a] === null ) {
            self::$cache[$a] = new Interval($a, $a);
    }
        return self::cache[$a];
}

	/** return number of elements between a and b inclusively. x..x is length 1.
     *  if b &lt; a, then length is 0.  9..10 has length 2.
     */
	public function length(): int
    {
		if ( $this->b < $this->b ) return 0;
		return $this->b - $this->a + 1;
	}

	public function equals(Object $o): bool
    {
        if ( $o === null || !($o instanceof Interval) ) {
            return false;
        }


		return $this->a == $o->a && $this->b === $o->b;
	}


	public function hashCode(): int
    {
        $hash = 23;
		$hash = $hash * 31 + $this->a;
		$hash = $hash * 31 + $this->b;
		return $hash;
	}

	/** Does this start completely before other? Disjoint */
	public function startsBeforeDisjoint(Interval $other): bool
    {
        return $this->a < $other->a && $this->b < $other->a;
    }

	/** Does this start at or before other? Nondisjoint */
	public function startsBeforeNonDisjoint(Interval $other)
    {
        return $this->a <= $other->a && $this->b >= $other->a;
    }

	/** Does this.a start after other.b? May or may not be disjoint */
	public function startsAfter(Interval $other): bool
    {
        return $this->a > $other->a;
    }

	/** Does this start completely after other? Disjoint */
	public function startsAfterDisjoint(Interval $other): bool
    {
        return $this->a > $other->b;
    }

	/** Does this start after other? NonDisjoint */
	public function startsAfterNonDisjoint(Interval $other): bool
    {
        return $this->a > $other->a && $this->a <= $other->b; // this.b>=other.b implied
    }

	/** Are both ranges disjoint? I.e., no overlap? */
	public function disjoint(Interval $other): bool
    {
        return $this->startsBeforeDisjoint($other) || $this->startsAfterDisjoint($other);
    }

	/** Are two intervals adjacent such as 0..41 and 42..42? */
	public function adjacent(Interval $other): bool
    {
        return $this->a == $other->b+1 || $this->b == $other->a-1;
    }

	public function properlyContains(Interval $other): bool
    {
        return $other->a >= $this->a && $other->b <= $this->b;
    }

	/** Return the interval computed from combining this and other */
	public function union(Interval $other): Interval
    {
        return Interval::of(min($this->a, $other->a), max($this->b, $other->b));
    }

	/** Return the interval in common between this and o */
	public function intersection(Interval $other): Interval
    {
        return Interval::of(max($this->a, $other->a), min($this->b, $other->b));
    }

	/** Return the interval with elements from this not in other;
     *  other must not be totally enclosed (properly contained)
     *  within this, which would result in two disjoint intervals
     *  instead of the single one returned by this method.
     */
	public function differenceNotProperlyContained(Interval $other): ?Interval
    {
        $diff = null;
		// other.a to left of this.a (or same)
		if ( $other->startsBeforeNonDisjoint($this) ) {
            $diff = Interval::of(max($this->a, $other->b + 1),
                    $this->b);
        }

        // other.a to right of this.a
        else if ( $other->startsAfterNonDisjoint($this) ) {
            $diff = Interval::of($this->a, $other->a - 1);
        }
		return $diff;
	}

	public function toString(): string
    {
		return a."..".b;
	}
}
