<?php

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr\V4\Runtime\Tree;

use Antlr\V4\Runtime\Parser;
use Antlr\V4\Runtime\RuleContextInterface;


/** An interface to access the tree of {@link RuleContext} objects created
 *  during a parse that makes the data structure look like a simple parse tree.
 *  This node represents both internal nodes, rule invocations,
 *  and leaf nodes, token matches.
 *
 *  <p>The payload is either a {@link Token} or a {@link RuleContext} object.</p>
 */
interface ParseTreeInterface extends SyntaxTreeInterface {
    // the following methods narrow the return type; they are not additional methods
    /**
     * @return ParseTreeInterface
     */
    public function getParent();

    /**
     * @param int $i
     * @return ParseTreeInterface
     */
    public function getChild(int $i);


    /** Set the parent for this node.
     *
     *  This is not backward compatible as it changes
     *  the interface but no one was able to create custom
     *  nodes anyway so I'm adding as it improves internal
     *  code quality.
     *
     *  One could argue for a restructuring of
     *  the class/interface hierarchy so that
     *  setParent, addChild are moved up to Tree
     *  but that's a major change. So I'll do the
     *  minimal change, which is to add this method.
     *
     *  @since 4.7
     */
    public function setParent(RuleContextInterface $parent): void;

    /** The {@link ParseTreeVisitor} needs a double dispatch method. */
    public function accept(ParseTreeVisitorInterface $visitor);

    /** Return the combined text of all leaf nodes. Does not get any
     *  off-channel tokens (if any) so won't return whitespace and
     *  comments if they are sent to parser on hidden channel.
     */
    public function getText(): string;

    /** Specialize toStringTree so that it can print out more information
     * 	based upon the parser.
     */
    public function toStringTree(Parser $parser = null): string;
}
