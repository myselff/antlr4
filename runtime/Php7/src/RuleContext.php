<?php

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
namespace Antlr\V4\Runtime;

use Antlr\V4\Runtime\Atn\Atn;use Antlr\V4\Runtime\Misc\Interval;use Antlr\V4\Runtime\Tree\ParseTreeInterface;use Antlr\V4\Runtime\Tree\ParseTreeVisitorInterface;use Antlr\V4\Runtime\Tree\RuleNodeInterface;

/** A rule context is a record of a single rule invocation.
 *
 *  We form a stack of these context objects using the parent
 *  pointer. A parent pointer of null indicates that the current
 *  context is the bottom of the stack. The ParserRuleContext subclass
 *  as a children list so that we can turn this data structure into a
 *  tree.
 *
 *  The root node always has a null pointer and invokingState of -1.
 *
 *  Upon entry to parsing, the first invoked rule function creates a
 *  context object (a subclass specialized for that rule such as
 *  SContext) and makes it the root of a parse tree, recorded by field
 *  Parser._ctx.
 *
 *  public final SContext s() throws RecognitionException {
 *      SContext _localctx = new SContext(_ctx, getState()); <-- create new node
 *      enterRule(_localctx, 0, RULE_s);                     <-- push it
 *      ...
 *      exitRule();                                          <-- pop back to _localctx
 *      return _localctx;
 *  }
 *
 *  A subsequent rule invocation of r from the start rule s pushes a
 *  new context object for r whose parent points at s and use invoking
 *  state is the state with r emanating as edge label.
 *
 *  The invokingState fields from a context object to the root
 *  together form a stack of rule indication states where the root
 *  (bottom of the stack) has a -1 sentinel value. If we invoke start
 *  symbol s then call r1, which calls r2, the  would look like
 *  this:
 *
 *     SContext[-1]   <- root node (bottom of the stack)
 *     R1Context[p]   <- p in rule s called r1
 *     R2Context[q]   <- q in rule r1 called r2
 *
 *  So the top of the stack, _ctx, represents a call to the current
 *  rule and it holds the return address from another rule that invoke
 *  to this rule. To invoke a rule, we must always have a current context.
 *
 *  The parent contexts are useful for computing lookahead sets and
 *  getting error information.
 *
 *  These objects are used during parsing and prediction.
 *  For the special case of parsers, we use the subclass
 *  ParserRuleContext.
 *
 *  @see ParserRuleContext
 */
class RuleContextInterface implements RuleNodeInterface
{
    //public static final ParserRuleContext EMPTY = new ParserRuleContext();

    /** What context invoked this rule? */
    /**
     * @var ?RuleContext
     */
    public $parent;

    /** What state invoked the rule associated with this context?
     *  The "return address" is the followState of invokingState
     *  If parent is null, this should be -1 this context object represents
     *  the start rule.
     */
    public $invokingState = -1;

	public function __construct(RuleContextInterface $parent = null, int $invokingState = null)
	{
	    $this->parent = $parent;
        //if ( $this->parent!=null ) var_dump("invoke " + $this->stateNumber + " from " + $this->parent);
        $this->invokingState = $invokingState;
    }

	public function depth(): int
	{
        $n = 0;
		$p = $this;
		while ( $p != null ) {
            $p = $p->parent;
            $n++;
        }
		return $n;
	}

	/** A context is empty if there is no invoking state; meaning nobody called
     *  current context.
     */
	public function isEmpty(): bool
	{
		return $this->invokingState == -1;
	}

	// satisfy the ParseTree / SyntaxTree interface

	public function getSourceInterval(): Interval
	{
	    //TODO: check this
		return new Interval(-1,-2); // Interval::INVALID;
	}

	public function getRuleContext(): RuleContextInterface
	{
	    return $this;
	}

	/**
     * @return RuleContextInterface
    */
	public function getParent()
	{
	    return $this->parent;
	}

	public function getPayload(): RuleContextInterface
	{
	    return $this;
	}

	/** Return the combined text of all child nodes. This method only considers
     *  tokens which have been added to the parse tree.
     *  <p>
     *  Since tokens on hidden channels (e.g. whitespace or comments) are not
     *  added to the parse trees, they will not appear in the output of this
     *  method.
     */
	public function getText(): string
	{
		if ($this->getChildCount() == 0) {
            return "";
        }

		$text = "";
		for ($i = 0; $i < $this->getChildCount(); $i++) {
            $text .= $this->getChild($i)->getText();
        }

		return $text;
	}

	public function getRuleIndex(): int
	{
	    return -1;
	}

	/** For rule associated with this parse tree internal node, return
     *  the outer alternative number used to match the input. Default
     *  implementation does not compute nor store this alt num. Create
     *  a subclass of ParserRuleContext with backing field and set
     *  option contextSuperClass.
     *  to set it.
     *
     *  @since 4.5.3
     */
	public function getAltNumber() { return Atn::INVALID_ALT_NUMBER; }

	/** Set the outer alternative number for this context node. Default
     *  implementation does nothing to avoid backing field overhead for
     *  trees that don't need it.  Create
     *  a subclass of ParserRuleContext with backing field and set
     *  option contextSuperClass.
     *
     *  @since 4.5.3
     */
	public function setAltNumber(int $altNumber): void
	{

	}

	/** @since 4.7. {@see ParseTree#setParent} comment */
	public function setParent(RuleContextInterface $parent): void
	{
        $this->parent = $parent;
    }

	public function getChild(int $i): ParseTreeInterface
	{
	    return null;
    }


	public function getChildCount(): int
	{
		return 0;
	}

	public function accept(ParseTreeVisitorInterface $visitor)
	{
	    return $visitor->visitChildren($this);
	}

	/** Print out a whole tree, not just a node, in LISP format
     *  (root child1 .. childN). Print just a node if this is a leaf.
     *  We have to know the recognizer so we can get rule names.
     */
	public function toStringTree(Parser $recog): int
	{
        return Trees::toStringTree(this, recog);
    }

	/** Print out a whole tree, not just a node, in LISP format
     *  (root child1 .. childN). Print just a node if this is a leaf.
     */
	public String toStringTree(List<String> ruleNames) {
    return Trees.toStringTree(this, ruleNames);
}

	@Override
	public String toStringTree() {
		return toStringTree((List<String>)null);
	}

	@Override
	public String toString() {
		return toString((List<String>)null, (RuleContext)null);
	}

	public final String toString(Recognizer<?,?> recog) {
return toString(recog, ParserRuleContext.EMPTY);
}

public final String toString(List<String> ruleNames) {
    return toString(ruleNames, null);
    }

    // recog null unless ParserRuleContext, in which case we use subclass toString(...)
    public String toString(Recognizer<?,?> recog, RuleContext stop) {
    String[] ruleNames = recog != null ? recog.getRuleNames() : null;
    List<String> ruleNamesList = ruleNames != null ? Arrays.asList(ruleNames) : null;
        return toString(ruleNamesList, stop);
        }

        public String toString(List<String> ruleNames, RuleContext stop) {
            StringBuilder buf = new StringBuilder();
            RuleContext p = this;
            buf.append("[");
            while (p != null && p != stop) {
            if (ruleNames == null) {
            if (!p.isEmpty()) {
            buf.append(p.invokingState);
            }
            }
            else {
            int ruleIndex = p.getRuleIndex();
            String ruleName = ruleIndex >= 0 && ruleIndex < ruleNames.size() ? ruleNames.get(ruleIndex) : Integer.toString(ruleIndex);
            buf.append(ruleName);
            }

            if (p.parent != null && (ruleNames != null || !p.parent.isEmpty())) {
            buf.append(" ");
            }

            p = p.parent;
            }

            buf.append("]");
            return buf.toString();
            }
            }
