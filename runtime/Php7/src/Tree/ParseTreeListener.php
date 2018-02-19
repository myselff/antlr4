<?php
/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr\V4\Runtime\Tree;

use Antlr\V4\Runtime\ParserRuleContext;

/** This interface describes the minimal core of methods triggered
 *  by {@link ParseTreeWalker}. E.g.,
 *
 *  	ParseTreeWalker walker = new ParseTreeWalker();
 *		walker.walk(myParseTreeListener, myParseTree); <-- triggers events in your listener
 *
 *  If you want to trigger events in multiple listeners during a single
 *  tree walk, you can use the ParseTreeDispatcher object available at
 *
 * 		https://github.com/antlr/antlr4/issues/841
 */
interface ParseTreeListener {
	public function visitTerminal(TerminalNode $node): void;
	public function visitErrorNode(ErrorNode $node): void;
    public function enterEveryRule(ParserRuleContext $ctx): void;
    public function exitEveryRule(ParserRuleContext $ctx): void;
}
