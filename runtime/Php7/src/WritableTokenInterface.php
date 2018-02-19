<?php

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr\V4\Runtime;

interface WritableTokenInterface extends TokenInterface {
    public function setText(string $text): void;

    public function setType(int $ttype): void;

    public function setLine(int $line): void;

    public function setCharPositionInLine(int $pos): void;

    public function setChannel(int $channel): void;

    public function setTokenIndex(int $index);
}
