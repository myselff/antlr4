<?php

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr\V4\Runtime;

use Antlr\V4\Runtime\Misc\Pair;


/** The default mechanism for creating tokens. It's used by default in Lexer and
 *  the error handling strategy (to create missing tokens).  Notifying the parser
 *  of a new factory means that it notifies its token source and error strategy.
 */
interface TokenFactory {
    /** This is the method used to create tokens in the lexer and in the
     *  error handling strategy. If text!=null, than the start and stop positions
     *  are wiped to -1 in the text override is set in the CommonToken.
     */
    public function create(
        Pair $source,
        int $type,
        string $text,
        int $channel,
        int $start,
        int $stop,
        int $line,
        int $charPositionInLine
    ): TokenInterface;

	/** Generically useful */
	public function createFromText(int $type, string $text);
}
